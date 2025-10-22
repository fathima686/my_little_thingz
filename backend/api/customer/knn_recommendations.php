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
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

require_once '../../config/database.php';
require_once '../../services/KNNRecommendationEngine.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $knnEngine = new KNNRecommendationEngine($db);
    
    // Get request parameters
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $limit = isset($_GET['limit']) ? max(1, min(20, (int)$_GET['limit'])) : 8;
    $k = isset($_GET['k']) ? max(1, min(20, (int)$_GET['k'])) : 5;
    $similarityThreshold = isset($_GET['similarity_threshold']) ? 
        max(0.0, min(1.0, (float)$_GET['similarity_threshold'])) : 0.3;
    $type = isset($_GET['type']) ? $_GET['type'] : 'auto'; // 'product', 'user', 'auto'
    
    // Configure KNN engine
    $knnEngine->setK($k);
    $knnEngine->setSimilarityThreshold($similarityThreshold);
    
    $recommendations = [];
    $algorithm = '';
    
    if ($productId > 0 && $type !== 'user') {
        // Product-based recommendations (find similar products)
        $similarProducts = $knnEngine->findSimilarProducts($productId, $limit);
        $recommendations = array_map(function($item) {
            return [
                'id' => $item['product']['id'],
                'title' => $item['product']['title'],
                'description' => $item['product']['description'],
                'price' => $item['product']['price'],
                'effective_price' => $item['product']['effective_price'],
                'category_id' => $item['product']['category_id'],
                'category_name' => $item['product']['category_name'],
                'image_url' => $item['product']['image_url'],
                'availability' => $item['product']['availability'],
                'created_at' => $item['product']['created_at'],
                'similarity_score' => $item['similarity'],
                'recommendation_type' => 'similar_products',
                'algorithm' => 'KNN'
            ];
        }, $similarProducts);
        $algorithm = 'KNN Product Similarity';
        
    } elseif ($userId > 0 && $type !== 'product') {
        // User-based recommendations (collaborative filtering)
        $userRecommendations = $knnEngine->findUserBasedRecommendations($userId, $limit);
        $recommendations = array_map(function($product) {
            return [
                'id' => $product['id'],
                'title' => $product['title'],
                'description' => $product['description'],
                'price' => $product['price'],
                'effective_price' => $product['effective_price'],
                'category_id' => $product['category_id'],
                'category_name' => $product['category_name'],
                'image_url' => $product['image_url'],
                'availability' => $product['availability'],
                'created_at' => $product['created_at'],
                'similarity_score' => $product['similarity_score'] ?? 0,
                'recommendation_type' => $product['recommendation_type'] ?? 'collaborative',
                'algorithm' => 'KNN'
            ];
        }, $userRecommendations);
        $algorithm = 'KNN Collaborative Filtering';
        
    } else {
        // Auto mode - try both approaches
        if ($productId > 0) {
            $similarProducts = $knnEngine->findSimilarProducts($productId, $limit);
            $recommendations = array_map(function($item) {
                return [
                    'id' => $item['product']['id'],
                    'title' => $item['product']['title'],
                    'description' => $item['product']['description'],
                    'price' => $item['product']['price'],
                    'effective_price' => $item['product']['effective_price'],
                    'category_id' => $item['product']['category_id'],
                    'category_name' => $item['product']['category_name'],
                    'image_url' => $item['product']['image_url'],
                    'availability' => $item['product']['availability'],
                    'created_at' => $item['product']['created_at'],
                    'similarity_score' => $item['similarity'],
                    'recommendation_type' => 'similar_products',
                    'algorithm' => 'KNN'
                ];
            }, $similarProducts);
            $algorithm = 'KNN Product Similarity';
        } elseif ($userId > 0) {
            $userRecommendations = $knnEngine->findUserBasedRecommendations($userId, $limit);
            $recommendations = array_map(function($product) {
                return [
                    'id' => $product['id'],
                    'title' => $product['title'],
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'effective_price' => $product['effective_price'],
                    'category_id' => $product['category_id'],
                    'category_name' => $product['category_name'],
                    'image_url' => $product['image_url'],
                    'availability' => $product['availability'],
                    'created_at' => $product['created_at'],
                    'similarity_score' => $product['similarity_score'] ?? 0,
                    'recommendation_type' => $product['recommendation_type'] ?? 'collaborative',
                    'algorithm' => 'KNN'
                ];
            }, $userRecommendations);
            $algorithm = 'KNN Collaborative Filtering';
        } else {
            throw new Exception('Either product_id or user_id is required');
        }
    }
    
    // Add offer information if available
    $hasOfferCols = false;
    try {
        $col = $db->query("SHOW COLUMNS FROM artworks LIKE 'offer_price'");
        if ($col && $col->rowCount() > 0) { 
            $hasOfferCols = true; 
        }
    } catch (Throwable $e) { 
        $hasOfferCols = false; 
    }
    
    if ($hasOfferCols) {
        $productIds = array_column($recommendations, 'id');
        if (!empty($productIds)) {
            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
            $stmt = $db->prepare("
                SELECT id, offer_price, offer_percent, offer_starts_at, offer_ends_at, force_offer_badge
                FROM artworks 
                WHERE id IN ($placeholders)
            ");
            $stmt->execute($productIds);
            
            $offers = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $offers[$row['id']] = $row;
            }
            
            // Add offer info to recommendations
            foreach ($recommendations as &$rec) {
                if (isset($offers[$rec['id']])) {
                    $offer = $offers[$rec['id']];
                    $rec['has_offer'] = !empty($offer['offer_price']);
                    $rec['offer_price'] = $offer['offer_price'] ? (float)$offer['offer_price'] : null;
                    $rec['offer_percent'] = $offer['offer_percent'] ? (float)$offer['offer_percent'] : null;
                    $rec['offer_starts_at'] = $offer['offer_starts_at'];
                    $rec['offer_ends_at'] = $offer['offer_ends_at'];
                    $rec['force_offer_badge'] = $offer['force_offer_badge'] ?? false;
                } else {
                    $rec['has_offer'] = false;
                    $rec['offer_price'] = null;
                    $rec['offer_percent'] = null;
                    $rec['offer_starts_at'] = null;
                    $rec['offer_ends_at'] = null;
                    $rec['force_offer_badge'] = false;
                }
            }
        }
    } else {
        // Add default offer fields
        foreach ($recommendations as &$rec) {
            $rec['has_offer'] = false;
            $rec['offer_price'] = null;
            $rec['offer_percent'] = null;
            $rec['offer_starts_at'] = null;
            $rec['offer_ends_at'] = null;
            $rec['force_offer_badge'] = false;
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'algorithm' => $algorithm,
        'parameters' => [
            'k' => $k,
            'similarity_threshold' => $similarityThreshold,
            'type' => $type,
            'product_id' => $productId,
            'user_id' => $userId
        ],
        'recommendations' => $recommendations,
        'count' => count($recommendations),
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}



