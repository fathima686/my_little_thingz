<?php
// Admin Workflow Manager API
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-ID");

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
    
    // Create tables if they don't exist
    $schemaFile = __DIR__ . "/../../database/admin-workflow-schema.sql";
    if (file_exists($schemaFile)) {
        $schema = file_get_contents($schemaFile);
        $statements = explode(';', $schema);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                } catch (Exception $e) {
                    // Continue if statement fails (might already exist)
                }
            }
        }
    }
    
    $method = $_SERVER["REQUEST_METHOD"];
    $action = $_GET["action"] ?? $_POST["action"] ?? "";
    
    switch ($method) {
        case "GET":
            handleGetRequest($pdo, $action);
            break;
        case "POST":
            handlePostRequest($pdo, $action);
            break;
        case "PUT":
            handlePutRequest($pdo, $action);
            break;
        default:
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

function handleGetRequest($pdo, $action) {
    switch ($action) {
        case "requests":
            getCustomRequests($pdo);
            break;
        case "request_details":
            getRequestDetails($pdo);
            break;
        case "categories":
            getProductCategories($pdo);
            break;
        case "workflow_progress":
            getWorkflowProgress($pdo);
            break;
        default:
            getCustomRequests($pdo);
    }
}

function handlePostRequest($pdo, $action) {
    switch ($action) {
        case "start_request":
            startRequest($pdo);
            break;
        case "update_stage":
            updateWorkflowStage($pdo);
            break;
        case "save_design":
            saveDesign($pdo);
            break;
        case "add_shipping":
            addShippingDetails($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Invalid action"]);
    }
}

function getCustomRequests($pdo) {
    $status = $_GET["status"] ?? "all";
    $stage = $_GET["stage"] ?? "all";
    $limit = min((int)($_GET["limit"] ?? 50), 100);
    $offset = max((int)($_GET["offset"] ?? 0), 0);
    
    $whereConditions = [];
    $params = [];
    
    if ($status !== "all") {
        $whereConditions[] = "cr.status = ?";
        $params[] = $status;
    }
    
    if ($stage !== "all") {
        $whereConditions[] = "cr.workflow_stage = ?";
        $params[] = $stage;
    }
    
    $whereClause = empty($whereConditions) ? "" : "WHERE " . implode(" AND ", $whereConditions);
    
    $query = "
        SELECT 
            cr.*,
            pc.name as category_name,
            pc.type as product_type,
            pc.requires_editor,
            au.full_name as admin_name,
            COUNT(cri.id) as image_count,
            crd.preview_image_url,
            sd.courier_service,
            sd.tracking_id
        FROM custom_requests cr
        LEFT JOIN product_categories pc ON cr.category = pc.name
        LEFT JOIN admin_users au ON cr.admin_id = au.id
        LEFT JOIN custom_request_images cri ON cr.id = cri.request_id
        LEFT JOIN custom_request_designs crd ON cr.id = crd.request_id
        LEFT JOIN shipping_details sd ON cr.id = sd.request_id
        $whereClause
        GROUP BY cr.id
        ORDER BY cr.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process each request
    foreach ($requests as &$request) {
        // Get images
        $request["images"] = getRequestImages($pdo, $request["id"]);
        
        // Get workflow progress
        $request["workflow_progress"] = getRequestWorkflowProgress($pdo, $request["id"]);
        
        // Calculate progress percentage
        $request["progress_percentage"] = calculateProgressPercentage($request["workflow_stage"], $request["product_type"]);
        
        // Format dates
        $request["created_at_formatted"] = date("M j, Y g:i A", strtotime($request["created_at"]));
        
        // Determine next action
        $request["next_action"] = determineNextAction($request["workflow_stage"], $request["requires_editor"]);
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(DISTINCT cr.id) FROM custom_requests cr $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute(array_slice($params, 0, -2));
    $totalCount = $countStmt->fetchColumn();
    
    echo json_encode([
        "status" => "success",
        "requests" => $requests,
        "total_count" => (int)$totalCount,
        "showing_count" => count($requests),
        "filter_applied" => ["status" => $status, "stage" => $stage]
    ]);
}

function getRequestDetails($pdo) {
    $requestId = $_GET["request_id"] ?? "";
    
    if (empty($requestId)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Request ID required"]);
        return;
    }
    
    $query = "
        SELECT 
            cr.*,
            pc.name as category_name,
            pc.type as product_type,
            pc.requires_editor,
            pc.description as category_description,
            au.full_name as admin_name,
            crd.design_data,
            crd.preview_image_url,
            crd.final_image_url,
            sd.courier_service,
            sd.tracking_id,
            sd.estimated_delivery,
            sd.shipping_address
        FROM custom_requests cr
        LEFT JOIN product_categories pc ON cr.category = pc.name
        LEFT JOIN admin_users au ON cr.admin_id = au.id
        LEFT JOIN custom_request_designs crd ON cr.id = crd.request_id
        LEFT JOIN shipping_details sd ON cr.id = sd.request_id
        WHERE cr.id = ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Request not found"]);
        return;
    }
    
    // Get images
    $request["images"] = getRequestImages($pdo, $requestId);
    
    // Get workflow progress
    $request["workflow_progress"] = getRequestWorkflowProgress($pdo, $requestId);
    
    // Get detailed progress timeline
    $request["progress_timeline"] = getProgressTimeline($pdo, $requestId);
    
    echo json_encode([
        "status" => "success",
        "request" => $request
    ]);
}

function startRequest($pdo) {
    $input = json_decode(file_get_contents("php://input"), true);
    $requestId = $input["request_id"] ?? "";
    $adminId = $input["admin_id"] ?? 1; // Default admin
    
    if (empty($requestId)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Request ID required"]);
        return;
    }
    
    // Get request details to determine workflow
    $stmt = $pdo->prepare("
        SELECT cr.*, pc.requires_editor, pc.type as product_type
        FROM custom_requests cr
        LEFT JOIN product_categories pc ON cr.category = pc.name
        WHERE cr.id = ?
    ");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Request not found"]);
        return;
    }
    
    // Determine next stage based on product type
    $nextStage = $request["requires_editor"] ? "in_design" : "in_crafting";
    
    // Update request
    $updateStmt = $pdo->prepare("
        UPDATE custom_requests 
        SET workflow_stage = ?, admin_id = ?, started_at = NOW(), updated_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$nextStage, $adminId, $requestId]);
    
    // Create workflow progress entry
    $progressStmt = $pdo->prepare("
        INSERT INTO workflow_progress (request_id, stage, status, started_at, admin_id)
        VALUES (?, ?, 'in_progress', NOW(), ?)
    ");
    $progressStmt->execute([$requestId, $nextStage, $adminId]);
    
    echo json_encode([
        "status" => "success",
        "message" => "Request started successfully",
        "next_stage" => $nextStage,
        "requires_editor" => (bool)$request["requires_editor"]
    ]);
}

