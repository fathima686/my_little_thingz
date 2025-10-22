<?php
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

    // Get user ID robustly from headers or query
    $user_id = null;
    if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') {
        $user_id = $_SERVER['HTTP_X_USER_ID'];
    }
    if (!$user_id) {
        $altKeys = ['REDIRECT_HTTP_X_USER_ID', 'X_USER_ID', 'HTTP_X_USERID'];
        foreach ($altKeys as $k) {
            if (isset($_SERVER[$k]) && $_SERVER[$k] !== '') {
                $user_id = $_SERVER[$k];
                break;
            }
        }
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
        // Fetch user's orders with Shiprocket tracking data
        $query = "SELECT 
                    o.id,
                    o.order_number,
                    o.status,
                    o.total_amount,
                    o.subtotal,
                    o.tax_amount,
                    o.shipping_cost,
                    o.shipping_address,
                    o.tracking_number,
                    o.created_at,
                    o.shipped_at,
                    o.delivered_at,
                    o.estimated_delivery,
                    o.shiprocket_order_id,
                    o.shiprocket_shipment_id,
                    o.awb_code,
                    o.courier_id,
                    o.courier_name,
                    o.shipping_charges,
                    o.pickup_scheduled_date,
                    o.pickup_token_number,
                    o.shipment_status,
                    o.current_status
                  FROM orders o
                  WHERE o.user_id = ?
                  ORDER BY o.created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch order items for each order
        foreach ($orders as &$order) {
            $items_query = "SELECT 
                              oi.id,
                              oi.quantity,
                              oi.price,
                              a.title as name,
                              a.image_url
                            FROM order_items oi
                            JOIN artworks a ON oi.artwork_id = a.id
                            WHERE oi.order_id = ?";
            
            $items_stmt = $db->prepare($items_query);
            $items_stmt->execute([$order['id']]);
            $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format prices
            $order['total_amount'] = number_format($order['total_amount'], 2);
            $order['subtotal'] = $order['subtotal'] ? number_format($order['subtotal'], 2) : null;
            $order['tax_amount'] = $order['tax_amount'] ? number_format($order['tax_amount'], 2) : null;
            $order['shipping_cost'] = $order['shipping_cost'] ? number_format($order['shipping_cost'], 2) : null;
            $order['shipping_charges'] = $order['shipping_charges'] ? number_format($order['shipping_charges'], 2) : null;

            // Format dates
            $order['created_at'] = date('Y-m-d H:i:s', strtotime($order['created_at']));
            $order['shipped_at'] = $order['shipped_at'] ? date('Y-m-d H:i:s', strtotime($order['shipped_at'])) : null;
            $order['delivered_at'] = $order['delivered_at'] ? date('Y-m-d H:i:s', strtotime($order['delivered_at'])) : null;
            $order['estimated_delivery'] = $order['estimated_delivery'] ? date('Y-m-d', strtotime($order['estimated_delivery'])) : null;

            // Format item prices
            foreach ($order['items'] as &$item) {
                $item['price'] = number_format($item['price'], 2);
            }
        }

        echo json_encode([
            'status' => 'success',
            'orders' => $orders
        ]);

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