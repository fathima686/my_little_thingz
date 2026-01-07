<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email, X-User-ID");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

try {
    require_once "../../config/database.php";
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents("php://input"), true);
    $userEmail = $_SERVER["HTTP_X_TUTORIAL_EMAIL"] ?? $input["email"] ?? null;
    $userId = $_SERVER["HTTP_X_USER_ID"] ?? $input["user_id"] ?? null;
    $newPlanCode = $input["plan_code"] ?? null;
    
    // Get user ID from email if not provided
    if (!$userId && $userEmail) {
        $userStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $userStmt->execute([$userEmail]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        $userId = $user ? $user["id"] : null;
    }
    
    if (!$userId || !$newPlanCode) {
        echo json_encode(["status" => "error", "message" => "User ID and plan_code are required"]);
        exit;
    }
    
    // Get plan by plan_code
    $planStmt = $db->prepare("SELECT * FROM subscription_plans WHERE plan_code = ? AND is_active = 1");
    $planStmt->execute([$newPlanCode]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        echo json_encode(["status" => "error", "message" => "Invalid plan code: $newPlanCode"]);
        exit;
    }
    
    // Check if user already has an active subscription with this plan
    $activeStmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name 
        FROM subscriptions s 
        LEFT JOIN subscription_plans sp ON s.plan_id = sp.id 
        WHERE s.user_id = ? AND s.plan_id = ? AND s.status = 'active'
    ");
    $activeStmt->execute([$userId, $plan["id"]]);
    $existingActive = $activeStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingActive) {
        echo json_encode([
            "status" => "error",
            "message" => "You already have an active " . $plan["name"] . " subscription"
        ]);
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Set all existing subscriptions to cancelled
        $cancelStmt = $db->prepare("UPDATE subscriptions SET status = 'cancelled', updated_at = NOW() WHERE user_id = ?");
        $cancelStmt->execute([$userId]);
        
        // Create new active subscription
        $createStmt = $db->prepare("
            INSERT INTO subscriptions (user_id, plan_id, status, created_at, updated_at) 
            VALUES (?, ?, 'active', NOW(), NOW())
        ");
        $createStmt->execute([$userId, $plan["id"]]);
        
        $db->commit();
        
        echo json_encode([
            "status" => "success",
            "message" => "Successfully upgraded to " . $plan["name"],
            "subscription" => [
                "plan_code" => $plan["plan_code"],
                "plan_name" => $plan["name"],
                "status" => "active",
                "price" => (float)$plan["price"]
            ]
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>