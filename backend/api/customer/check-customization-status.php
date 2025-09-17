<?php
require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $user_id = $_SERVER['HTTP_X_USER_ID'] ?? null;
    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'User ID required']);
        exit;
    }

    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();

    // Get cart items
    $stmt = $db->prepare("
        SELECT c.id as cart_id, c.artwork_id, c.quantity, a.title, a.requires_customization 
        FROM cart c 
        JOIN artworks a ON c.artwork_id = a.id 
        WHERE c.user_id = ? AND a.status = 'active'
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $customization_status = [
        'has_customization_items' => false,
        'pending_requests' => [],
        'approved_requests' => [],
        'can_proceed_payment' => true
    ];

    // Check if user has any cart-originated customization requests (prefer source='cart' when column exists)
    $hasSource = false;
    try {
        $chk = $db->query("SHOW COLUMNS FROM custom_requests LIKE 'source'");
        $hasSource = $chk && $chk->rowCount() > 0;
    } catch (Throwable $e) {}
    if ($hasSource) {
        $customization_stmt = $db->prepare("
            SELECT id, title, status, created_at, special_instructions 
            FROM custom_requests 
            WHERE user_id = ? AND source = 'cart'
            ORDER BY created_at DESC
        ");
        $customization_stmt->execute([$user_id]);
    } else {
        $customization_stmt = $db->prepare("
            SELECT id, title, status, created_at, special_instructions 
            FROM custom_requests 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $customization_stmt->execute([$user_id]);
    }
    $customization_requests = $customization_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if any cart items require customization
    $has_customization_items = false;
    foreach ($cart_items as $item) {
        if ($item['requires_customization']) {
            $has_customization_items = true;
            break;
        }
    }

    if ($has_customization_items) {
        $customization_status['has_customization_items'] = true;
        
        if (empty($customization_requests)) {
            // No customization requests submitted
            $customization_status['pending_requests'][] = [
                'title' => 'Customization Required',
                'status' => 'not_submitted',
                'message' => 'Please submit customization requests for items requiring customization'
            ];
            $customization_status['can_proceed_payment'] = false;
        } else {
            // Check status of existing requests
            $has_pending = false;
            $has_approved = false;
            
            foreach ($customization_requests as $request) {
                if ($request['status'] === 'pending') {
                    $has_pending = true;
                    $customization_status['pending_requests'][] = [
                        'title' => $request['title'],
                        'status' => 'pending',
                        'message' => 'Awaiting admin approval',
                        'submitted_at' => $request['created_at']
                    ];
                } elseif ($request['status'] === 'completed') {
                    $has_approved = true;
                    $customization_status['approved_requests'][] = [
                        'title' => $request['title'],
                        'status' => 'approved',
                        'message' => 'Customization approved',
                        'approved_at' => $request['created_at']
                    ];
                } elseif ($request['status'] === 'cancelled') {
                    $customization_status['pending_requests'][] = [
                        'title' => $request['title'],
                        'status' => 'rejected',
                        'message' => 'Customization request was cancelled'
                    ];
                }
            }
            
            // Only allow payment if there's at least one approved request and no pending requests
            if ($has_pending || (!$has_approved && !empty($customization_requests))) {
                $customization_status['can_proceed_payment'] = false;
            }
        }
    }

    echo json_encode([
        'status' => 'success',
        'customization_status' => $customization_status
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
