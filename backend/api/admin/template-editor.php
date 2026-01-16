<?php
// Template Design Editor API
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-Id");

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
    $action = $_GET['action'] ?? '';
    
    switch ($method) {
        case 'GET':
            handleGet($pdo, $action);
            break;
        case 'POST':
            handlePost($pdo);
            break;
        case 'PUT':
            handlePut($pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}

function handleGet($pdo, $action) {
    switch ($action) {
        case 'design':
            getDesign($pdo);
            break;
        case 'export':
            exportDesign($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
}

function getDesign($pdo) {
    try {
        $requestId = $_GET['request_id'] ?? '';
        $designId = $_GET['design_id'] ?? '';
        
        if (!$requestId && !$designId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Request ID or Design ID required']);
            return;
        }
        
        $where = $designId ? "d.id = ?" : "d.request_id = ?";
        $param = $designId ?: $requestId;
        
        $stmt = $pdo->prepare("
            SELECT d.*, t.name as template_name, t.category as template_category,
                   r.title as request_title, r.customer_name, r.customer_email
            FROM custom_request_designs d
            LEFT JOIN design_templates t ON d.template_id = t.id
            LEFT JOIN custom_requests r ON d.request_id = r.id
            WHERE $where
            ORDER BY d.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$param]);
        $design = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$design) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Design not found']);
            return;
        }
        
        // Parse JSON design data
        $design['design_data'] = json_decode($design['design_data'], true);
        
        // Get request images if available
        $images = [];
        if ($design['request_id']) {
            $imageStmt = $pdo->prepare("
                SELECT image_url, filename, original_filename 
                FROM custom_request_images 
                WHERE request_id = ? 
                ORDER BY uploaded_at
            ");
            $imageStmt->execute([$design['request_id']]);
            $images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode([
            'status' => 'success',
            'design' => $design,
            'images' => $images
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function handlePost($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'save':
                saveDesign($pdo, $input);
                break;
            case 'save-as-template':
                saveAsTemplate($pdo, $input);
                break;
            case 'complete':
                completeDesign($pdo, $input);
                break;
            default:
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function saveDesign($pdo, $input) {
    try {
        $designId = $input['design_id'] ?? '';
        $requestId = $input['request_id'] ?? '';
        $designData = $input['design_data'] ?? [];
        $previewUrl = $input['preview_url'] ?? '';
        
        if (!$designData) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Design data required']);
            return;
        }
        
        if ($designId) {
            // Update existing design
            $stmt = $pdo->prepare("
                UPDATE custom_request_designs 
                SET design_data = ?, preview_image_url = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([
                json_encode($designData),
                $previewUrl,
                $designId
            ]);
        } else if ($requestId) {
            // Create new design
            $stmt = $pdo->prepare("
                INSERT INTO custom_request_designs (
                    request_id, design_data, preview_image_url, created_by
                ) VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $requestId,
                json_encode($designData),
                $previewUrl,
                $_SERVER['HTTP_X_ADMIN_USER_ID'] ?? null
            ]);
            $designId = $pdo->lastInsertId();
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Design ID or Request ID required']);
            return;
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Design saved successfully',
            'design_id' => $designId
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function saveAsTemplate($pdo, $input) {
    try {
        $designId = $input['design_id'] ?? '';
        $templateName = $input['template_name'] ?? '';
        $templateCategory = $input['template_category'] ?? '';
        $templateDescription = $input['template_description'] ?? '';
        $isPublic = $input['is_public'] ?? true;
        
        if (!$designId || !$templateName || !$templateCategory) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Design ID, template name, and category required']);
            return;
        }
        
        // Get design data
        $stmt = $pdo->prepare("SELECT design_data FROM custom_request_designs WHERE id = ?");
        $stmt->execute([$designId]);
        $design = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$design) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Design not found']);
            return;
        }
        
        $designData = json_decode($design['design_data'], true);
        
        // Extract canvas dimensions from design data
        $canvasWidth = $designData['canvas']['width'] ?? 800;
        $canvasHeight = $designData['canvas']['height'] ?? 600;
        
        // Create template
        $templateStmt = $pdo->prepare("
            INSERT INTO design_templates (
                name, description, category, canvas_width, canvas_height,
                template_data, is_public, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $templateStmt->execute([
            $templateName,
            $templateDescription,
            $templateCategory,
            $canvasWidth,
            $canvasHeight,
            $design['design_data'],
            $isPublic,
            $_SERVER['HTTP_X_ADMIN_USER_ID'] ?? null
        ]);
        
        $templateId = $pdo->lastInsertId();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Template created successfully',
            'template_id' => $templateId
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function completeDesign($pdo, $input) {
    try {
        $designId = $input['design_id'] ?? '';
        $requestId = $input['request_id'] ?? '';
        $finalImageUrl = $input['final_image_url'] ?? '';
        $exportFormat = $input['export_format'] ?? 'png';
        $exportQuality = $input['export_quality'] ?? 'standard';
        
        if (!$designId && !$requestId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Design ID or Request ID required']);
            return;
        }
        
        $pdo->beginTransaction();
        
        try {
            // Update design with final image
            if ($designId) {
                $stmt = $pdo->prepare("
                    UPDATE custom_request_designs 
                    SET final_image_url = ?, export_format = ?, export_quality = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$finalImageUrl, $exportFormat, $exportQuality, $designId]);
                
                // Get request ID from design
                $reqStmt = $pdo->prepare("SELECT request_id FROM custom_request_designs WHERE id = ?");
                $reqStmt->execute([$designId]);
                $requestId = $reqStmt->fetchColumn();
            }
            
            // Update request status to design completed
            if ($requestId) {
                $updateStmt = $pdo->prepare("
                    UPDATE custom_requests 
                    SET status = 'completed', design_url = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $updateStmt->execute([$finalImageUrl, $requestId]);
                
                // Update workflow stage if workflow table exists
                $workflowStmt = $pdo->prepare("
                    INSERT INTO workflow_progress (request_id, stage, status, completed_at, admin_id)
                    VALUES (?, 'design_completed', 'completed', NOW(), ?)
                    ON DUPLICATE KEY UPDATE 
                    status = 'completed', completed_at = NOW(), admin_id = ?
                ");
                $workflowStmt->execute([
                    $requestId, 
                    $_SERVER['HTTP_X_ADMIN_USER_ID'] ?? null,
                    $_SERVER['HTTP_X_ADMIN_USER_ID'] ?? null
                ]);
            }
            
            $pdo->commit();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Design completed successfully',
                'request_id' => $requestId,
                'final_image_url' => $finalImageUrl
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function handlePut($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        saveDesign($pdo, $input);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function exportDesign($pdo) {
    try {
        $designId = $_GET['design_id'] ?? '';
        $format = $_GET['format'] ?? 'png';
        $quality = $_GET['quality'] ?? 'standard';
        
        if (!$designId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Design ID required']);
            return;
        }
        
        // Get design data
        $stmt = $pdo->prepare("
            SELECT d.*, r.title as request_title, r.customer_name
            FROM custom_request_designs d
            LEFT JOIN custom_requests r ON d.request_id = r.id
            WHERE d.id = ?
        ");
        $stmt->execute([$designId]);
        $design = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$design) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Design not found']);
            return;
        }
        
        // Generate export filename
        $timestamp = date('Y-m-d_H-i-s');
        $customerName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $design['customer_name'] ?? 'design');
        $filename = "design_{$customerName}_{$timestamp}.{$format}";
        
        echo json_encode([
            'status' => 'success',
            'design' => [
                'id' => $design['id'],
                'design_data' => json_decode($design['design_data'], true),
                'export_filename' => $filename,
                'export_format' => $format,
                'export_quality' => $quality
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>