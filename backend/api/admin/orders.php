<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';
require_once '../../models/Shiprocket.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get admin user ID
    $adminId = null;
    if (isset($_SERVER['HTTP_X_ADMIN_USER_ID']) && $_SERVER['HTTP_X_ADMIN_USER_ID'] !== '') {
        $adminId = $_SERVER['HTTP_X_ADMIN_USER_ID'];
    }
    if (!$adminId) {
        $altKeys = ['REDIRECT_HTTP_X_ADMIN_USER_ID', 'X_ADMIN_USER_ID', 'HTTP_X_ADMIN_USERID'];
        foreach ($altKeys as $k) {
            if (isset($_SERVER[$k]) && $_SERVER[$k] !== '') {
                $adminId = $_SERVER[$k];
                break;
            }
        }
    }
    if (!$adminId && isset($_GET['admin_id']) && $_GET['admin_id'] !== '') {
        $adminId = $_GET['admin_id'];
    }

    if (!$adminId) {
        echo json_encode(['status' => 'error', 'message' => 'Admin authentication required']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch orders with user details
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
                    CONCAT(u.first_name, ' ', u.last_name) AS customer_name,
                    u.email AS customer_email
                  FROM orders o
                  JOIN users u ON o.user_id = u.id
                  ORDER BY o.created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();
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

        echo json_encode(['status' => 'success', 'orders' => $orders]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $orderId = $input['order_id'] ?? null;
        $action = $input['action'] ?? null; // 'ship', 'deliver', 'cancel'

        if (!$orderId || !$action) {
            echo json_encode(['status' => 'error', 'message' => 'Order ID and action required']);
            exit;
        }

        if ($action === 'ship') {
            // Create shipment in Shiprocket
            $shiprocket = new Shiprocket();

            // Fetch order details
            $orderQuery = "SELECT o.*, u.first_name, u.last_name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?";
            $orderStmt = $db->prepare($orderQuery);
            $orderStmt->execute([$orderId]);
            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                echo json_encode(['status' => 'error', 'message' => 'Order not found']);
                exit;
            }

            // Fetch order items
            $itemsQuery = "SELECT oi.quantity, oi.price, a.title, a.description FROM order_items oi JOIN artworks a ON oi.artwork_id = a.id WHERE oi.order_id = ?";
            $itemsStmt = $db->prepare($itemsQuery);
            $itemsStmt->execute([$orderId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Parse shipping address (assuming JSON)
            $shippingAddress = json_decode($order['shipping_address'], true);

            // Prepare Shiprocket order data
            $shiprocketConfig = require __DIR__ . '/../../config/shiprocket.php';
            $fullName = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
            $fallbackPhone = '9999999999';
            $billingPhone = $fallbackPhone;
            $shippingPhone = $fallbackPhone;
            if (is_array($shippingAddress)) {
                if (!empty($shippingAddress['phone'])) { $billingPhone = (string)$shippingAddress['phone']; $shippingPhone = (string)$shippingAddress['phone']; }
            }
            $shiprocketOrder = [
                'order_id' => $order['order_number'],
                'order_date' => date('Y-m-d H:i:s', strtotime($order['created_at'])),
                'pickup_location' => $shiprocketConfig['pickup_location'] ?? 'Primary',
                'channel_id' => $shiprocketConfig['channel_id'] ?? '',
                'comment' => 'Order from My Little Thingz',
                'billing_customer_name' => $fullName !== '' ? $fullName : 'Customer',
                'billing_last_name' => '',
                'billing_address' => $shippingAddress['address_line1'] ?? '',
                'billing_address_2' => $shippingAddress['address_line2'] ?? '',
                'billing_city' => $shippingAddress['city'] ?? '',
                'billing_pincode' => $shippingAddress['pincode'] ?? '',
                'billing_state' => $shippingAddress['state'] ?? '',
                'billing_country' => $shippingAddress['country'] ?? 'India',
                'billing_email' => $order['email'],
                'billing_phone' => $billingPhone,
                'shipping_is_billing' => true,
                'shipping_customer_name' => $fullName !== '' ? $fullName : 'Customer',
                'shipping_last_name' => '',
                'shipping_address' => $shippingAddress['address_line1'] ?? '',
                'shipping_address_2' => $shippingAddress['address_line2'] ?? '',
                'shipping_city' => $shippingAddress['city'] ?? '',
                'shipping_pincode' => $shippingAddress['pincode'] ?? '',
                'shipping_country' => $shippingAddress['country'] ?? 'India',
                'shipping_state' => $shippingAddress['state'] ?? '',
                'shipping_email' => $order['email'],
                'shipping_phone' => $shippingPhone,
                'order_items' => array_map(function($item) {
                    return [
                        'name' => $item['title'],
                        'sku' => '', // You may want to add SKU to artworks
                        'units' => $item['quantity'],
                        'selling_price' => $item['price'],
                        'discount' => 0,
                        'tax' => 0,
                        'hsn' => '', // HSN code if applicable
                    ];
                }, $items),
                'payment_method' => 'Prepaid', // Assuming prepaid since payment is done via Razorpay
                'shipping_charges' => $order['shipping_cost'],
                'giftwrap_charges' => 0,
                'transaction_charges' => 0,
                'total_discount' => 0,
                'sub_total' => $order['subtotal'],
                'length' => 10, // Default dimensions, you may want to make this configurable
                'breadth' => 10,
                'height' => 10,
                'weight' => 0.5, // Default weight
            ];

            $response = $shiprocket->createOrder($shiprocketOrder);

            if (isset($response['order_id']) && isset($response['shipment_id'])) {
                // Update order with tracking info
                $updateQuery = "UPDATE orders SET status = 'shipped', shipped_at = NOW(), tracking_number = ?, shiprocket_order_id = ?, estimated_delivery = ? WHERE id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$response['awb'], $response['order_id'], $response['etd'] ?? null, $orderId]);

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Order shipped successfully',
                    'tracking_number' => $response['awb'],
                    'shiprocket_order_id' => $response['order_id']
                ]);
            } else {
                $errMsg = 'Failed to create shipment in Shiprocket';
                if (is_array($response)) {
                    if (isset($response['message']) && is_string($response['message'])) {
                        $errMsg .= ': ' . $response['message'];
                    } elseif (isset($response['error']) && is_string($response['error'])) {
                        $errMsg .= ': ' . $response['error'];
                    }
                }
                echo json_encode(['status' => 'error', 'message' => $errMsg, 'response' => $response]);
            }

        } elseif ($action === 'deliver') {
            $updateQuery = "UPDATE orders SET status = 'delivered', delivered_at = NOW() WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$orderId]);
            echo json_encode(['status' => 'success', 'message' => 'Order marked as delivered']);

        } elseif ($action === 'cancel') {
            // Cancel in Shiprocket if shipped
            $orderQuery = "SELECT tracking_number, shiprocket_order_id FROM orders WHERE id = ?";
            $orderStmt = $db->prepare($orderQuery);
            $orderStmt->execute([$orderId]);
            $orderData = $orderStmt->fetch(PDO::FETCH_ASSOC);
            $tracking = $orderData['tracking_number'] ?? null;
            $shiprocketOrderId = $orderData['shiprocket_order_id'] ?? null;

            if ($tracking && $shiprocketOrderId) {
                $shiprocket = new Shiprocket();
                $shiprocket->cancelOrder($shiprocketOrderId);
            }

            $updateQuery = "UPDATE orders SET status = 'cancelled' WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$orderId]);
            echo json_encode(['status' => 'success', 'message' => 'Order cancelled']);

        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        }

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>