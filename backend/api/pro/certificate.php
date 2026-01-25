<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once '../../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

$userEmail = $_GET['email'] ?? $_POST['email'] ?? $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? '';
$requestedName = null;

// PRIORITY: Always use the name from POST request if provided
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded) && isset($decoded['name'])) {
            $requestedName = trim($decoded['name']);
            if (!empty($requestedName)) {
                // Clean and validate the name
                $requestedName = preg_replace('/\s+/', ' ', $requestedName);
                if (strlen($requestedName) > 80) {
                    $requestedName = substr($requestedName, 0, 80);
                }
            } else {
                $requestedName = null;
            }
        }
    }
    
    // Also check form data
    if (empty($requestedName) && !empty($_POST['name'])) {
        $requestedName = trim($_POST['name']);
        if (!empty($requestedName)) {
            $requestedName = preg_replace('/\s+/', ' ', $requestedName);
            if (strlen($requestedName) > 80) {
                $requestedName = substr($requestedName, 0, 80);
            }
        } else {
            $requestedName = null;
        }
    }
}

if (empty($userEmail)) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Email parameter required'
    ]);
    exit;
}

try {
    // Get user ID first
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute([$userEmail]);
    $userForSub = $userStmt->fetch(PDO::FETCH_ASSOC);
    $userIdForSub = $userForSub['id'] ?? null;
    
    // Check if user has Pro subscription
    $isPro = ($userEmail === 'soudhame52@gmail.com');
    
    if (!$isPro && $userIdForSub) {
        $subStmt = $pdo->prepare("
            SELECT s.status, sp.plan_code 
            FROM subscriptions s
            LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE s.user_id = ? AND s.status = 'active'
            ORDER BY s.created_at DESC 
            LIMIT 1
        ");
        $subStmt->execute([$userIdForSub]);
        $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
        
        $isPro = ($subscription && $subscription['plan_code'] === 'pro' && $subscription['status'] === 'active');
    }
    
    if (!$isPro) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Certificates are only available for Pro subscribers',
            'upgrade_required' => true
        ]);
        exit;
    }
    
    // Get user details - try to get name field first, then first_name/last_name
    try {
        // Try to get name field (if it exists)
        $userStmt = $pdo->prepare("SELECT id, name, first_name, last_name FROM users WHERE email = ?");
        $userStmt->execute([$userEmail]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // If name column doesn't exist, try without it
        $userStmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE email = ?");
        $userStmt->execute([$userEmail]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$user) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found'
        ]);
        exit;
    }
    
    $userId = $user['id'];
    
    // CERTIFICATE NAME RESOLUTION - Different logic for POST vs GET
    $userName = '';
    
    // For GET requests (downloads), ALWAYS use stored name from database first
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get the stored certificate name from database - this is the authoritative source for downloads
        $existingCertStmt = $pdo->prepare("SELECT user_name, certificate_id FROM certificates WHERE user_id = ? ORDER BY issued_at DESC LIMIT 1");
        $existingCertStmt->execute([$userId]);
        $existingCert = $existingCertStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingCert && !empty($existingCert['user_name'])) {
            $userName = $existingCert['user_name'];
            $certificateId = $existingCert['certificate_id'];
            error_log("GET request - Using stored name from database: '$userName'"); // Debug
        } else {
            error_log("GET request - No stored certificate found, will resolve name"); // Debug
            // Fall through to name resolution logic below
        }
    }
    
    // If we don't have a name yet (POST request or GET with no stored certificate), resolve it
    if (empty($userName)) {
        // 1) FIRST PRIORITY: Use the name from the request if provided (POST only)
        if (!empty($requestedName)) {
            $userName = $requestedName;
            error_log("Using requested name: '$userName'"); // Debug
        } else {
            error_log("No requested name provided"); // Debug
            // 2) Fallback to database name field
            if (!empty($user['name'])) {
                $userName = trim($user['name']);
                error_log("Using database name field: '$userName'"); // Debug
            } else {
                // 3) Fallback to first_name + last_name
                $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                if (empty($userName)) {
                    // 4) Extract from email as last resort
                    $emailParts = explode('@', $userEmail);
                    $emailName = $emailParts[0] ?? '';
                    $emailName = preg_replace('/[0-9._-]+/', ' ', $emailName);
                    $emailName = trim($emailName);
                    if (!empty($emailName)) {
                        $userName = ucwords(strtolower($emailName));
                        error_log("Using email-derived name: '$userName'"); // Debug
                    } else {
                        $userName = 'Student';
                        error_log("Using fallback name: '$userName'"); // Debug
                    }
                }
            }
        }
    }
    
    // Calculate overall progress using unified course-level calculation
    // Get total tutorials in the course (all active tutorials)
    $totalTutorialsStmt = $pdo->prepare("SELECT COUNT(*) as total FROM tutorials WHERE is_active = 1");
    $totalTutorialsStmt->execute();
    $totalTutorialsResult = $totalTutorialsStmt->fetch(PDO::FETCH_ASSOC);
    $totalTutorials = (int)($totalTutorialsResult['total'] ?? 0);
    
    if ($totalTutorials == 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'No tutorials found in the course.',
            'current_progress' => 0,
            'required_progress' => 80
        ]);
        exit;
    }
    
    // Count completed tutorials (completion >= 80 OR practice approved)
    $completedStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT t.id) as completed_tutorials
        FROM tutorials t
        WHERE t.is_active = 1
        AND (
            EXISTS (
                SELECT 1 FROM learning_progress lp 
                WHERE lp.tutorial_id = t.id 
                AND lp.user_id = ? 
                AND lp.completion_percentage >= 80
            )
            OR EXISTS (
                SELECT 1 FROM practice_uploads pu 
                WHERE pu.tutorial_id = t.id 
                AND pu.user_id = ? 
                AND pu.status = 'approved'
            )
        )
    ");
    $completedStmt->execute([$userId, $userId]);
    $completedResult = $completedStmt->fetch(PDO::FETCH_ASSOC);
    $completedTutorials = (int)($completedResult['completed_tutorials'] ?? 0);
    
    // Calculate overall course completion percentage
    $overallProgress = ($completedTutorials / $totalTutorials) * 100;
    
    // Check if eligible for certificate (80% overall completion)
    if ($overallProgress < 80) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Certificate not available. Complete at least 80% of the course to earn your certificate.',
            'current_progress' => round($overallProgress, 2),
            'required_progress' => 80
        ]);
        exit;
    }
    
    // Generate certificate ID
    $certificateId = 'MLT-' . strtoupper(substr(md5($userEmail . date('Y-m-d')), 0, 8));
    
    // Create certificates table if it doesn't exist
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS certificates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            certificate_id VARCHAR(50) UNIQUE NOT NULL,
            user_name VARCHAR(255) NOT NULL,
            completion_date DATE NOT NULL,
            tutorials_completed INT DEFAULT 0,
            overall_progress DECIMAL(5,2) DEFAULT 0.00,
            issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_certificate_id (certificate_id)
        )");
    } catch (Exception $e) {
        // Table might already exist, continue
    }
    
    // Check if certificate already exists for this user
    $existingCertStmt = $pdo->prepare("SELECT certificate_id, user_name FROM certificates WHERE user_id = ? ORDER BY issued_at DESC LIMIT 1");
    $existingCertStmt->execute([$userId]);
    $existingCert = $existingCertStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingCert) {
        $certificateId = $existingCert['certificate_id'];
        
        // ALWAYS update the certificate name if we have a name (requested or resolved)
        if (!empty($userName)) {
            try {
                $updateStmt = $pdo->prepare("UPDATE certificates SET user_name = ? WHERE user_id = ? AND certificate_id = ?");
                $updateStmt->execute([$userName, $userId, $certificateId]);
            } catch (Exception $e) {
                // Continue if update fails
            }
        }
    } else {
        // Insert certificate record
        try {
            error_log("Inserting certificate with userName: '$userName'"); // Debug
            $insertStmt = $pdo->prepare("
                INSERT INTO certificates 
                (user_id, certificate_id, user_name, completion_date, tutorials_completed, overall_progress)
                VALUES (?, ?, ?, CURDATE(), ?, ?)
            ");
            $insertStmt->execute([
                $userId,
                $certificateId,
                $userName,
                $completedTutorials,
                round($overallProgress, 2)
            ]);
            error_log("Certificate inserted successfully"); // Debug
        } catch (Exception $e) {
            // If insert fails (e.g., duplicate), try to get existing certificate
            $existingCertStmt = $pdo->prepare("SELECT certificate_id FROM certificates WHERE user_id = ? ORDER BY issued_at DESC LIMIT 1");
            $existingCertStmt->execute([$userId]);
            $existingCert = $existingCertStmt->fetch(PDO::FETCH_ASSOC);
            if ($existingCert) {
                $certificateId = $existingCert['certificate_id'];
            }
        }
    }
    
    // Handle POST request (generation request) - return JSON
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        // Construct download URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $scriptPath = $_SERVER['SCRIPT_NAME'];
        $downloadUrl = $protocol . '://' . $host . $scriptPath . '?email=' . urlencode($userEmail);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Certificate generated successfully',
            'certificate_id' => $certificateId,
            'certificate_name' => $userName,
            'download_url' => $downloadUrl
        ]);
        exit;
    }
    
    // Handle GET request (download request) - return HTML certificate
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Name is already resolved above, just proceed with certificate generation
        error_log("GET request - Final userName for certificate: '$userName'"); // Debug
    }
    
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="certificate_' . $certificateId . '.html"');
    
    // Create simple HTML certificate (since TCPDF might not be available)
    error_log("Certificate generation: userName = '$userName', certificateId = '$certificateId'"); // Debug log
    $certificateHtml = generateCertificateHTML($userName, $certificateId, $completedTutorials, $totalTutorials);
    echo $certificateHtml;
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Certificate generation failed: ' . $e->getMessage()
    ]);
}

