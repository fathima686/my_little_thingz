<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Create custom_requests table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS custom_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    occasion VARCHAR(100),
    description TEXT,
    requirements TEXT,
    deadline DATE,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('submitted', 'drafted_by_admin', 'changes_requested', 'approved_by_customer', 'locked_for_production') DEFAULT 'submitted',
    design_url VARCHAR(500),
    admin_notes TEXT,
    customer_feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetRequests($pdo);
        break;
    case 'POST':
        handleCreateRequest($pdo);
        break;
    case 'PUT':
        handleUpdateRequest($pdo);
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
        $status = $_GET['status'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $search = $_GET['search'] ?? '';
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        // Build query with filters
        $whereConditions = [];
        $params = [];
        
        if (!empty($status)) {
            $whereConditions[] = "cr.status = ?";
            $params[] = $status;
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
        
        // Get requests with pagination
        $stmt = $pdo->prepare("
            SELECT 
                cr.*,
                DATEDIFF(cr.deadline, CURDATE()) as days_until_deadline
            FROM custom_requests cr
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
        
        // Get total count for pagination
        $countParams = array_slice($params, 0, -2); // Remove limit and offset
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM custom_requests cr 
            $whereClause
        ");
        $countStmt->execute($countParams);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get statistics
        $statsStmt = $pdo->query("
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status IN ('submitted', 'changes_requested') THEN 1 ELSE 0 END) as pending_requests,
                SUM(CASE WHEN status IN ('approved_by_customer', 'locked_for_production') THEN 1 ELSE 0 END) as completed_requests,
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
            'message' => 'Custom requests loaded successfully (no auth required)'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch requests: ' . $e->getMessage()]);
    }
}

function handleCreateRequest($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $requiredFields = ['customer_id', 'customer_name', 'customer_email', 'title', 'deadline'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "Field '$field' is required"]);
                return;
            }
        }
        
        // Generate unique order ID
        $orderId = 'CR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        
        $stmt = $pdo->prepare("
            INSERT INTO custom_requests (
                order_id, customer_id, customer_name, customer_email, 
                title, occasion, description, requirements, deadline, priority
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $orderId,
            $input['customer_id'],
            $input['customer_name'],
            $input['customer_email'],
            $input['title'],
            $input['occasion'] ?? '',
            $input['description'] ?? '',
            $input['requirements'] ?? '',
            $input['deadline'],
            $input['priority'] ?? 'medium'
        ]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Custom request created successfully',
            'order_id' => $orderId,
            'request_id' => $pdo->lastInsertId()
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create request: ' . $e->getMessage()]);
    }
}

function handleUpdateRequest($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Request ID is required']);
            return;
        }
        
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['status', 'priority', 'design_url', 'admin_notes', 'customer_feedback', 'deadline'];
        
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
        echo json_encode(['status' => 'error', 'message' => 'Failed to update request: ' . $e->getMessage()]);
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
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete request: ' . $e->getMessage()]);
    }
}
?>