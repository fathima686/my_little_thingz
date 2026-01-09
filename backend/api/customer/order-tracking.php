<?php
// Customer Order Tracking API
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

ini_set("display_errors", 0);
error_reporting(0);

try {
    require_once "../../config/database.php";
    $database = new Database();
    $pdo = $database->getConnection();
    
    $method = $_SERVER["REQUEST_METHOD"];
    
    if ($method === "GET") {
        $orderId = $_GET["order_id"] ?? "";
        $customerEmail = $_GET["customer_email"] ?? "";
        
        if (empty($orderId) && empty($customerEmail)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Order ID or customer email required"]);
            exit;
        }
        
        // Build query based on provided parameters
        $whereConditions = [];
        $params = [];
        
        if (!empty($orderId)) {
            $whereConditions[] = "cr.order_id = ?";
            $params[] = $orderId;
        }
        
        if (!empty($customerEmail)) {
            $whereConditions[] = "cr.customer_email = ?";
            $params[] = $customerEmail;
        }
        
        $whereClause = "WHERE " . implode(" AND ", $whereConditions);
        
        $query = "
            SELECT 
                cr.id,
                cr.order_id,
                cr.title,
                cr.customer_name,
                cr.customer_email,
                cr.description,
                cr.workflow_stage,
                cr.created_at,
                cr.started_at,
                cr.design_completed_at,
                cr.packed_at,
                cr.courier_assigned_at,
                cr.delivered_at,
                pc.name as category_name,
                pc.type as product_type,
                pc.requires_editor,
                sd.courier_service,
                sd.tracking_id,
                sd.estimated_delivery,
                sd.shipping_address,
                crd.preview_image_url,
                crd.final_image_url
            FROM custom_requests cr
            LEFT JOIN product_categories pc ON cr.category = pc.name
            LEFT JOIN shipping_details sd ON cr.id = sd.request_id
            LEFT JOIN custom_request_designs crd ON cr.id = crd.request_id
            $whereClause
            ORDER BY cr.created_at DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($orders)) {
            echo json_encode([
                "status" => "error",
                "message" => "No orders found with the provided criteria"
            ]);
            exit;
        }
        
        // Process each order
        foreach ($orders as &$order) {
            // Get images
            $imageStmt = $pdo->prepare("
                SELECT image_url, filename, original_filename, uploaded_at
                FROM custom_request_images
                WHERE request_id = ?
                ORDER BY uploaded_at ASC
            ");
            $imageStmt->execute([$order["id"]]);
            $images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $baseUrl = "http://localhost/my_little_thingz/backend/";
            foreach ($images as &$image) {
                $image["full_url"] = $baseUrl . $image["image_url"];
            }
            $order["images"] = $images;
            
            // Get detailed progress timeline
            $progressStmt = $pdo->prepare("
                SELECT 
                    stage,
                    status,
                    started_at,
                    completed_at,
                    notes
                FROM workflow_progress
                WHERE request_id = ?
                ORDER BY created_at ASC
            ");
            $progressStmt->execute([$order["id"]]);
            $order["progress_timeline"] = $progressStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate progress percentage
            $order["progress_percentage"] = calculateProgressPercentage($order["workflow_stage"]);
            
            // Get current stage info
            $order["current_stage_info"] = getCurrentStageInfo($order["workflow_stage"]);
            
            // Get estimated completion
            $order["estimated_completion"] = getEstimatedCompletion($order["workflow_stage"], $order["created_at"]);
            
            // Format dates
            $order["created_at_formatted"] = date("M j, Y g:i A", strtotime($order["created_at"]));
            if ($order["estimated_delivery"]) {
                $order["estimated_delivery_formatted"] = date("M j, Y", strtotime($order["estimated_delivery"]));
            }
        }
        
        echo json_encode([
            "status" => "success",
            "orders" => $orders,
            "total_orders" => count($orders),
            "message" => "Orders retrieved successfully"
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}

function calculateProgressPercentage($currentStage) {
    $stages = [
        'submitted' => 10,
        'in_design' => 25,
        'in_crafting' => 25,
        'design_completed' => 50,
        'packed' => 75,
        'courier_assigned' => 90,
        'delivered' => 100
    ];
    
    return $stages[$currentStage] ?? 0;
}

function getCurrentStageInfo($stage) {
    $stageInfo = [
        'submitted' => [
            'title' => 'Order Submitted',
            'description' => 'Your custom request has been submitted and is waiting to be processed by our team.',
            'icon' => '📝',
            'color' => '#ffc107'
        ],
        'in_design' => [
            'title' => 'Design in Progress',
            'description' => 'Our designers are working on creating your custom design based on your requirements.',
            'icon' => '🎨',
            'color' => '#17a2b8'
        ],
        'in_crafting' => [
            'title' => 'Crafting in Progress',
            'description' => 'Your item is being handcrafted by our skilled artisans.',
            'icon' => '🔨',
            'color' => '#6f42c1'
        ],
        'design_completed' => [
            'title' => 'Design Completed',
            'description' => 'The design phase is complete and your item is ready for final production.',
            'icon' => '✅',
            'color' => '#28a745'
        ],
        'packed' => [
            'title' => 'Packed & Ready',
            'description' => 'Your order has been completed and packed, ready for shipping.',
            'icon' => '📦',
            'color' => '#fd7e14'
        ],
        'courier_assigned' => [
            'title' => 'Out for Delivery',
            'description' => 'Your order has been picked up by the courier and is on its way to you.',
            'icon' => '🚚',
            'color' => '#20c997'
        ],
        'delivered' => [
            'title' => 'Delivered',
            'description' => 'Your order has been successfully delivered. Thank you for choosing us!',
            'icon' => '🎉',
            'color' => '#28a745'
        ]
    ];
    
    return $stageInfo[$stage] ?? [
        'title' => 'Unknown Stage',
        'description' => 'Status information not available.',
        'icon' => '❓',
        'color' => '#6c757d'
    ];
}

function getEstimatedCompletion($stage, $createdAt) {
    $estimatedDays = [
        'submitted' => 1,
        'in_design' => 3,
        'in_crafting' => 5,
        'design_completed' => 1,
        'packed' => 1,
        'courier_assigned' => 3,
        'delivered' => 0
    ];
    
    $daysToAdd = $estimatedDays[$stage] ?? 7;
    $estimatedDate = date('Y-m-d', strtotime($createdAt . " + $daysToAdd days"));
    
    return [
        'date' => $estimatedDate,
        'formatted' => date('M j, Y', strtotime($estimatedDate)),
        'days_remaining' => max(0, ceil((strtotime($estimatedDate) - time()) / (60 * 60 * 24)))
    ];
}
?>