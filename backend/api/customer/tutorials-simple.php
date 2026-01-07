<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Simple error handling
try {
    require_once '../../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

$userEmail = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? '';

try {
    // First, ensure tutorials table exists with basic structure
    $pdo->exec("CREATE TABLE IF NOT EXISTS tutorials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        video_url VARCHAR(500),
        thumbnail_url VARCHAR(500),
        duration_minutes INT DEFAULT 0,
        difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
        category VARCHAR(100) DEFAULT 'general',
        is_free BOOLEAN DEFAULT 0,
        price DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Get tutorials
    $stmt = $pdo->query("SELECT * FROM tutorials ORDER BY created_at DESC");
    $tutorials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no tutorials exist, create some sample ones
    if (empty($tutorials)) {
        $sampleTutorials = [
            ['Ring', 'Learn to make beautiful rings', 'https://example.com/ring.mp4', 45, 'intermediate', 0, 299.00],
            ['Earing', 'Create stunning earrings', 'https://example.com/earing.mp4', 30, 'beginner', 1, 0.00],
            ['Kitkat Chocolate boquetes', 'Chocolate bouquet tutorial', 'https://example.com/kitkat.mp4', 60, 'advanced', 0, 399.00],
            ['Clock resin art', 'Resin art clock making', 'https://example.com/clock.mp4', 90, 'intermediate', 0, 499.00],
            ['Mirror clay', 'Clay mirror decoration', 'https://example.com/mirror.mp4', 40, 'beginner', 0, 199.00]
        ];
        
        $insertStmt = $pdo->prepare("INSERT INTO tutorials (title, description, video_url, duration_minutes, difficulty_level, is_free, price) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($sampleTutorials as $tutorial) {
            $insertStmt->execute($tutorial);
        }
        
        // Fetch the newly created tutorials
        $stmt = $pdo->query("SELECT * FROM tutorials ORDER BY created_at DESC");
        $tutorials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Add access information if user email provided
    if (!empty($userEmail)) {
        // Check subscription status
        $hasSubscription = false;
        try {
            $subStmt = $pdo->prepare("SELECT COUNT(*) as count FROM subscriptions WHERE email = ? AND is_active = 1");
            $subStmt->execute([$userEmail]);
            $hasSubscription = $subStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        } catch (Exception $e) {
            // Subscription table might not exist, that's ok
        }
        
        // Check purchases
        $purchases = [];
        try {
            $purchaseStmt = $pdo->prepare("SELECT tutorial_id FROM tutorial_purchases WHERE email = ? AND payment_status = 'completed'");
            $purchaseStmt->execute([$userEmail]);
            $purchases = $purchaseStmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            // Purchase table might not exist, that's ok
        }
        
        // Add access info to each tutorial
        foreach ($tutorials as &$tutorial) {
            if ($tutorial['is_free']) {
                $tutorial['has_access'] = true;
                $tutorial['access_type'] = 'FREE';
            } elseif ($hasSubscription) {
                $tutorial['has_access'] = true;
                $tutorial['access_type'] = 'SUBSCRIPTION';
                $tutorial['subscription_plan'] = 'pro';
            } elseif (in_array($tutorial['id'], $purchases)) {
                $tutorial['has_access'] = true;
                $tutorial['access_type'] = 'PURCHASED';
            } else {
                $tutorial['has_access'] = false;
                $tutorial['access_type'] = 'DENIED';
            }
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'tutorials' => $tutorials,
        'user_email' => $userEmail,
        'total_count' => count($tutorials)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>