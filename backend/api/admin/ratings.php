<?php
/**
 * Admin Ratings Management
 * View and manage all product ratings and feedback
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user ID from headers
    $user_id = null;
    if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') {
        $user_id = $_SERVER['HTTP_X_USER_ID'];
    }
    if (!$user_id && function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $key => $value) {
            if (strtolower(trim($key)) === 'x-user-id' && $value !== '') {
                $user_id = $value;
                break;
            }
        }
    }

    if (!$user_id) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'User ID required'
        ]);
        exit;
    }

    // Verify admin access
    $adminQuery = "SELECT role FROM users WHERE id = ? AND role = 'admin'";
    $adminStmt = $db->prepare($adminQuery);
    $adminStmt->execute([$user_id]);
    
    if (!$adminStmt->fetch()) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Admin access required'
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get filter parameters
        $status = $_GET['status'] ?? 'all';
        $rating = $_GET['rating'] ?? null;
        $artwork_id = $_GET['artwork_id'] ?? null;
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);

        // Build query
        $whereConditions = [];
        $params = [];

        if ($status !== 'all') {
            $whereConditions[] = "pr.status = ?";
            $params[] = $status;
        }

        if ($rating) {
            $whereConditions[] = "pr.rating = ?";
            $params[] = (int)$rating;
        }

        if ($artwork_id) {
            $whereConditions[] = "pr.artwork_id = ?";
            $params[] = $artwork_id;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get ratings
        $query = "SELECT 
                    pr.id,
                    pr.rating,
                    pr.feedback,
                    pr.is_anonymous,
                    pr.status,
                    pr.admin_notes,
                    pr.created_at,
                    pr.updated_at,
                    o.order_number,
                    o.delivered_at,
                    a.id as artwork_id,
                    a.title as artwork_title,
                    a.image_url,
                    u.first_name,
                    u.last_name,
                    u.email
                  FROM product_ratings pr
                  JOIN orders o ON pr.order_id = o.id
                  JOIN artworks a ON pr.artwork_id = a.id
                  JOIN users u ON pr.user_id = u.id
                  $whereClause
                  ORDER BY pr.created_at DESC
                  LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $countQuery = "SELECT COUNT(*) as total 
                       FROM product_ratings pr
                       JOIN orders o ON pr.order_id = o.id
                       JOIN artworks a ON pr.artwork_id = a.id
                       JOIN users u ON pr.user_id = u.id
                       $whereClause";

        $countParams = array_slice($params, 0, -2); // Remove limit and offset
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($countParams);
        $total_count = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Format dates
        foreach ($ratings as &$rating) {
            $rating['created_at'] = date('M d, Y H:i', strtotime($rating['created_at']));
            $rating['updated_at'] = $rating['updated_at'] ? date('M d, Y H:i', strtotime($rating['updated_at'])) : null;
            $rating['delivered_at'] = $rating['delivered_at'] ? date('M d, Y', strtotime($rating['delivered_at'])) : null;
        }

        echo json_encode([
            'status' => 'success',
            'ratings' => $ratings,
            'pagination' => [
                'total' => (int)$total_count,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total_count
            ]
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Update rating status or admin notes
        $input = json_decode(file_get_contents('php://input'), true);
        
        $rating_id = $input['rating_id'] ?? null;
        $status = $input['status'] ?? null;
        $admin_notes = $input['admin_notes'] ?? null;

        if (!$rating_id) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Rating ID is required'
            ]);
            exit;
        }

        $updateFields = [];
        $params = [];

        if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
            $updateFields[] = "status = ?";
            $params[] = $status;
        }

        if ($admin_notes !== null) {
            $updateFields[] = "admin_notes = ?";
            $params[] = $admin_notes;
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'No valid fields to update'
            ]);
            exit;
        }

        $updateFields[] = "updated_at = NOW()";
        $params[] = $rating_id;

        $updateQuery = "UPDATE product_ratings SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $result = $updateStmt->execute($params);

        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Rating updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update rating');
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>





