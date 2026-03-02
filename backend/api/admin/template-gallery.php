<?php
/**
 * Template Gallery API
 * Handles template management for the Canva-style editor
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-User-Id');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';
require_once '../../config/env-loader.php';

class TemplateGalleryAPI {
    private $db;
    private $admin_user_id;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->admin_user_id = $_SERVER['HTTP_X_ADMIN_USER_ID'] ?? 1;
    }
    
    public function handleRequest() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $action = $_GET['action'] ?? '';
            
            switch ($method) {
                case 'GET':
                    return $this->handleGet($action);
                case 'POST':
                    return $this->handlePost();
                case 'PUT':
                    return $this->handlePut();
                case 'DELETE':
                    return $this->handleDelete();
                default:
                    throw new Exception('Method not allowed');
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    private function handleGet($action) {
        switch ($action) {
            case 'list':
                return $this->getTemplates();
            case 'template':
                $id = $_GET['id'] ?? '';
                return $this->getTemplate($id);
            case 'categories':
                return $this->getCategories();
            default:
                return $this->getTemplates();
        }
    }
    
    private function handlePost() {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'save_template':
                return $this->saveTemplate($input);
            case 'duplicate_template':
                return $this->duplicateTemplate($input);
            default:
                throw new Exception('Invalid action');
        }
    }
    
    private function handlePut() {
        $input = json_decode(file_get_contents('php://input'), true);
        return $this->updateTemplate($input);
    }
    
    private function handleDelete() {
        $id = $_GET['id'] ?? '';
        return $this->deleteTemplate($id);
    }
    
    /**
     * Get all templates with optional filtering
     */
    private function getTemplates() {
        $category = $_GET['category'] ?? '';
        $search = $_GET['search'] ?? '';
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);
        
        $sql = "SELECT 
                    id,
                    name,
                    category,
                    description,
                    thumbnail_path,
                    template_data,
                    is_active,
                    created_at,
                    updated_at
                FROM design_templates 
                WHERE is_active = 1";
        
        $params = [];
        
        if (!empty($category) && $category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        if (!empty($search)) {
            $sql .= " AND (name LIKE ? OR description LIKE ? OR category LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process templates
        foreach ($templates as &$template) {
            $template['template_data'] = json_decode($template['template_data'], true);
            
            // Generate thumbnail URL if path exists
            if ($template['thumbnail_path']) {
                $template['thumbnail'] = $this->getFullUrl($template['thumbnail_path']);
            } else {
                $template['thumbnail'] = $this->generateDefaultThumbnail($template);
            }
        }
        
        return $this->successResponse([
            'templates' => $templates,
            'total' => $this->getTotalTemplatesCount($category, $search)
        ]);
    }
    
    /**
     * Get a specific template by ID
     */
    private function getTemplate($id) {
        if (empty($id)) {
            throw new Exception('Template ID is required');
        }
        
        $sql = "SELECT 
                    id,
                    name,
                    category,
                    description,
                    thumbnail_path,
                    template_data,
                    is_active,
                    created_at,
                    updated_at
                FROM design_templates 
                WHERE id = ? AND is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            throw new Exception('Template not found');
        }
        
        $template['template_data'] = json_decode($template['template_data'], true);
        
        if ($template['thumbnail_path']) {
            $template['thumbnail'] = $this->getFullUrl($template['thumbnail_path']);
        }
        
        return $this->successResponse(['template' => $template]);
    }
    
    /**
     * Get available categories
     */
    private function getCategories() {
        $sql = "SELECT DISTINCT category, COUNT(*) as count 
                FROM design_templates 
                WHERE is_active = 1 
                GROUP BY category 
                ORDER BY category";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $this->successResponse(['categories' => $categories]);
    }
    
    /**
     * Save a new template
     */
    private function saveTemplate($data) {
        $required = ['name', 'category', 'template_data'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '{$field}' is required");
            }
        }
        
        // Validate category
        $validCategories = ['birthday', 'name-frame', 'quotes', 'anniversary', 'other'];
        if (!in_array($data['category'], $validCategories)) {
            $data['category'] = 'other';
        }
        
        // Save thumbnail if provided
        $thumbnailPath = null;
        if (!empty($data['thumbnail'])) {
            $thumbnailPath = $this->saveThumbnail($data['thumbnail'], $data['name']);
        }
        
        $sql = "INSERT INTO design_templates 
                (name, category, description, thumbnail_path, template_data, created_by, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            $data['name'],
            $data['category'],
            $data['description'] ?? '',
            $thumbnailPath,
            json_encode($data['template_data']),
            $this->admin_user_id
        ]);
        
        if (!$success) {
            throw new Exception('Failed to save template');
        }
        
        $templateId = $this->db->lastInsertId();
        
        return $this->successResponse([
            'message' => 'Template saved successfully',
            'template_id' => $templateId
        ]);
    }
    
    /**
     * Update an existing template
     */
    private function updateTemplate($data) {
        if (empty($data['id'])) {
            throw new Exception('Template ID is required');
        }
        
        // Check if template exists
        $stmt = $this->db->prepare("SELECT id FROM design_templates WHERE id = ? AND is_active = 1");
        $stmt->execute([$data['id']]);
        if (!$stmt->fetch()) {
            throw new Exception('Template not found');
        }
        
        $updateFields = [];
        $params = [];
        
        if (!empty($data['name'])) {
            $updateFields[] = "name = ?";
            $params[] = $data['name'];
        }
        
        if (!empty($data['category'])) {
            $updateFields[] = "category = ?";
            $params[] = $data['category'];
        }
        
        if (isset($data['description'])) {
            $updateFields[] = "description = ?";
            $params[] = $data['description'];
        }
        
        if (!empty($data['template_data'])) {
            $updateFields[] = "template_data = ?";
            $params[] = json_encode($data['template_data']);
        }
        
        if (!empty($data['thumbnail'])) {
            $thumbnailPath = $this->saveThumbnail($data['thumbnail'], $data['name'] ?? 'template');
            $updateFields[] = "thumbnail_path = ?";
            $params[] = $thumbnailPath;
        }
        
        if (empty($updateFields)) {
            throw new Exception('No fields to update');
        }
        
        $updateFields[] = "updated_at = NOW()";
        $params[] = $data['id'];
        
        $sql = "UPDATE design_templates SET " . implode(', ', $updateFields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute($params);
        
        if (!$success) {
            throw new Exception('Failed to update template');
        }
        
        return $this->successResponse(['message' => 'Template updated successfully']);
    }
    
    /**
     * Delete a template (soft delete)
     */
    private function deleteTemplate($id) {
        if (empty($id)) {
            throw new Exception('Template ID is required');
        }
        
        $sql = "UPDATE design_templates SET is_active = 0, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([$id]);
        
        if (!$success) {
            throw new Exception('Failed to delete template');
        }
        
        return $this->successResponse(['message' => 'Template deleted successfully']);
    }
    
    /**
     * Duplicate a template
     */
    private function duplicateTemplate($data) {
        if (empty($data['id'])) {
            throw new Exception('Template ID is required');
        }
        
        // Get original template
        $stmt = $this->db->prepare("SELECT * FROM design_templates WHERE id = ? AND is_active = 1");
        $stmt->execute([$data['id']]);
        $original = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$original) {
            throw new Exception('Template not found');
        }
        
        // Create duplicate
        $newName = $data['name'] ?? ($original['name'] . ' (Copy)');
        
        $sql = "INSERT INTO design_templates 
                (name, category, description, thumbnail_path, template_data, created_by, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            $newName,
            $original['category'],
            $original['description'],
            $original['thumbnail_path'], // Could copy thumbnail file too
            $original['template_data'],
            $this->admin_user_id
        ]);
        
        if (!$success) {
            throw new Exception('Failed to duplicate template');
        }
        
        $templateId = $this->db->lastInsertId();
        
        return $this->successResponse([
            'message' => 'Template duplicated successfully',
            'template_id' => $templateId
        ]);
    }
    
    /**
     * Save thumbnail image to file system
     */
    private function saveThumbnail($base64Data, $templateName) {
        // Extract base64 data
        if (strpos($base64Data, 'data:image/') === 0) {
            $data = explode(',', $base64Data);
            $imageData = base64_decode($data[1]);
            
            // Get image type
            $mimeType = explode(';', explode(':', $data[0])[1])[0];
            $extension = $mimeType === 'image/jpeg' ? 'jpg' : 'png';
        } else {
            $imageData = base64_decode($base64Data);
            $extension = 'png';
        }
        
        // Create thumbnails directory if it doesn't exist
        $uploadDir = '../../uploads/templates/thumbnails/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $filename = 'template_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Save file
        if (file_put_contents($filepath, $imageData) === false) {
            throw new Exception('Failed to save thumbnail');
        }
        
        return 'uploads/templates/thumbnails/' . $filename;
    }
    
    /**
     * Generate default thumbnail for templates without custom thumbnails
     */
    private function generateDefaultThumbnail($template) {
        // Return a data URL for a simple SVG placeholder
        $category = ucfirst(str_replace('-', ' ', $template['category']));
        $name = htmlspecialchars($template['name']);
        
        $svg = '<svg width="200" height="120" xmlns="http://www.w3.org/2000/svg">
                    <rect width="100%" height="100%" fill="#f3f4f6"/>
                    <text x="50%" y="40%" font-family="Arial" font-size="12" fill="#6b7280" text-anchor="middle" dy=".3em">' . $category . '</text>
                    <text x="50%" y="65%" font-family="Arial" font-size="10" fill="#374151" text-anchor="middle" dy=".3em">' . $name . '</text>
                </svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    /**
     * Get total count of templates for pagination
     */
    private function getTotalTemplatesCount($category = '', $search = '') {
        $sql = "SELECT COUNT(*) FROM design_templates WHERE is_active = 1";
        $params = [];
        
        if (!empty($category) && $category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        if (!empty($search)) {
            $sql .= " AND (name LIKE ? OR description LIKE ? OR category LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Get full URL for relative paths
     */
    private function getFullUrl($relativePath) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . '://' . $host . '/my_little_thingz/backend/';
        return $baseUrl . $relativePath;
    }
    
    /**
     * Return success response
     */
    private function successResponse($data) {
        return [
            'status' => 'success',
            'data' => $data
        ];
    }
    
    /**
     * Return error response
     */
    private function errorResponse($message, $code = 400) {
        http_response_code($code);
        return [
            'status' => 'error',
            'message' => $message
        ];
    }
}

// Initialize and handle request
$api = new TemplateGalleryAPI();
$response = $api->handleRequest();

echo json_encode($response);
?>