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

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get artwork ID
    $artworkId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($artworkId <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Valid artwork ID is required'
        ]);
        exit;
    }

    // Get artwork details
    $stmt = $db->prepare("
        SELECT 
            a.id,
            a.title,
            a.description,
            a.price,
            a.image_url,
            a.category_id,
            a.availability,
            a.created_at,
            a.offer_price,
            a.offer_percent,
            a.offer_starts_at,
            a.offer_ends_at,
            a.force_offer_badge,
            c.name as category_name
        FROM artworks a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.id = :artwork_id AND a.status = 'active'
    ");
    $stmt->bindValue(':artwork_id', $artworkId, PDO::PARAM_INT);
    $stmt->execute();

    $artwork = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$artwork) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Artwork not found'
        ]);
        exit;
    }

    // Calculate effective price
    $effectivePrice = $artwork['price'];
    $hasOffer = false;
    
    if ($artwork['offer_price'] && 
        $artwork['offer_starts_at'] <= date('Y-m-d H:i:s') && 
        $artwork['offer_ends_at'] >= date('Y-m-d H:i:s')) {
        $effectivePrice = $artwork['offer_price'];
        $hasOffer = true;
    }

    // Format response
    $response = [
        'status' => 'success',
        'artwork' => [
            'id' => (int)$artwork['id'],
            'title' => $artwork['title'],
            'description' => $artwork['description'],
            'price' => (float)$artwork['price'],
            'effective_price' => (float)$effectivePrice,
            'image_url' => $artwork['image_url'],
            'category_id' => (int)$artwork['category_id'],
            'category_name' => $artwork['category_name'],
            'availability' => $artwork['availability'],
            'created_at' => $artwork['created_at'],
            'has_offer' => $hasOffer,
            'offer_price' => $artwork['offer_price'] ? (float)$artwork['offer_price'] : null,
            'offer_percent' => $artwork['offer_percent'] ? (float)$artwork['offer_percent'] : null,
            'force_offer_badge' => (bool)$artwork['force_offer_badge']
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Artwork Details Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch artwork details',
        'debug' => $e->getMessage()
    ]);
}








