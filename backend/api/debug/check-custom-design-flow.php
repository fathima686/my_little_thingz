<?php
/**
 * Debug API: Check Custom Design → Cart Flow
 * This helps debug why completed custom designs aren't appearing in cart
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

try {
    require_once "../../config/database.php";
    $database = new Database();
    $pdo = $database->getConnection();
    
    $requestId = $_GET["request_id"] ?? null;
    $customerId = $_GET["customer_id"] ?? null;
    
    if (!$requestId) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "request_id parameter required"
        ]);
        exit;
    }
    
    $debug = [];
    
    // 1. Check request details
    $requestStmt = $pdo->prepare("
        SELECT id, customer_id, user_id, title, description, status, 
               workflow_stage, created_at, updated_at
        FROM custom_requests 
        WHERE id = ?
    ");
    $requestStmt->execute([$requestId]);
    $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if price column exists and add it if available
    try {
        $priceCheck = $pdo->query("SHOW COLUMNS FROM custom_requests LIKE 'price'");
        if ($priceCheck && $priceCheck->rowCount() > 0) {
            $requestStmt = $pdo->prepare("
                SELECT id, customer_id, user_id, title, price, description, status, 
                       workflow_stage, created_at, updated_at
                FROM custom_requests 
                WHERE id = ?
            ");
            $requestStmt->execute([$requestId]);
            $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // Price column doesn't exist, continue without it
        $debug['price_column_missing'] = true;
    }
    
    $debug['request'] = $request;
    
    if (!$request) {
        echo json_encode([
            "status" => "error",
            "message" => "Request not found",
            "debug" => $debug
        ]);
        exit;
    }
    
    // Determine actual customer ID
    $actualCustomerId = $request['customer_id'] ?: $request['user_id'];
    $debug['actual_customer_id'] = $actualCustomerId;
    $debug['provided_customer_id'] = $customerId;
    $debug['customer_id_match'] = ($customerId && $customerId == $actualCustomerId);
    
    // 2. Check design records
    $designStmt = $pdo->prepare("
        SELECT id, request_id, status, design_image_url, canvas_data_file, 
               created_at, updated_at
        FROM custom_request_designs 
        WHERE request_id = ?
        ORDER BY created_at DESC
    ");
    $designStmt->execute([$requestId]);
    $designs = $designStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $debug['designs'] = $designs;
    $debug['design_count'] = count($designs);
    $debug['has_completed_design'] = false;
    
    foreach ($designs as $design) {
        if ($design['status'] === 'design_completed') {
            $debug['has_completed_design'] = true;
            break;
        }
    }
    
    // 3. Check created artworks
    $artworkStmt = $pdo->prepare("
        SELECT id, title, description, price, image_url, category, status, 
               availability, created_at
        FROM artworks 
        WHERE category = 'custom' OR description LIKE ?
        ORDER BY created_at DESC
    ");
    $artworkStmt->execute(["%Request #$requestId%"]);
    $artworks = $artworkStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $debug['artworks'] = $artworks;
    $debug['artwork_count'] = count($artworks);
    
    // 4. Check cart items for this customer
    if ($actualCustomerId) {
        $cartStmt = $pdo->prepare("
            SELECT c.id as cart_id, c.artwork_id, c.quantity, c.added_at,
                   a.title, a.price, a.category, a.description
            FROM cart c
            JOIN artworks a ON c.artwork_id = a.id
            WHERE c.user_id = ?
            ORDER BY c.added_at DESC
        ");
        $cartStmt->execute([$actualCustomerId]);
        $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $debug['cart_items'] = $cartItems;
        $debug['cart_item_count'] = count($cartItems);
        
        // Check for custom designs in cart
        $customCartItems = array_filter($cartItems, function($item) use ($requestId) {
            return (strpos($item['title'], 'Custom Design') !== false) ||
                   (strpos($item['description'], "Request #$requestId") !== false);
        });
        
        $debug['custom_cart_items'] = array_values($customCartItems);
        $debug['custom_cart_count'] = count($customCartItems);
        $debug['has_custom_in_cart'] = count($customCartItems) > 0;
    } else {
        $debug['cart_items'] = [];
        $debug['cart_item_count'] = 0;
        $debug['custom_cart_items'] = [];
        $debug['custom_cart_count'] = 0;
        $debug['has_custom_in_cart'] = false;
        $debug['error'] = 'No customer ID found in request';
    }
    
    // 5. Analysis and recommendations
    $debug['analysis'] = [];
    
    if (!$debug['has_completed_design']) {
        $debug['analysis'][] = "❌ No completed design found - admin needs to complete the design first";
    } else {
        $debug['analysis'][] = "✅ Completed design found";
    }
    
    if ($debug['artwork_count'] === 0) {
        $debug['analysis'][] = "❌ No artwork created - check if addCompletedDesignToCart function is working";
    } else {
        $debug['analysis'][] = "✅ Artwork(s) created: " . $debug['artwork_count'];
    }
    
    if (!$debug['has_custom_in_cart']) {
        $debug['analysis'][] = "❌ Custom design not in cart - check cart addition logic";
    } else {
        $debug['analysis'][] = "✅ Custom design found in cart";
    }
    
    if (!$actualCustomerId) {
        $debug['analysis'][] = "❌ No customer ID in request - cannot add to cart";
    }
    
    // 6. Recommendations
    $debug['recommendations'] = [];
    
    if (!$debug['has_completed_design']) {
        $debug['recommendations'][] = "Complete the design using the admin design editor";
    }
    
    if ($debug['has_completed_design'] && $debug['artwork_count'] === 0) {
        $debug['recommendations'][] = "Check server logs for errors in addCompletedDesignToCart function";
        $debug['recommendations'][] = "Verify artworks table exists and is writable";
    }
    
    if ($debug['artwork_count'] > 0 && !$debug['has_custom_in_cart']) {
        $debug['recommendations'][] = "Check if cart table exists and is writable";
        $debug['recommendations'][] = "Verify customer ID mapping is correct";
        $debug['recommendations'][] = "Check for duplicate prevention logic blocking cart addition";
    }
    
    if ($debug['has_custom_in_cart']) {
        $debug['recommendations'][] = "✅ Everything looks good! Custom design should appear in customer's cart";
    }
    
    echo json_encode([
        "status" => "success",
        "message" => "Debug analysis complete",
        "debug" => $debug
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Debug error: " . $e->getMessage(),
        "debug" => $debug ?? []
    ]);
}
?>