function updateWorkflowStage($pdo) {
    $input = json_decode(file_get_contents("php://input"), true);
    $requestId = $input["request_id"] ?? "";
    $newStage = $input["stage"] ?? "";
    $adminId = $input["admin_id"] ?? 1;
    $notes = $input["notes"] ?? "";
    
    if (empty($requestId) || empty($newStage)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Request ID and stage required"]);
        return;
    }
    
    // Update request stage
    $updateStmt = $pdo->prepare("
        UPDATE custom_requests 
        SET workflow_stage = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$newStage, $requestId]);
    
    // Complete previous stage
    $completeStmt = $pdo->prepare("
        UPDATE workflow_progress 
        SET status = 'completed', completed_at = NOW()
        WHERE request_id = ? AND status = 'in_progress'
    ");
    $completeStmt->execute([$requestId]);
    
    // Create new stage entry
    $progressStmt = $pdo->prepare("
        INSERT INTO workflow_progress (request_id, stage, status, started_at, admin_id, notes)
        VALUES (?, ?, 'in_progress', NOW(), ?, ?)
    ");
    $progressStmt->execute([$requestId, $newStage, $adminId, $notes]);
    
    // Update specific timestamp fields
    $timestampField = getTimestampField($newStage);
    if ($timestampField) {
        $timestampStmt = $pdo->prepare("UPDATE custom_requests SET $timestampField = NOW() WHERE id = ?");
        $timestampStmt->execute([$requestId]);
    }
    
    echo json_encode([
        "status" => "success",
        "message" => "Workflow stage updated successfully",
        "new_stage" => $newStage
    ]);
}

// Helper functions
function getRequestImages($pdo, $requestId) {
    $stmt = $pdo->prepare("
        SELECT image_url, filename, original_filename, uploaded_at
        FROM custom_request_images
        WHERE request_id = ?
        ORDER BY uploaded_at ASC
    ");
    $stmt->execute([$requestId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $baseUrl = "http://localhost/my_little_thingz/backend/";
    foreach ($images as &$image) {
        $image["full_url"] = $baseUrl . $image["image_url"];
    }
    
    return $images;
}

function getRequestWorkflowProgress($pdo, $requestId) {
    $stmt = $pdo->prepare("
        SELECT stage, status, started_at, completed_at, notes
        FROM workflow_progress
        WHERE request_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$requestId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function calculateProgressPercentage($currentStage, $productType) {
    $stages = [
        'submitted' => 10,
        'in_design' => 30,
        'in_crafting' => 30,
        'design_completed' => 50,
        'packed' => 80,
        'courier_assigned' => 90,
        'delivered' => 100
    ];
    
    return $stages[$currentStage] ?? 0;
}

function determineNextAction($currentStage, $requiresEditor) {
    $actions = [
        'submitted' => $requiresEditor ? 'start_design' : 'start_crafting',
        'in_design' => 'complete_design',
        'in_crafting' => 'mark_crafted',
        'design_completed' => 'pack_item',
        'packed' => 'assign_courier',
        'courier_assigned' => 'mark_delivered',
        'delivered' => 'completed'
    ];
    
    return $actions[$currentStage] ?? 'unknown';
}

function getTimestampField($stage) {
    $fields = [
        'design_completed' => 'design_completed_at',
        'packed' => 'packed_at',
        'courier_assigned' => 'courier_assigned_at',
        'delivered' => 'delivered_at'
    ];
    
    return $fields[$stage] ?? null;
}

function getProductCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM product_categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "success",
        "categories" => $categories
    ]);
}

function getProgressTimeline($pdo, $requestId) {
    $stmt = $pdo->prepare("
        SELECT 
            wp.stage,
            wp.status,
            wp.started_at,
            wp.completed_at,
            wp.notes,
            au.full_name as admin_name
        FROM workflow_progress wp
        LEFT JOIN admin_users au ON wp.admin_id = au.id
        WHERE wp.request_id = ?
        ORDER BY wp.created_at ASC
    ");
    $stmt->execute([$requestId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>