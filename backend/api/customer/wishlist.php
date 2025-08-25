<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user ID from request (robust across servers and tools)
    $user_id = null;

    // 1) Standard PHP server var set by web servers for custom headers
    if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') {
        $user_id = $_SERVER['HTTP_X_USER_ID'];
    }

    // 2) Some environments use different prefixes (e.g., REDIRECT_)
    if (!$user_id) {
        $altKeys = ['REDIRECT_HTTP_X_USER_ID', 'X_USER_ID', 'HTTP_X_USERID', 'HTTP_X_USERID'];
        foreach ($altKeys as $k) {
            if (isset($_SERVER[$k]) && $_SERVER[$k] !== '') {
                $user_id = $_SERVER[$k];
                break;
            }
        }
    }

    // 3) Generic header retrieval (Apache-only typically)
    if (!$user_id && function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $key => $value) {
            if (strtolower(trim($key)) === 'x-user-id' && $value !== '') {
                $user_id = $value;
                break;
            }
        }
    }

    // 4) Fallback to query param for manual browser testing
    if (!$user_id && isset($_GET['user_id']) && $_GET['user_id'] !== '') {
        $user_id = $_GET['user_id'];
    }

    if (!$user_id) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User ID required'
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch user's wishlist
        $query = "SELECT 
                    w.id,
                    w.artwork_id,
                    w.added_at,
                    a.title,
                    a.description,
                    a.price,
                    a.image_url,
                    a.availability,
                    CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS artist_name
                  FROM wishlist w
                  JOIN artworks a ON w.artwork_id = a.id
                  LEFT JOIN users u ON a.artist_id = u.id
                  WHERE w.user_id = ? AND a.status = 'active'
                  ORDER BY w.added_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the data
        foreach ($wishlist as &$item) {
            $item['price'] = number_format($item['price'], 2);
            $item['added_at'] = date('Y-m-d H:i:s', strtotime($item['added_at']));
        }

        echo json_encode([
            'status' => 'success',
            'wishlist' => $wishlist
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Add item to wishlist
        $input = json_decode(file_get_contents('php://input'), true);
        $artwork_id = isset($input['artwork_id']) ? $input['artwork_id'] : null;

        if (!$artwork_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Artwork ID required'
            ]);
            exit;
        }

        // Check if item already exists in wishlist
        $check_query = "SELECT id FROM wishlist WHERE user_id = ? AND artwork_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$user_id, $artwork_id]);
        
        if ($check_stmt->fetch()) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Item already in wishlist'
            ]);
            exit;
        }

        // Add to wishlist
        $insert_query = "INSERT INTO wishlist (user_id, artwork_id, added_at) VALUES (?, ?, NOW())";
        $insert_stmt = $db->prepare($insert_query);
        
        if ($insert_stmt->execute([$user_id, $artwork_id])) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Item added to wishlist'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to add item to wishlist'
            ]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Remove item from wishlist
        $input = json_decode(file_get_contents('php://input'), true);
        $artwork_id = isset($input['artwork_id']) ? $input['artwork_id'] : null;

        if (!$artwork_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Artwork ID required'
            ]);
            exit;
        }

        $delete_query = "DELETE FROM wishlist WHERE user_id = ? AND artwork_id = ?";
        $delete_stmt = $db->prepare($delete_query);
        
        if ($delete_stmt->execute([$user_id, $artwork_id])) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Item removed from wishlist'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to remove item from wishlist'
            ]);
        }

    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Method not allowed'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>