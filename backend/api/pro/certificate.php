<?php
header('Content-Type: application/pdf');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

$userEmail = $_GET['email'] ?? $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? '';

if (empty($userEmail)) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Email parameter required'
    ]);
    exit;
}

try {
    // Check if user has Pro subscription
    $subStmt = $pdo->prepare("
        SELECT s.plan_code, s.subscription_status, s.is_active 
        FROM subscriptions s 
        WHERE s.email = ? AND s.is_active = 1 
        ORDER BY s.created_at DESC 
        LIMIT 1
    ");
    $subStmt->execute([$userEmail]);
    $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
    
    // Allow soudhame52@gmail.com (test user) or Pro users
    $isPro = ($userEmail === 'soudhame52@gmail.com') || 
             ($subscription && $subscription['plan_code'] === 'pro' && $subscription['subscription_status'] === 'active');
    
    if (!$isPro) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Certificates are only available for Pro subscribers',
            'upgrade_required' => true
        ]);
        exit;
    }
    
    // Get user details
    $userStmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE email = ?");
    $userStmt->execute([$userEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found'
        ]);
        exit;
    }
    
    $userId = $user['id'];
    $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    if (empty($userName)) {
        $userName = 'Student'; // Fallback name
    }
    
    // Calculate overall progress
    $progressStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_tutorials,
            AVG(completion_percentage) as avg_completion,
            SUM(CASE WHEN completion_percentage >= 80 THEN 1 ELSE 0 END) as completed_tutorials
        FROM learning_progress lp
        JOIN tutorials t ON lp.tutorial_id = t.id
        WHERE lp.user_id = ?
    ");
    $progressStmt->execute([$userId]);
    $progress = $progressStmt->fetch(PDO::FETCH_ASSOC);
    
    $overallProgress = $progress['avg_completion'] ?? 0;
    $completedTutorials = $progress['completed_tutorials'] ?? 0;
    $totalTutorials = $progress['total_tutorials'] ?? 0;
    
    // Check if eligible for certificate (80% overall completion)
    if ($overallProgress < 80) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Certificate not available. Complete at least 80% of tutorials to earn your certificate.',
            'current_progress' => round($overallProgress, 1),
            'required_progress' => 80
        ]);
        exit;
    }
    
    // Generate certificate ID
    $certificateId = 'MLT-' . strtoupper(substr(md5($userEmail . date('Y-m-d')), 0, 8));
    
    // Create simple HTML certificate (since TCPDF might not be available)
    $certificateHtml = generateCertificateHTML($userName, $certificateId, $completedTutorials, $totalTutorials);
    
    // Try to convert to PDF if possible, otherwise return HTML
    if (class_exists('TCPDF')) {
        generatePDFCertificate($certificateHtml, $userName);
    } else {
        // Return HTML certificate
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="certificate_' . $certificateId . '.html"');
        echo $certificateHtml;
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Certificate generation failed: ' . $e->getMessage()
    ]);
}

function generateCertificateHTML($userName, $certificateId, $completedTutorials, $totalTutorials) {
    $completionDate = date('F j, Y');
    
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