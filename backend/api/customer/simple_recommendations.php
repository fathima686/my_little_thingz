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

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get request parameters
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $limit = isset($_GET['limit']) ? max(1, min(20, (int)$_GET['limit'])) : 8;

    if ($userId <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Valid user_id is required'
        ]);
        exit;
    }

    // Get user's recent orders to understand preferences
    $stmt = $db->prepare("
        SELECT DISTINCT oi.artwork_id, oi.quantity, oi.price, a.category_id, a.title
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN artworks a ON oi.artwork_id = a.id
        WHERE o.user_id = :user_id
        AND o.status IN ('delivered', 'shipped', 'processing')
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $userOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get popular items in similar categories
    $categoryIds = array_unique(array_column($userOrders, 'category_id'));
    $recommendations = [];

    if (!empty($categoryIds)) {
        $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
        
        $stmt = $db->prepare("
            SELECT 
                a.id, a.title, a.description, a.price, a.image_url, 
                a.category_id, a.availability, a.created_at,
                c.name as category_name,
                COUNT(oi.id) as order_count
            FROM artworks a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN order_items oi ON a.id = oi.artwork_id
            WHERE a.category_id IN ($placeholders)
            AND a.status = 'active'
            AND a.id NOT IN (
                SELECT DISTINCT artwork_id 
                FROM order_items oi2 
                JOIN orders o2 ON oi2.order_id = o2.id 
                WHERE o2.user_id = ?
            )
            GROUP BY a.id
            ORDER BY order_count DESC, a.created_at DESC
            LIMIT ?
        ");
        
        $params = array_merge($categoryIds, [$userId, $limit]);
        $stmt->execute($params);
        
        $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // If no category-based recommendations, get popular items
    if (empty($recommendations)) {
        $stmt = $db->prepare("
            SELECT 
                a.id, a.title, a.description, a.price, a.image_url, 
                a.category_id, a.availability, a.created_at,
                c.name as category_name,
                COUNT(oi.id) as order_count
            FROM artworks a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN order_items oi ON a.id = oi.artwork_id
            WHERE a.status = 'active'
            GROUP BY a.id
            ORDER BY order_count DESC, a.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Format recommendations
    $formattedRecommendations = [];
    foreach ($recommendations as $rec) {
        $formattedRecommendations[] = [
            'id' => (int)$rec['id'],
            'title' => $rec['title'],
            'description' => $rec['description'] ?: 'Personalized recommendation based on your purchase history',
            'price' => (float)$rec['price'],
            'image_url' => $rec['image_url'] ?: '/images/placeholder.jpg',
            'category_id' => (int)$rec['category_id'],
            'category_name' => $rec['category_name'],
            'availability' => $rec['availability'],
            'created_at' => $rec['created_at'],
            'recommendationScore' => 0.8, // High confidence for purchase-based recommendations
            'preference_score' => 0.8,
            'confidence' => 0.8,
            'algorithm' => 'Purchase History Analysis'
        ];
    }

    echo json_encode([
        'status' => 'success',
        'recommendations' => $formattedRecommendations,
        'count' => count($formattedRecommendations),
        'user_id' => $userId,
        'generated_at' => date('Y-m-d H:i:s'),
        'method' => 'purchase_history_analysis'
    ]);

} catch (Exception $e) {
    error_log("Simple Recommendations Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to generate recommendations',
        'debug' => $e->getMessage()
    ]);
}
?>
