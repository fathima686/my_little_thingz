<?php
/**
 * Download Invoice PDF
 * Generates and downloads invoice PDF for completed orders
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';
require_once '../../includes/InvoicePDFGenerator.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user ID from headers or query
    $user_id = null;
    if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') {
        $user_id = $_SERVER['HTTP_X_USER_ID'];
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
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'User ID required'
        ]);
        exit;
    }

    // Get order ID from query parameters
    $order_id = $_GET['order_id'] ?? null;
    if (!$order_id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Order ID required'
        ]);
        exit;
    }

    // Verify order belongs to user and is completed (paid, processing, shipped, or delivered)
    $orderQuery = "SELECT 
                    o.id,
                    o.order_number,
                    o.total_amount,
                    o.subtotal,
                    o.tax_amount,
                    o.shipping_cost,
                    o.shipping_charges,
                    o.shipping_address,
                    o.created_at,
                    o.payment_status,
                    o.status,
                    u.first_name,
                    u.last_name,
                    u.email
                  FROM orders o
                  JOIN users u ON o.user_id = u.id
                  WHERE o.id = ? AND o.user_id = ? AND (o.payment_status = 'paid' OR o.status IN ('processing', 'shipped', 'delivered'))";
    
    $orderStmt = $db->prepare($orderQuery);
    $orderStmt->execute([$order_id, $user_id]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Order not found or not completed'
        ]);
        exit;
    }

    // Get invoice data
    $invoiceQuery = "SELECT 
                      invoice_number,
                      invoice_date,
                      billing_name,
                      billing_email,
                      billing_address,
                      subtotal,
                      tax_amount,
                      shipping_cost,
                      addon_total,
                      total_amount,
                      items_json,
                      addons_json
                    FROM invoices 
                    WHERE order_id = ?";
    
    $invoiceStmt = $db->prepare($invoiceQuery);
    $invoiceStmt->execute([$order_id]);
    $invoice = $invoiceStmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invoice not found'
        ]);
        exit;
    }

    // Get order items
    $itemsQuery = "SELECT 
                    oi.quantity,
                    oi.price,
                    a.title as artwork_name,
                    a.image_url as artwork_image
                  FROM order_items oi
                  JOIN artworks a ON oi.artwork_id = a.id
                  WHERE oi.order_id = ?";
    
    $itemsStmt = $db->prepare($itemsQuery);
    $itemsStmt->execute([$order_id]);
    $order_items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get order addons if table exists
    $order_addons = [];
    try {
        $addonsCheck = $db->query("SHOW TABLES LIKE 'order_addons'");
        if ($addonsCheck && $addonsCheck->rowCount() > 0) {
            $addonsQuery = "SELECT addon_name, addon_price FROM order_addons WHERE order_id = ?";
            $addonsStmt = $db->prepare($addonsQuery);
            $addonsStmt->execute([$order_id]);
            $order_addons = $addonsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // Addons table doesn't exist, continue without addons
    }

    // Generate PDF using the InvoicePDFGenerator
    $pdfGenerator = new InvoicePDFGenerator($order, $invoice, $order_items, $order_addons);
    $pdfContent = $pdfGenerator->generatePDF();
    
    // Set headers for PDF download (HTML format for now, can be converted to PDF by browser)
    header('Content-Type: text/html');
    header('Content-Disposition: inline; filename="Invoice-' . $invoice['invoice_number'] . '.html"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    echo $pdfContent;
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error generating invoice: ' . $e->getMessage()
    ]);
}

?>
