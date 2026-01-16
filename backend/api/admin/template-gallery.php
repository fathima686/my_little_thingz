<?php
// Template Gallery Management API
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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
    
    // Initialize tables if they don't exist
    $schemaFile = __DIR__ . '/../../database/template-gallery-schema.sql';
    if (file_exists($schemaFile)) {
        $sql = file_get_contents($schemaFile);
        $pdo->exec($sql);
    }
    
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
        case 'DELETE':
            handleDelete($pdo);
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
        case 'categories':
            getCategories($pdo);
            break;
        case 'templates':
            getTemplates($pdo);
            break;
        case 'template':
            getTemplate($pdo);
            break;
        case 'usage-stats':
            getUsageStats($pdo);
            break;
        default:
            getTemplateGallery($pdo);
    }
}

function getCategories($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT c.*, 
                   COUNT(t.id) as template_count
            FROM template_categories c
            LEFT JOIN design_templates t ON c.name = t.category AND t.is_public = 1
            WHERE c.is_active = 1
            GROUP BY c.id
            ORDER BY c.sort_order, c.name
        ");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'categories' => $categories
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function getTemplates($pdo) {
    try {
        $category = $_GET['category'] ?? '';
        $search = $_GET['search'] ?? '';
        $featured = $_GET['featured'] ?? '';
        $limit = min((int)($_GET['limit'] ?? 50), 100);
        $offset = max((int)($_GET['offset'] ?? 0), 0);
        
        $where = ["t.is_public = 1"];
        $params = [];
        
        if ($category) {
            $where[] = "t.category = ?";
            $params[] = $category;
        }
        
        if ($search) {
            $where[] = "(t.name LIKE ? OR t.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($featured === '1') {
            $where[] = "t.is_featured = 1";
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   COALESCE(u.usage_count, 0) as usage_count
            FROM design_templates t
            LEFT JOIN (
                SELECT template_id, COUNT(*) as usage_count 
                FROM template_usage 
                GROUP BY template_id
            ) u ON t.id = u.template_id
            WHERE $whereClause
            ORDER BY t.is_featured DESC, t.usage_count DESC, t.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON template_data for each template
        foreach ($templates as &$template) {
            $template['template_data'] = json_decode($template['template_data'], true);
        }
        
        // Get total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM design_templates t WHERE $whereClause");
        $countStmt->execute(array_slice($params, 0, -2));
        $total = $countStmt->fetchColumn();
        
        echo json_encode([
            'status' => 'success',
            'templates' => $templates,
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function getTemplate($pdo) {
    try {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Template ID required']);
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   COALESCE(u.usage_count, 0) as usage_count
            FROM design_templates t
            LEFT JOIN (
                SELECT template_id, COUNT(*) as usage_count 
                FROM template_usage 
                GROUP BY template_id
            ) u ON t.id = u.template_id
            WHERE t.id = ? AND t.is_public = 1
        ");
        $stmt->execute([$id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Template not found']);
            return;
        }
        
        $template['template_data'] = json_decode($template['template_data'], true);
        
        echo json_encode([
            'status' => 'success',
            'template' => $template
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function getTemplateGallery($pdo) {
    try {
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        
        // Get categories with template counts
        $categoriesStmt = $pdo->query("
            SELECT c.*, COUNT(t.id) as template_count
            FROM template_categories c
            LEFT JOIN design_templates t ON c.name = t.category AND t.is_public = 1
            WHERE c.is_active = 1
            GROUP BY c.id
            ORDER BY c.sort_order, c.name
        ");
        $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get templates grouped by category
        $where = ["t.is_public = 1"];
        $params = [];
        
        if ($search) {
            $where[] = "(t.name LIKE ? OR t.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($category) {
            $where[] = "t.category = ?";
            $params[] = $category;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $templatesStmt = $pdo->prepare("
            SELECT t.id, t.name, t.description, t.category, t.canvas_width, t.canvas_height,
                   t.preview_image_url, t.thumbnail_url, t.is_featured, t.created_at,
                   COALESCE(u.usage_count, 0) as usage_count
            FROM design_templates t
            LEFT JOIN (
                SELECT template_id, COUNT(*) as usage_count 
                FROM template_usage 
                GROUP BY template_id
            ) u ON t.id = u.template_id
            WHERE $whereClause
            ORDER BY t.category, t.is_featured DESC, t.usage_count DESC, t.name
        ");
        $templatesStmt->execute($params);
        $templates = $templatesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group templates by category
        $grouped = [];
        foreach ($templates as $template) {
            $cat = $template['category'] ?: 'Other';
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = $template;
        }
        
        echo json_encode([
            'status' => 'success',
            'categories' => $categories,
            'templates' => $templates,
            'grouped' => $grouped,
            'search' => $search,
            'selected_category' => $category
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
            case 'create':
                createTemplate($pdo, $input);
                break;
            case 'use':
                useTemplate($pdo, $input);
                break;
            case 'duplicate':
                duplicateTemplate($pdo, $input);
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

function createTemplate($pdo, $input) {
    try {
        $required = ['name', 'category', 'canvas_width', 'canvas_height', 'template_data'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "Field '$field' is required"]);
                return;
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO design_templates (
                name, description, category, canvas_width, canvas_height, 
                template_data, is_public, is_featured, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['name'],
            $input['description'] ?? '',
            $input['category'],
            $input['canvas_width'],
            $input['canvas_height'],
            json_encode($input['template_data']),
            $input['is_public'] ?? true,
            $input['is_featured'] ?? false,
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

function useTemplate($pdo, $input) {
    try {
        $templateId = $input['template_id'] ?? '';
        $requestId = $input['request_id'] ?? '';
        
        if (!$templateId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Template ID required']);
            return;
        }
        
        // Get template
        $stmt = $pdo->prepare("SELECT * FROM design_templates WHERE id = ? AND is_public = 1");
        $stmt->execute([$templateId]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Template not found']);
            return;
        }
        
        // Create design record if request_id provided
        $designId = null;
        if ($requestId) {
            $designStmt = $pdo->prepare("
                INSERT INTO custom_request_designs (
                    request_id, template_id, design_data, created_by
                ) VALUES (?, ?, ?, ?)
            ");
            $designStmt->execute([
                $requestId,
                $templateId,
                $template['template_data'],
                $_SERVER['HTTP_X_ADMIN_USER_ID'] ?? null
            ]);
            $designId = $pdo->lastInsertId();
        }
        
        // Track usage
        $usageStmt = $pdo->prepare("
            INSERT INTO template_usage (template_id, request_id, user_id, user_type) 
            VALUES (?, ?, ?, ?)
        ");
        $usageStmt->execute([
            $templateId,
            $requestId,
            $_SERVER['HTTP_X_ADMIN_USER_ID'] ?? null,
            'admin'
        ]);
        
        // Update usage count
        $pdo->prepare("UPDATE design_templates SET usage_count = usage_count + 1 WHERE id = ?")
            ->execute([$templateId]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Template applied successfully',
            'design_id' => $designId,
            'template' => [
                'id' => $template['id'],
                'name' => $template['name'],
                'template_data' => json_decode($template['template_data'], true)
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function handlePut($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Template ID required']);
            return;
        }
        
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['name', 'description', 'category', 'template_data', 'is_public', 'is_featured'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $field === 'template_data' ? json_encode($input[$field]) : $input[$field];
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
            return;
        }
        
        $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
        $params[] = $id;
        
        $stmt = $pdo->prepare("
            UPDATE design_templates 
            SET " . implode(', ', $updateFields) . "
            WHERE id = ?
        ");
        $stmt->execute($params);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Template updated successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function handleDelete($pdo) {
    try {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Template ID required']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM design_templates WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Template not found']);
            return;
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Template deleted successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function getUsageStats($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                t.category,
                COUNT(DISTINCT t.id) as template_count,
                COUNT(u.id) as total_usage,
                AVG(t.usage_count) as avg_usage_per_template
            FROM design_templates t
            LEFT JOIN template_usage u ON t.id = u.template_id
            WHERE t.is_public = 1
            GROUP BY t.category
            ORDER BY total_usage DESC
        ");
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'stats' => $stats
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>