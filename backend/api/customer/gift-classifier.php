<?php
/**
 * Gift Category Classifier API
 * Predicts gift categories when customers browse or create gifts
 * GET: Predict category for one or more gift names
 * POST: Train classifier from existing database
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
$mysqli->set_charset('utf8mb4');

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// Include the classifier service
require_once __DIR__ . '/../../services/GiftCategoryClassifier.php';
$classifier = new GiftCategoryClassifier($mysqli);

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Predict categories for gift names
        // Usage: ?gift_name=Birthday Card&confidence_threshold=0.75
        // OR: ?gift_names[]=Birthday Card&gift_names[]=Custom Hamper&confidence_threshold=0.75
        
        $giftNames = [];
        $confidenceThreshold = 0.75;
        
        if (isset($_GET['gift_name'])) {
            $giftNames[] = $_GET['gift_name'];
        } elseif (isset($_GET['gift_names'])) {
            $giftNames = (array)$_GET['gift_names'];
        }
        
        if (isset($_GET['confidence_threshold'])) {
            $confidenceThreshold = max(0, min(1, (float)$_GET['confidence_threshold']));
        }
        
        if (empty($giftNames)) {
            http_response_code(422);
            echo json_encode([
                "status" => "error",
                "message" => "gift_name or gift_names[] required",
                "example" => "?gift_name=Wedding Hamper&confidence_threshold=0.75"
            ]);
            exit;
        }
        
        $predictions = [];
        foreach ($giftNames as $name) {
            $prediction = $classifier->classifyGift($name, $confidenceThreshold);
            $prediction['input_name'] = $name;
            $predictions[] = $prediction;
        }
        
        echo json_encode([
            "status" => "success",
            "predictions" => $predictions,
            "count" => count($predictions)
        ]);
        
    } elseif ($method === 'POST') {
        // Train classifier from existing database
        // This analyzes all gifts and their categories to improve accuracy
        
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $body['action'] ?? 'train';
        
        if ($action === 'train') {
            $result = $classifier->trainFromDatabase($mysqli);
            http_response_code($result['status'] === 'success' ? 200 : 500);
            echo json_encode($result);
            
        } elseif ($action === 'get_categories') {
            $categories = $classifier->getCategories($mysqli);
            echo json_encode([
                "status" => "success",
                "categories" => $categories,
                "count" => count($categories)
            ]);
            
        } else {
            http_response_code(422);
            echo json_encode([
                "status" => "error",
                "message" => "Unknown action",
                "available_actions" => ["train", "get_categories"]
            ]);
        }
        
    } else {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}