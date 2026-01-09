<?php
// Custom requests API with real database images
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once '../../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    // Fallback to sample data if database fails
    include 'custom-requests-complete.php';
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetRequests($pdo);
        break;
    case 'POST':
        handlePostRequest($pdo);
        break;
    case 'PUT':
        handlePutRequest($pdo);
        break;
    case 'DELETE':
        handleDeleteRequest($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}

function handleGetRequests($pdo) {
    try {
        // Get filters from query parameters
        $status = $_GET['status'] ?? 'all';
        $priority = $_GET['priority'] ?? '';
        $search = $_GET['search'] ?? '';
        $limit = min((int)($_GET['limit'] ?? 50), 100);
        $offset = max((int)($_GET['offset'] ?? 0), 0);
        
        // Build query with filters
        $whereConditions = [];
        $params = [];
        
        if (!empty($status) && $status !== 'all') {
            if ($status === 'pending') {
                $whereConditions[] = "cr.status IN ('submitted', 'pending')";
            } else {
                $whereConditions[] = "cr.status = ?";
                $params[] = $status;
            }
        }
        
        if (!empty($priority)) {
            $whereConditions[] = "cr.priority = ?";
            $params[] = $priority;
        }
        
        if (!empty($search)) {
            $whereConditions[] = "(cr.customer_name LIKE ? OR cr.customer_email LIKE ? OR cr.title LIKE ? OR cr.order_id LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Main query to get requests with user details
        $stmt = $pdo->prepare("
            SELECT 
                cr.*,
                DATEDIFF(cr.deadline, CURDATE()) as days_until_deadline,
                u.name as full_name,
                u.email as user_email,
                SUBSTRING_INDEX(u.name, ' ', 1) as first_name,
                SUBSTRING_INDEX(u.name, ' ', -1) as last_name
            FROM custom_requests cr
            LEFT JOIN users u ON cr.customer_id = u.id
            $whereClause
            ORDER BY 
                CASE cr.priority 
                    WHEN 'high' THEN 1 
                    WHEN 'medium' THEN 2 
                    WHEN 'low' THEN 3 
                END,
                cr.deadline ASC,
                cr.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get images for each request
        foreach ($requests as &$request) {
            // Check which columns exist
            $checkCol = $pdo->query("SHOW COLUMNS FROM custom_request_images LIKE 'image_url'");
            $hasImageUrl = $checkCol->rowCount() > 0;
            
            if (!$hasImageUrl) {
                $checkCol = $pdo->query("SHOW COLUMNS FROM custom_request_images LIKE 'image_path'");
                $hasImagePath = $checkCol->rowCount() > 0;
            } else {
                $hasImagePath = false;
            }
            
            $checkCol = $pdo->query("SHOW COLUMNS FROM custom_request_images LIKE 'uploaded_at'");
            $hasUploadedAt = $checkCol->rowCount() > 0;
            
            if (!$hasUploadedAt) {
                $checkCol = $pdo->query("SHOW COLUMNS FROM custom_request_images LIKE 'upload_time'");
                $hasUploadTime = $checkCol->rowCount() > 0;
            } else {
                $hasUploadTime = false;
            }
            
            $imageColumn = $hasImageUrl ? 'image_url' : ($hasImagePath ? 'image_path' : 'image_url');
            $timeColumn = $hasUploadedAt ? 'uploaded_at' : ($hasUploadTime ? 'upload_time' : 'uploaded_at');
            
            // Get uploaded images for this request
            $selectCols = [$imageColumn];
            if ($hasUploadedAt || $hasUploadTime) {
                $selectCols[] = $timeColumn;
            }
            $selectColsStr = implode(', ', $selectCols);
            
            $imageStmt = $pdo->prepare("
                SELECT $selectColsStr
                FROM custom_request_images 
                WHERE request_id = ? 
                ORDER BY $timeColumn DESC
            ");
            $imageStmt->execute([$request['id']]);
            $images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format images array
            $request['images'] = [];
            foreach ($images as $img) {
                $imagePathOrUrl = $img[$imageColumn] ?? null;
                if (!empty($imagePathOrUrl)) {
                    // If it's already a full URL, use it as is
                    if (preg_match('/^https?:\/\//', $imagePathOrUrl)) {
                        $fullUrl = $imagePathOrUrl;
                    } else {
                        // It's a relative path, make it absolute
                        $fullUrl = 'http://localhost/my_little_thingz/backend/' . ltrim($imagePathOrUrl, '/');
                    }
                    $request['images'][] = $fullUrl;
                }
            }
            
            // If no images, check if there are any files in the upload directory for this request
            if (empty($request['images'])) {
                $uploadDir = __DIR__ . '/../../uploads/custom-requests/';
                if (is_dir($uploadDir)) {
                    $files = glob($uploadDir . 'cr_' . $request['id'] . '_*');
                    foreach ($files as $file) {
                        $filename = basename($file);
                        $request['images'][] = 'http://localhost/my_little_thingz/backend/uploads/custom-requests/' . $filename;
                    }
                }
            }
            
            // Format data for UI compatibility
            $request['first_name'] = $request['first_name'] ?: explode(' ', $request['customer_name'])[0];
            $request['last_name'] = $request['last_name'] ?: (explode(' ', $request['customer_name'])[1] ?? '');
            $request['email'] = $request['user_email'] ?: $request['customer_email'];
            $request['category_name'] = $request['occasion'] ?: 'General';
            $request['budget_min'] = '500'; // Default values - can be made dynamic
            $request['budget_max'] = '1000';
        }
        
        // Get total count for pagination
        $countParams = array_slice($params, 0, -2); // Remove limit and offset
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM custom_requests cr 
            LEFT JOIN users u ON cr.customer_id = u.id
            $whereClause
        ");
        $countStmt->execute($countParams);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get statistics
        $statsStmt = $pdo->query("
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status IN ('submitted', 'pending') THEN 1 ELSE 0 END) as pending_requests,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_requests,
                SUM(CASE WHEN priority = 'high' AND DATEDIFF(deadline, CURDATE()) <= 3 THEN 1 ELSE 0 END) as urgent_requests
            FROM custom_requests
        ");
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'requests' => $requests,
            'total_count' => (int)$totalCount,
            'showing_count' => count($requests),
            'stats' => $stats,
            'message' => 'Custom requests loaded with real images',
            'api_version' => 'real-images-v1.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'filter_applied' => $status
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to fetch requests',
            'debug' => $e->getMessage()
        ]);
    }
}

function handlePostRequest($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
            return;
        }
        
        // Handle status update
        if (isset($input['request_id']) && isset($input['status'])) {
            $requestId = $input['request_id'];
            $newStatus = $input['status'];
            
            $stmt = $pdo->prepare("
                UPDATE custom_requests 
                SET status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$newStatus, $requestId]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Request status updated successfully',
                    'request_id' => $requestId,
                    'new_status' => $newStatus
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Request not found']);
            }
            return;
        }
        
        // Handle new request creation
        if (isset($input['customer_name']) && isset($input['title'])) {
            $orderId = 'CR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            $stmt = $pdo->prepare("
                INSERT INTO custom_requests (
                    order_id, customer_id, customer_name, customer_email, 
                    title, occasion, description, requirements, deadline, priority, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted')
            ");
            
            $stmt->execute([
                $orderId,
                $input['customer_id'] ?? 0,
                $input['customer_name'],
                $input['customer_email'] ?? '',
                $input['title'],
                $input['occasion'] ?? '',
                $input['description'] ?? '',
                $input['requirements'] ?? '',
                $input['deadline'] ?? date('Y-m-d', strtotime('+7 days')),
                $input['priority'] ?? 'medium'
            ]);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Custom request created successfully',
                'order_id' => $orderId,
                'request_id' => $pdo->lastInsertId()
            ]);
            return;
        }
        
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid request data']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to process request',
            'debug' => $e->getMessage()
        ]);
    }
}

function handlePutRequest($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Request ID is required']);
            return;
        }
        
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['status', 'priority', 'admin_notes', 'design_url', 'deadline'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No valid fields to update']);
            return;
        }
        
        $params[] = $input['id'];
        
        $stmt = $pdo->prepare("
            UPDATE custom_requests 
            SET " . implode(', ', $updateFields) . ", updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Request updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Request not found']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to update request',
            'debug' => $e->getMessage()
        ]);
    }
}

function handleDeleteRequest($pdo) {
    try {
        $requestId = $_GET['id'] ?? '';
        
        if (empty($requestId)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Request ID is required']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM custom_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Request deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Request not found']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to delete request',
            'debug' => $e->getMessage()
        ]);
    }
}
?>