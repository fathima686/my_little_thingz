<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';
require_once '../../models/Shiprocket.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

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

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $order_id = $input['order_id'] ?? null;

    if (!$order_id) {
        echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
        exit;
    }

    // Get order details
    $orderQuery = "SELECT o.*, 
                   CONCAT(u.first_name, ' ', u.last_name) as customer_name, 
                   u.email as customer_email
                   FROM orders o
                   JOIN users u ON o.user_id = u.id
                   WHERE o.id = ?";
    $stmt = $db->prepare($orderQuery);
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit;
    }

    // Check if shipment already created
    if ($order['shiprocket_order_id']) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Shipment already created for this order',
            'shiprocket_order_id' => $order['shiprocket_order_id']
        ]);
        exit;
    }

    // Get order items
    $itemsQuery = "SELECT oi.*, a.title, a.image_url
                   FROM order_items oi
                   JOIN artworks a ON oi.artwork_id = a.id
                   WHERE oi.order_id = ?";
    $stmt = $db->prepare($itemsQuery);
    $stmt->execute([$order_id]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($orderItems)) {
        echo json_encode(['status' => 'error', 'message' => 'No items found in order']);
        exit;
    }

    // Parse shipping address
    $shippingAddress = $order['shipping_address'] ?? '';
    $addressLines = explode("\n", $shippingAddress);
    
    // Try to extract structured address
    $billing_customer_name = $order['customer_name'];
    $billing_address = $addressLines[0] ?? '';
    $billing_city = '';
    $billing_pincode = '';
    $billing_state = '';
    $billing_phone = '';
    
    // Extract phone from shipping address
    if (preg_match('/(?:Phone|Mobile|Tel):\s*(\d{10})/i', $shippingAddress, $matches)) {
        $billing_phone = $matches[1];
    } elseif (preg_match('/\b(\d{10})\b/', $shippingAddress, $matches)) {
        $billing_phone = $matches[1];
    }

    // Try to extract pincode from address
    if (preg_match('/\b(\d{6})\b/', $shippingAddress, $matches)) {
        $billing_pincode = $matches[1];
    }

    // Try to extract state
    if (preg_match('/\b(Kerala|Tamil Nadu|Karnataka|Maharashtra|Delhi|Gujarat|Rajasthan|Punjab|Haryana|Uttar Pradesh|Madhya Pradesh|Bihar|West Bengal|Andhra Pradesh|Telangana|Odisha|Assam|Jharkhand|Chhattisgarh|Uttarakhand|Himachal Pradesh|Goa|Jammu and Kashmir|Ladakh|Puducherry|Chandigarh|Sikkim|Meghalaya|Manipur|Mizoram|Nagaland|Tripura|Arunachal Pradesh)\b/i', $shippingAddress, $matches)) {
        $billing_state = $matches[1];
    }

    // Extract city (line before pincode or state)
    foreach ($addressLines as $line) {
        $line = trim($line);
        if (!empty($line) && !preg_match('/phone|mobile/i', $line)) {
            if (empty($billing_city) && !preg_match('/\d{6}/', $line)) {
                $billing_city = $line;
            }
        }
    }

    // Load warehouse config for pickup address
    $warehouseConfig = require __DIR__ . '/../../config/warehouse.php';
    $pickupAddress = $warehouseConfig['address_fields'];

    // Prepare order items for Shiprocket
    $shiprocketItems = [];
    $totalWeight = 0;
    foreach ($orderItems as $item) {
        $shiprocketItems[] = [
            'name' => $item['title'],
            'sku' => 'ART-' . $item['artwork_id'],
            'units' => (int)$item['quantity'],
            'selling_price' => (float)$item['price'],
            'discount' => 0,
            'tax' => 0,
            'hsn' => ''
        ];
        // Estimate weight (0.5 kg per item by default)
        $totalWeight += 0.5 * (int)$item['quantity'];
    }

    // Prepare Shiprocket order data
    $shiprocketOrderData = [
        'order_id' => $order['order_number'],
        'order_date' => date('Y-m-d H:i', strtotime($order['created_at'])),
        'pickup_location' => $pickupAddress['name'],
        'channel_id' => '',
        'comment' => 'Order from My Little Thingz',
        'billing_customer_name' => $billing_customer_name,
        'billing_last_name' => '',
        'billing_address' => $billing_address,
        'billing_address_2' => '',
        'billing_city' => $billing_city ?: 'Unknown',
        'billing_pincode' => $billing_pincode ?: '000000',
        'billing_state' => $billing_state ?: 'Kerala',
        'billing_country' => 'India',
        'billing_email' => $order['customer_email'],
        'billing_phone' => $billing_phone,
        'shipping_is_billing' => true,
        'order_items' => $shiprocketItems,
        'payment_method' => $order['payment_method'] === 'razorpay' ? 'Prepaid' : 'COD',
        'shipping_charges' => (float)($order['shipping_cost'] ?? 0),
        'giftwrap_charges' => 0,
        'transaction_charges' => 0,
        'total_discount' => 0,
        'sub_total' => (float)$order['subtotal'],
        'length' => (float)($input['length'] ?? 10),
        'breadth' => (float)($input['breadth'] ?? 10),
        'height' => (float)($input['height'] ?? 10),
        'weight' => (float)($input['weight'] ?? $totalWeight)
    ];

    // Create order in Shiprocket
    $shiprocket = new Shiprocket();
    $response = $shiprocket->createOrder($shiprocketOrderData);

    if (isset($response['order_id']) && isset($response['shipment_id'])) {
        // Update order with Shiprocket details
        $updateQuery = "UPDATE orders SET 
                        shiprocket_order_id = ?,
                        shiprocket_shipment_id = ?,
                        weight = ?,
                        length = ?,
                        breadth = ?,
                        height = ?,
                        status = 'processing'
                        WHERE id = ?";
        $stmt = $db->prepare($updateQuery);
        $stmt->execute([
            $response['order_id'],
            $response['shipment_id'],
            $shiprocketOrderData['weight'],
            $shiprocketOrderData['length'],
            $shiprocketOrderData['breadth'],
            $shiprocketOrderData['height'],
            $order_id
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Shipment created successfully',
            'data' => [
                'shiprocket_order_id' => $response['order_id'],
                'shiprocket_shipment_id' => $response['shipment_id'],
                'order_number' => $order['order_number']
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to create shipment in Shiprocket',
            'response' => $response
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>