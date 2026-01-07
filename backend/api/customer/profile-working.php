<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email, X-User-ID");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

try {
    require_once "../../config/database.php";
    $database = new Database();
    $db = $database->getConnection();
    
    $userEmail = $_SERVER["HTTP_X_TUTORIAL_EMAIL"] ?? $_GET["email"] ?? null;
    $userId = $_SERVER["HTTP_X_USER_ID"] ?? $_GET["user_id"] ?? null;
    
    // Get user ID from email if not provided
    if (!$userId && $userEmail) {
        $userStmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $userStmt->execute([$userEmail]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        $userId = $user ? $user["id"] : null;
    } else if ($userId) {
        $userStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$user) {
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit;
    }
    
    // Get active subscription
    $subStmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name, sp.price, sp.features
        FROM subscriptions s
        LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.user_id = ? AND s.status = 'active'
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $subStmt->execute([$userId]);
    $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
    
    // If no active subscription, create a free one
    if (!$subscription) {
        $freePlanStmt = $db->prepare("SELECT * FROM subscription_plans WHERE plan_code = 'free' AND is_active = 1");
        $freePlanStmt->execute();
        $freePlan = $freePlanStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($freePlan) {
            $db->prepare("
                INSERT INTO subscriptions (user_id, plan_id, status, created_at, updated_at) 
                VALUES (?, ?, 'active', NOW(), NOW())
            ")->execute([$userId, $freePlan["id"]]);
            
            $subscription = [
                "plan_code" => "free",
                "plan_name" => $freePlan["name"],
                "status" => "active",
                "price" => 0,
                "features" => $freePlan["features"]
            ];
        }
    }
    
    // Parse features
    $features = [];
    if ($subscription && $subscription["features"]) {
        $features = json_decode($subscription["features"], true) ?: [];
    }
    
    // Determine if user is pro
    $isProUser = $subscription && in_array($subscription["plan_code"], ["pro", "premium"]) && $subscription["status"] === "active";
    
    echo json_encode([
        "status" => "success",
        "profile" => [
            "first_name" => $user["first_name"] ?? "User",
            "last_name" => $user["last_name"] ?? "",
            "phone" => $user["phone"] ?? "",
            "address" => $user["address"] ?? "",
            "city" => $user["city"] ?? "",
            "state" => $user["state"] ?? "",
            "postal_code" => $user["postal_code"] ?? "",
            "country" => $user["country"] ?? "India"
        ],
        "subscription" => [
            "plan_code" => $subscription["plan_code"] ?? "free",
            "plan_name" => $subscription["plan_name"] ?? "Free Plan",
            "subscription_status" => $subscription["status"] ?? "active",
            "is_active" => ($subscription["status"] ?? "active") === "active" ? 1 : 0,
            "price" => (float)($subscription["price"] ?? 0),
            "features" => $features ?: ["Access to free tutorials"]
        ],
        "stats" => [
            "purchased_tutorials" => 2,
            "completed_tutorials" => 3,
            "learning_hours" => 8,
            "practice_uploads" => 0,
            "is_pro_user" => $isProUser
        ],
        "user_email" => $user["email"],
        "user_id" => $user["id"],
        "debug" => [
            "timestamp" => date("Y-m-d H:i:s"),
            "method" => $_SERVER["REQUEST_METHOD"],
            "subscription_source" => "database_active_only",
            "is_pro_calculated" => $isProUser
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>