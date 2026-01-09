<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Customer-Email');

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

// Get customer email from header
$customerEmail = $_SERVER['HTTP_X_CUSTOMER_EMAIL'] ?? '';
if (empty($customerEmail)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Customer email required']);
    exit;
}

// Get or create customer
try {
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$customerEmail]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        // Create new customer
        $stmt = $pdo->prepare("INSERT INTO users (email, name, created_at) VALUES (?, ?, NOW())");
        $customerName = explode('@', $customerEmail)[0]; // Default name from email
        $stmt->execute([$customerEmail, $customerName]);
        $customerId = $pdo->lastInsertId();
        $customerName = $customerName;
    } else {
        $customerId = $customer['id'];
        $customerName = $customer['name'] ?? explode('@', $customerEmail)[0];
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to get customer information']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetCustomerRequests($pdo, $customerId);
        break;
    case 'POST':
        handleSubmitRequest($pdo, $customerId, $customerName, $customerEmail);
        break;
    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}

function handleGetCustomerRequests($pdo, $customerId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id, order_id, title, occasion, description, deadline, 
                status, design_url, customer_feedback, created_at, updated_at
            FROM custom_requests 
            WHERE customer_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$customerId]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'requests' => $requests
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch requests']);
    }
}

function handleSubmitRequest($pdo, $customerId, $customerName, $customerEmail) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $requiredFields = ['title', 'deadline'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "Field '$field' is required"]);
                return;
            }
        }
        
        // Validate deadline is in the future
        $deadline = new DateTime($input['deadline']);
        $now = new DateTime();
        if ($deadline <= $now) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Deadline must be in the future']);
            return;
        }
        
        // Generate unique order ID
        $orderId = 'CR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        
        // Determine priority based on deadline
        $daysUntilDeadline = $now->diff($deadline)->days;
        $priority = 'medium';
        if ($daysUntilDeadline <= 7) {
            $priority = 'high';
        } elseif ($daysUntilDeadline > 30) {
            $priority = 'low';
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO custom_requests (
                order_id, customer_id, customer_name, customer_email, 
                title, occasion, description, requirements, deadline, priority, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted')
        ");
        
        $stmt->execute([
            $orderId,
            $customerId,
            $customerName,
            $customerEmail,
            $input['title'],
            $input['occasion'] ?? '',
            $input['description'] ?? '',
            $input['requirements'] ?? '',
            $input['deadline'],
            $priority
        ]);
        
        $requestId = $pdo->lastInsertId();
        
        // Skip notification creation to avoid database errors
        // Admin can check dashboard directly for new requests
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Custom request submitted successfully',
            'order_id' => $orderId,
            'request_id' => $requestId,
            'estimated_completion' => $deadline->format('Y-m-d'),
            'priority' => $priority
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Database error: ' . $e->getMessage(),
            'code' => $e->getCode(),
            'sql_state' => $e->errorInfo[0] ?? null
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>