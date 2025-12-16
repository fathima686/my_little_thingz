<?php
/**
 * Get Product Ratings
 * Retrieves ratings and feedback for a specific artwork
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get artwork ID from query parameters
    $artwork_id = $_GET['artwork_id'] ?? null;
    $limit = (int)($_GET['limit'] ?? 10);
    $offset = (int)($_GET['offset'] ?? 0);

    if (!$artwork_id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Artwork ID is required'
        ]);
        exit;
    }

    // Get artwork details with rating summary
    $artworkQuery = "SELECT 
                      a.id,
                      a.title,
                      a.average_rating,
                      a.total_ratings,
                      a.rating_updated_at
                    FROM artworks a
                    WHERE a.id = ? AND a.status = 'active'";
    
    $artworkStmt = $db->prepare($artworkQuery);
    $artworkStmt->execute([$artwork_id]);
    $artwork = $artworkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$artwork) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Artwork not found'
        ]);
        exit;
    }

    // Get individual ratings
    $ratingsQuery = "SELECT 
                      pr.id,
                      pr.rating,
                      pr.feedback,
                      pr.is_anonymous,
                      pr.created_at,
                      CASE 
                        WHEN pr.is_anonymous = 1 THEN 'Anonymous'
                        ELSE CONCAT(LEFT(u.first_name, 1), '. ', u.last_name)
                      END as reviewer_name
                    FROM product_ratings pr
                    LEFT JOIN users u ON pr.user_id = u.id
                    WHERE pr.artwork_id = ? AND pr.status = 'approved'
                    ORDER BY pr.created_at DESC
                    LIMIT ? OFFSET ?";
    
    $ratingsStmt = $db->prepare($ratingsQuery);
    $ratingsStmt->execute([$artwork_id, $limit, $offset]);
    $ratings = $ratingsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM product_ratings WHERE artwork_id = ? AND status = 'approved'";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute([$artwork_id]);
    $total_count = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Format ratings
    foreach ($ratings as &$rating) {
        $rating['created_at'] = date('M d, Y', strtotime($rating['created_at']));
    }

    echo json_encode([
        'status' => 'success',
        'artwork' => [
            'id' => $artwork['id'],
            'title' => $artwork['title'],
            'average_rating' => $artwork['average_rating'],
            'total_ratings' => (int)$artwork['total_ratings'],
            'rating_updated_at' => $artwork['rating_updated_at']
        ],
        'ratings' => $ratings,
        'pagination' => [
            'total' => (int)$total_count,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total_count
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error retrieving ratings: ' . $e->getMessage()
    ]);
}
?>





