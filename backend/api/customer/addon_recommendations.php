<?php
header('Content-Type: application/json');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost:5173', 'http://127.0.0.1:5173'];
if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

require_once '../../config/database.php';
require_once '../../services/DecisionTreeAddonRecommender.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $addonRecommender = new DecisionTreeAddonRecommender($db);

    // Get request parameters
    $artworkId = isset($_GET['artwork_id']) ? (int)$_GET['artwork_id'] : 0;
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    $price = isset($_GET['price']) ? (float)$_GET['price'] : null;
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    $occasion = isset($_GET['occasion']) ? $_GET['occasion'] : null;

    if ($artworkId <= 0 && !$price) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Either artwork_id or price is required'
        ]);
        exit;
    }

    // Get add-on recommendations
    if ($artworkId > 0) {
        // Get recommendations for specific artwork
        $result = $addonRecommender->getGiftAddonRecommendations($artworkId, $userId);
        
        if (isset($result['error'])) {
            echo json_encode([
                'status' => 'error',
                'message' => $result['error']
            ]);
            exit;
        }
    } else {
        // Get recommendations based on price and other parameters
        $giftData = [
            'price' => $price,
            'category_name' => $category,
            'occasion' => $occasion
        ];
        
        if ($userId) {
            $giftData['customer_preferences'] = $addonRecommender->getCustomerPreferences($userId);
        }
        
        $result = $addonRecommender->getAddonRecommendations($giftData);
    }

    // Format response
    $response = [
        'status' => 'success',
        'addon_recommendations' => $result['recommendations'],
        'overall_confidence' => $result['overall_confidence'],
        'rule_count' => $result['rule_count'],
        'decision_path' => $result['decision_path'],
        'generated_at' => date('Y-m-d H:i:s')
    ];

    // Add artwork info if available
    if ($artworkId > 0) {
        $stmt = $db->prepare("
            SELECT a.id, a.title, a.price, a.image_url, c.name as category_name
            FROM artworks a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.id = :artwork_id
        ");
        $stmt->bindValue(':artwork_id', $artworkId, PDO::PARAM_INT);
        $stmt->execute();
        $artwork = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($artwork) {
            $response['artwork'] = [
                'id' => (int)$artwork['id'],
                'title' => $artwork['title'],
                'price' => (float)$artwork['price'],
                'image_url' => $artwork['image_url'],
                'category_name' => $artwork['category_name']
            ];
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Add-on Recommendations Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to generate add-on recommendations',
        'debug' => $e->getMessage()
    ]);
}

