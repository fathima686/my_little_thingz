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

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    
    $delivery_pincode = $input['pincode'] ?? null;
    $weight = $input['weight'] ?? 0.5; // Default 0.5 kg
    $cod = $input['cod'] ?? 0; // 0 for prepaid, 1 for COD

    if (!$delivery_pincode) {
        echo json_encode(['status' => 'error', 'message' => 'Delivery pincode is required']);
        exit;
    }

    // Validate pincode format
    if (!preg_match('/^\d{6}$/', $delivery_pincode)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid pincode format. Must be 6 digits']);
        exit;
    }

    // Load warehouse config for pickup pincode
    $warehouseConfig = require __DIR__ . '/../../config/warehouse.php';
    $pickup_pincode = $warehouseConfig['address_fields']['pincode'];

    // Check cache first (optional optimization)
    $cacheQuery = "SELECT courier_data, expires_at FROM courier_serviceability_cache 
                   WHERE pickup_pincode = ? AND delivery_pincode = ? 
                   AND weight = ? AND cod = ? 
                   AND expires_at > NOW()
                   ORDER BY created_at DESC LIMIT 1";
    $stmt = $db->prepare($cacheQuery);
    $stmt->execute([$pickup_pincode, $delivery_pincode, $weight, $cod]);
    $cached = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cached) {
        $courierData = json_decode($cached['courier_data'], true);
        echo json_encode([
            'status' => 'success',
            'message' => 'Shipping rates retrieved (cached)',
            'couriers' => $courierData,
            'cached' => true
        ]);
        exit;
    }

    // Get courier serviceability from Shiprocket
    $shiprocket = new Shiprocket();
    $params = [
        'pickup_postcode' => $pickup_pincode,
        'delivery_postcode' => $delivery_pincode,
        'weight' => $weight,
        'cod' => $cod
    ];

    $response = $shiprocket->getCourierServiceability($params);

    if (isset($response['data']['available_courier_companies'])) {
        $couriers = $response['data']['available_courier_companies'];
        
        // Cache the result for 24 hours
        $cacheInsert = "INSERT INTO courier_serviceability_cache 
                        (pickup_pincode, delivery_pincode, weight, cod, courier_data, expires_at)
                        VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))";
        $stmt = $db->prepare($cacheInsert);
        $stmt->execute([
            $pickup_pincode,
            $delivery_pincode,
            $weight,
            $cod,
            json_encode($couriers)
        ]);

        // Format courier data for frontend
        $formattedCouriers = array_map(function($courier) {
            return [
                'id' => $courier['id'] ?? null,
                'name' => $courier['courier_name'] ?? '',
                'rate' => $courier['rate'] ?? 0,
                'estimated_delivery_days' => $courier['estimated_delivery_days'] ?? null,
                'etd' => $courier['etd'] ?? '',
                'cod_charges' => $courier['cod_charges'] ?? 0,
                'freight_charge' => $courier['freight_charge'] ?? 0,
                'rating' => $courier['rating'] ?? 0,
                'recommendation' => $courier['recommendation'] ?? ''
            ];
        }, $couriers);

        // Sort by rate (cheapest first)
        usort($formattedCouriers, function($a, $b) {
            return $a['rate'] <=> $b['rate'];
        });

        echo json_encode([
            'status' => 'success',
            'message' => 'Shipping rates retrieved',
            'couriers' => $formattedCouriers,
            'cheapest' => $formattedCouriers[0] ?? null,
            'cached' => false
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Unable to fetch shipping rates. Please check pincode.',
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