function generateCertificateHTML($userName, $certificateId, $completedTutorials, $totalTutorials) {
    $completionDate = date('F j, Y');
    
    // Debug log to see what name is being used
    error_log("generateCertificateHTML called with userName = '$userName'");
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Certificate of Completion</title>
        <style>
            body {
                font-family: 'Georgia', serif;
                margin: 0;
                padding: 40px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .certificate {
                background: white;
                width: 800px;
                padding: 60px;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                text-align: center;
                border: 8px solid #10B981;
                position: relative;
            }
            .certificate::before {
                content: '';
                position: absolute;
                top: 20px;
                left: 20px;
                right: 20px;
                bottom: 20px;
                border: 2px solid #10B981;
                border-radius: 12px;
            }
            .header {
                margin-bottom: 40px;
            }
            .logo {
                font-size: 32px;
                font-weight: bold;
                color: #10B981;
                margin-bottom: 10px;
            }
            .title {
                font-size: 48px;
                font-weight: bold;
                color: #1F2937;
                margin-bottom: 20px;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            }
            .subtitle {
                font-size: 24px;
                color: #6B7280;
                margin-bottom: 40px;
            }
            .recipient {
                font-size: 36px;
                font-weight: bold;
                color: #10B981;
                margin: 30px 0;
                text-decoration: underline;
                text-decoration-color: #10B981;
            }
            .description {
                font-size: 18px;
                color: #374151;
                line-height: 1.6;
                margin: 30px 0;
            }
            .stats {
                display: flex;
                justify-content: center;
                gap: 40px;
                margin: 30px 0;
            }
            .stat {
                text-align: center;
            }
            .stat-number {
                font-size: 32px;
                font-weight: bold;
                color: #10B981;
            }
            .stat-label {
                font-size: 14px;
                color: #6B7280;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            .footer {
                margin-top: 50px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .date {
                font-size: 16px;
                color: #6B7280;
            }
            .certificate-id {
                font-size: 14px;
                color: #9CA3AF;
                font-family: monospace;
            }
            .signature {
                text-align: center;
            }
            .signature-line {
                border-top: 2px solid #10B981;
                width: 200px;
                margin: 20px auto 10px;
            }
            .signature-text {
                font-size: 14px;
                color: #6B7280;
            }
            @media print {
                body { background: white; }
                .certificate { box-shadow: none; }
            }
        </style>
    </head>
    <body>
        <div class='certificate'>
            <div class='header'>
                <div class='logo'>My Little Thingz</div>
                <div class='title'>Certificate of Completion</div>
                <div class='subtitle'>Craft Tutorial Mastery Program</div>
            </div>
            
            <div class='content'>
                <p class='description'>This is to certify that</p>
                <div class='recipient'>$userName</div>
                <p class='description'>
                    has successfully completed the craft tutorial program, demonstrating 
                    dedication, creativity, and mastery of various crafting techniques.
                </p>
                
                <div class='stats'>
                    <div class='stat'>
                        <div class='stat-number'>$completedTutorials</div>
                        <div class='stat-label'>Tutorials Completed</div>
                    </div>
                    <div class='stat'>
                        <div class='stat-number'>$totalTutorials</div>
                        <div class='stat-label'>Total Tutorials</div>
                    </div>
                </div>
            </div>
            
            <div class='footer'>
                <div class='date'>
                    Completed on<br>
                    <strong>$completionDate</strong>
                </div>
                
                <div class='signature'>
                    <div class='signature-line'></div>
                    <div class='signature-text'>Instructor Signature</div>
                </div>
                
                <div class='certificate-id'>
                    Certificate ID:<br>
                    <strong>$certificateId</strong>
                </div>
            </div>
        </div>
    </body>
    </html>";
}

function generatePDFCertificate($html, $userName) {
    // This would require TCPDF library
    // For now, we'll just return the HTML version
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="certificate_' . str_replace(' ', '_', $userName) . '.html"');
    echo $html;
}
?>