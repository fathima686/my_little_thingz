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
require_once '../../services/PurchaseHistoryAnalyzer.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $purchaseAnalyzer = new PurchaseHistoryAnalyzer($db);

    // Get request parameters
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $limit = isset($_GET['limit']) ? max(1, min(20, (int)$_GET['limit'])) : 8;
    $analysis = isset($_GET['analysis']) ? filter_var($_GET['analysis'], FILTER_VALIDATE_BOOLEAN) : false;

    if ($userId <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Valid user_id is required'
        ]);
        exit;
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
        exit;
    }

    // Get purchase history-based recommendations
    $recommendations = $purchaseAnalyzer->getHistoryBasedRecommendations($userId, $limit);

    // Add additional artwork information
    $recommendations = enrichRecommendations($db, $recommendations);

    $response = [
        'status' => 'success',
        'recommendations' => $recommendations,
        'count' => count($recommendations),
        'user_id' => $userId,
        'generated_at' => date('Y-m-d H:i:s')
    ];

    // Add analysis if requested
    if ($analysis) {
        $purchaseAnalysis = $purchaseAnalyzer->analyzePurchaseHistory($userId);
        $response['analysis'] = [
            'total_purchases' => $purchaseAnalysis['total_purchases'],
            'categories_purchased' => array_keys($purchaseAnalysis['categories_purchased']),
            'occasions_detected' => array_keys($purchaseAnalysis['occasions_detected']),
            'price_range' => $purchaseAnalysis['price_preferences']['price_range'],
            'most_active_season' => $purchaseAnalysis['seasonal_patterns']['most_active_season']
        ];
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Purchase History Recommendations Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to generate recommendations',
        'debug' => $e->getMessage()
    ]);
}

/**
 * Enrich recommendations with artwork details
 */
function enrichRecommendations($db, $recommendations)
{
    if (empty($recommendations)) {
        return [];
    }

    $artworkIds = array_column($recommendations, 'artwork_id');
    $placeholders = str_repeat('?,', count($artworkIds) - 1) . '?';

    $stmt = $db->prepare("
        SELECT 
            a.id, a.title, a.description, a.price, a.image_url, 
            a.category_id, a.availability, a.created_at,
            a.offer_price, a.offer_percent, a.offer_starts_at, a.offer_ends_at,
            a.force_offer_badge,
            c.name as category_name
        FROM artworks a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.id IN ($placeholders)
        AND a.status = 'active'
    ");
    $stmt->execute($artworkIds);

    $artworkData = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $artworkData[$row['id']] = $row;
    }

    // Merge recommendations with artwork data
    $enrichedRecommendations = [];
    foreach ($recommendations as $rec) {
        $artworkId = $rec['artwork_id'];
        if (isset($artworkData[$artworkId])) {
            $artwork = $artworkData[$artworkId];
            
            // Calculate effective price
            $effectivePrice = $artwork['price'];
            if ($artwork['offer_price'] && 
                $artwork['offer_starts_at'] <= date('Y-m-d H:i:s') && 
                $artwork['offer_ends_at'] >= date('Y-m-d H:i:s')) {
                $effectivePrice = $artwork['offer_price'];
            }

            $enrichedRecommendations[] = array_merge($rec, [
                'title' => $artwork['title'],
                'description' => $artwork['description'],
                'price' => (float)$artwork['price'],
                'effective_price' => (float)$effectivePrice,
                'image_url' => $artwork['image_url'],
                'category_id' => (int)$artwork['category_id'],
                'category_name' => $artwork['category_name'],
                'availability' => $artwork['availability'],
                'created_at' => $artwork['created_at'],
                'has_offer' => !empty($artwork['offer_price']),
                'offer_price' => $artwork['offer_price'] ? (float)$artwork['offer_price'] : null,
                'offer_percent' => $artwork['offer_percent'] ? (float)$artwork['offer_percent'] : null,
                'force_offer_badge' => (bool)$artwork['force_offer_badge']
            ]);
        }
    }

    return $enrichedRecommendations;
}
















