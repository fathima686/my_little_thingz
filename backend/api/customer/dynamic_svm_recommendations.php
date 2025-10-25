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

// Python ML Service Configuration
define('PYTHON_ML_SERVICE_URL', 'http://localhost:5001/api/ml/dynamic-svm');

try {
    $database = new Database();
    $db = $database->getConnection();

    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        handleGetRecommendations($db);
    } elseif ($method === 'POST') {
        handlePostRequest($db);
    } else {
        throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    error_log("Dynamic SVM Recommendations Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to process request',
        'debug' => $e->getMessage()
    ]);
}

function handleGetRecommendations($db) {
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $limit = isset($_GET['limit']) ? max(1, min(20, (int)$_GET['limit'])) : 8;
    $minConfidence = isset($_GET['min_confidence']) ? (float)$_GET['min_confidence'] : 0.3;
    
    if (!$userId) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User ID is required'
        ]);
        return;
    }
    
    // Check if user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE id = :user_id");
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found'
        ]);
        return;
    }
    
    // Call Python ML Service
    $recommendations = callPythonMLService('recommendations', [
        'user_id' => $userId,
        'limit' => $limit
    ]);
    
    if ($recommendations['success']) {
        // Enrich recommendations with artwork details
        $enrichedRecommendations = enrichRecommendations($db, $recommendations['recommendations']);
        
        echo json_encode([
            'status' => 'success',
            'recommendations' => $enrichedRecommendations,
            'count' => count($enrichedRecommendations),
            'user_id' => $userId,
            'is_new_user' => $recommendations['is_new_user'],
            'model_version' => $recommendations['model_version'],
            'generated_at' => $recommendations['generated_at']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => $recommendations['error'] ?? 'Failed to get recommendations'
        ]);
    }
}

function handlePostRequest($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'update_behavior':
            handleUpdateBehavior($db, $input);
            break;
        case 'predict':
            handlePredict($db, $input);
            break;
        case 'retrain':
            handleRetrain($db, $input);
            break;
        case 'model_info':
            handleModelInfo($db);
            break;
        default:
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action'
            ]);
    }
}

function handleUpdateBehavior($db, $input) {
    $userId = $input['user_id'] ?? 0;
    $productId = $input['product_id'] ?? 0;
    $behaviorType = $input['behavior_type'] ?? '';
    $additionalData = $input['additional_data'] ?? [];
    
    if (!$userId || !$productId || !$behaviorType) {
        echo json_encode([
            'status' => 'error',
            'message' => 'user_id, product_id, and behavior_type are required'
        ]);
        return;
    }
    
    // Call Python ML Service
    $result = callPythonMLService('update-behavior', [
        'user_id' => $userId,
        'product_id' => $productId,
        'behavior_type' => $behaviorType,
        'additional_data' => $additionalData
    ]);
    
    echo json_encode($result);
}

function handlePredict($db, $input) {
    $userId = $input['user_id'] ?? 0;
    $productId = $input['product_id'] ?? 0;
    
    if (!$userId || !$productId) {
        echo json_encode([
            'status' => 'error',
            'message' => 'user_id and product_id are required'
        ]);
        return;
    }
    
    // Call Python ML Service
    $result = callPythonMLService('predict', [
        'user_id' => $userId,
        'product_id' => $productId
    ]);
    
    echo json_encode($result);
}

function handleRetrain($db, $input) {
    $force = $input['force'] ?? false;
    
    // Call Python ML Service
    $result = callPythonMLService('retrain', [
        'force' => $force
    ]);
    
    echo json_encode($result);
}

function handleModelInfo($db) {
    // Call Python ML Service
    $result = callPythonMLService('model-info', []);
    
    echo json_encode($result);
}

function callPythonMLService($endpoint, $data) {
    $url = PYTHON_ML_SERVICE_URL . '/' . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($data))
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => 'Python ML Service connection failed: ' . $error
        ];
    }
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => 'Python ML Service returned HTTP ' . $httpCode
        ];
    }
    
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error' => 'Invalid JSON response from Python ML Service'
        ];
    }
    
    return $result;
}

function enrichRecommendations($db, $recommendations) {
    if (empty($recommendations)) {
        return [];
    }
    
    $productIds = array_column($recommendations, 'product_id');
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    
    // Get artwork details
    $stmt = $db->prepare("
        SELECT 
            a.id, a.title, a.description, a.price, a.image_url, 
            a.category_id, a.availability, a.created_at,
            c.name as category_name,
            CASE 
                WHEN a.offer_price IS NOT NULL AND a.offer_price > 0 THEN a.offer_price
                ELSE a.price 
            END as effective_price,
            CASE 
                WHEN a.offer_price IS NOT NULL AND a.offer_price > 0 THEN 
                    ROUND(((a.price - a.offer_price) / a.price) * 100, 2)
                ELSE 0 
            END as discount_percentage,
            CASE 
                WHEN a.offer_price IS NOT NULL AND a.offer_price > 0 THEN 1
                ELSE 0 
            END as has_offer
        FROM artworks a 
        LEFT JOIN categories c ON a.category_id = c.id 
        WHERE a.id IN ($placeholders) AND a.status = 'active'
    ");
    $stmt->execute($productIds);
    $artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create lookup array
    $artworkLookup = [];
    foreach ($artworks as $artwork) {
        $artworkLookup[$artwork['id']] = $artwork;
    }
    
    // Enrich recommendations
    $enrichedRecommendations = [];
    foreach ($recommendations as $rec) {
        $artworkId = $rec['product_id'];
        if (isset($artworkLookup[$artworkId])) {
            $artwork = $artworkLookup[$artworkId];
            $enrichedRecommendations[] = [
                'artwork_id' => $artwork['id'],
                'title' => $artwork['title'],
                'description' => $artwork['description'],
                'price' => (float)$artwork['price'],
                'effective_price' => (float)$artwork['effective_price'],
                'image_url' => $artwork['image_url'],
                'category_id' => (int)$artwork['category_id'],
                'category_name' => $artwork['category_name'],
                'availability' => $artwork['availability'],
                'discount_percentage' => (float)$artwork['discount_percentage'],
                'has_offer' => (bool)$artwork['has_offer'],
                'confidence' => (float)$rec['confidence'],
                'probability_like' => (float)$rec['probability_like'],
                'model_version' => $rec['model_version'],
                'recommendation_type' => $rec['recommendation_type'] ?? 'personalized'
            ];
        }
    }
    
    return $enrichedRecommendations;
}
?>






