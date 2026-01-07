<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once '../../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get user email
    $email = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? '';
    
    if (empty($email)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User email required in X-Tutorial-Email header'
        ]);
        exit;
    }
    
    // Check user's subscription status directly
    $subStmt = $pdo->prepare("
        SELECT s.plan_code, s.subscription_status, s.is_active, sp.plan_name
        FROM subscriptions s
        LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code
        WHERE s.email = ? AND s.is_active = 1
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $subStmt->execute([$email]);
    $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
    
    // Determine access level
    $hasProAccess = false;
    $currentPlan = 'basic';
    
    if ($subscription) {
        $currentPlan = $subscription['plan_code'];
        $hasProAccess = in_array($currentPlan, ['pro', 'premium']);
    }
    
    if (!$hasProAccess) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Live workshops require Pro or Premium subscription',
            'error_code' => 'FEATURE_ACCESS_DENIED',
            'current_plan' => $currentPlan,
            'required_plans' => ['pro', 'premium'],
            'upgrade_required' => true
        ]);
        exit;
    }
    
    // Create live_sessions table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS live_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        instructor_name VARCHAR(255),
        scheduled_date DATE,
        scheduled_time TIME,
        duration_minutes INT DEFAULT 60,
        max_participants INT DEFAULT 50,
        google_meet_link VARCHAR(500),
        status ENUM('scheduled', 'live', 'completed', 'cancelled') DEFAULT 'scheduled',
        workshop_type ENUM('pro_only', 'premium_only', 'all_paid') DEFAULT 'pro_only',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Check if we have sample workshops, if not create them
    $workshopCount = $pdo->query("SELECT COUNT(*) FROM live_sessions")->fetchColumn();
    if ($workshopCount == 0) {
        $sampleWorkshops = [
            [
                'Advanced Ring Making Techniques',
                'Master advanced techniques for creating professional-quality rings with intricate designs',
                'Sarah Johnson',
                date('Y-m-d', strtotime('+3 days')),
                '14:00:00',
                90,
                25,
                'https://meet.google.com/abc-defg-hij',
                'scheduled'
            ],
            [
                'Resin Art Masterclass',
                'Learn professional resin art techniques and create stunning pieces',
                'Michael Chen',
                date('Y-m-d', strtotime('+7 days')),
                '16:00:00',
                120,
                30,
                'https://meet.google.com/xyz-uvwx-rst',
                'scheduled'
            ],
            [
                'Business of Handmade Crafts',
                'Turn your craft skills into a profitable business with expert guidance',
                'Emma Rodriguez',
                date('Y-m-d', strtotime('+10 days')),
                '18:00:00',
                75,
                40,
                'https://meet.google.com/lmn-opqr-stu',
                'scheduled'
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO live_sessions 
            (title, description, instructor_name, scheduled_date, scheduled_time, duration_minutes, max_participants, google_meet_link, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sampleWorkshops as $workshop) {
            $stmt->execute($workshop);
        }
    }
    
    // Get upcoming workshops for Pro users
    $stmt = $pdo->prepare("
        SELECT 
            id,
            title,
            description,
            instructor_name,
            scheduled_date,
            scheduled_time,
            duration_minutes,
            max_participants,
            google_meet_link,
            status,
            CONCAT(scheduled_date, ' ', scheduled_time) as full_datetime
        FROM live_sessions 
        WHERE status IN ('scheduled', 'live')
        AND workshop_type = 'pro_only'
        ORDER BY scheduled_date ASC, scheduled_time ASC
    ");
    
    $stmt->execute();
    $workshops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format workshops for frontend
    foreach ($workshops as &$workshop) {
        $workshop['formatted_date'] = date('F j, Y', strtotime($workshop['scheduled_date']));
        $workshop['formatted_time'] = date('g:i A', strtotime($workshop['scheduled_time']));
        $workshop['is_upcoming'] = strtotime($workshop['full_datetime']) > time();
        $workshop['can_join'] = $workshop['status'] === 'live';
    }
    
    echo json_encode([
        'status' => 'success',
        'workshops' => $workshops,
        'access_level' => $currentPlan,
        'subscription_info' => $subscription,
        'message' => 'Pro-level live workshops available',
        'total_workshops' => count($workshops)
    ]);
    
} catch (Exception $e) {
    error_log('Live workshops API error: ' . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error occurred: ' . $e->getMessage(),
        'error_code' => 'INTERNAL_ERROR'
    ]);
}
?>