<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';
require_once '../../models/Shiprocket.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get admin user ID
    $user_id = null;
    if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') {
        $user_id = $_SERVER['HTTP_X_USER_ID'];
    }

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Admin authentication required']);
        exit;
    }

    // Verify admin role
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        echo json_encode(['status' => 'error', 'message' => 'Admin access required']);
        exit;
    }

    // Get query parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
    $status = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;
    $offset = ($page - 1) * $per_page;

    // Build query
    $whereConditions = [];
    $params = [];

    if ($status) {
        $whereConditions[] = "o.status = ?";
        $params[] = $status;
    }

    if ($search) {
        $whereConditions[] = "(o.order_number LIKE ? OR o.awb_code LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.email LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Get total count
    $countQuery = "SELECT COUNT(*) as total 
                   FROM orders o
                   JOIN users u ON o.user_id = u.id
                   $whereClause";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get orders with shipment details
    $ordersQuery = "SELECT 
                        o.id,
                        o.order_number,
                        o.status,
                        o.payment_status,
                        o.total_amount,
                        o.created_at,
                        o.shiprocket_order_id,
                        o.shiprocket_shipment_id,
                        o.awb_code,
                        o.courier_name,
                        o.courier_id,
                        o.pickup_scheduled_date,
                        o.pickup_token_number,
                        o.label_url,
                        o.manifest_url,
                        o.shipping_charges,
                        o.weight,
                        o.estimated_delivery,
                        CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                        u.email as customer_email
                    FROM orders o
                    JOIN users u ON o.user_id = u.id
                    $whereClause
                    ORDER BY o.created_at DESC
                    LIMIT ? OFFSET ?";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($ordersQuery);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get order items for each order
    foreach ($orders as &$order) {
        $itemsQuery = "SELECT oi.*, a.title, a.image_url
                       FROM order_items oi
                       JOIN artworks a ON oi.artwork_id = a.id
                       WHERE oi.order_id = ?";
        $stmt = $db->prepare($itemsQuery);
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Determine shipment status
        $order['shipment_status'] = 'not_created';
        if ($order['shiprocket_order_id']) {
            $order['shipment_status'] = 'created';
        }
        if ($order['awb_code']) {
            $order['shipment_status'] = 'awb_assigned';
        }
        if ($order['pickup_scheduled_date']) {
            $order['shipment_status'] = 'pickup_scheduled';
        }
        if ($order['status'] === 'shipped') {
            $order['shipment_status'] = 'shipped';
        }
        if ($order['status'] === 'delivered') {
            $order['shipment_status'] = 'delivered';
        }
    }
    unset($order);

    // Get statistics
    $statsQuery = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN shiprocket_order_id IS NOT NULL THEN 1 ELSE 0 END) as shipments_created,
                    SUM(CASE WHEN awb_code IS NOT NULL THEN 1 ELSE 0 END) as awb_assigned,
                    SUM(CASE WHEN pickup_scheduled_date IS NOT NULL THEN 1 ELSE 0 END) as pickup_scheduled,
                    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                   FROM orders
                   WHERE payment_status = 'paid'";
    $stmt = $db->prepare($statsQuery);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'orders' => $orders,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => (int)$totalCount,
                'total_pages' => ceil($totalCount / $per_page)
            ],
            'statistics' => $stats
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>