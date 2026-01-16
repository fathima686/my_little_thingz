<?php
/**
 * Admin Image Review API V2
 * 
 * Endpoints:
 * - GET: Fetch pending reviews
 * - POST: Submit admin decision (approve/reject)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Email, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once '../../config/database.php';
    require_once '../../config/env-loader.php';
    require_once '../../services/EnhancedImageAuthenticityServiceV2.php';
    
    // Load environment variables
    EnvLoader::load();
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    $localClassifierUrl = getenv('LOCAL_CLASSIFIER_URL') ?: $_ENV['LOCAL_CLASSIFIER_URL'] ?? 'http://localhost:5000';
    $authenticityService = new EnhancedImageAuthenticityServiceV2($pdo, $localClassifierUrl);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'System initialization failed: ' . $e->getMessage()
    ]);
    exit;
}

// Verify admin authentication
$adminEmail = $_SERVER['HTTP_X_ADMIN_EMAIL'] ?? $_POST['admin_email'] ?? '';

if (empty($adminEmail)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Admin authentication required'
    ]);
    exit;
}

try {
    // Verify admin user
    $adminStmt = $pdo->prepare("SELECT id, role FROM users WHERE email = ? AND role = 'admin'");
    $adminStmt->execute([$adminEmail]);
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Unauthorized: Admin access required'
        ]);
        exit;
    }
    
    $adminId = $admin['id'];
    
    // Handle GET request - Fetch pending reviews
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $status = $_GET['status'] ?? 'pending';
        $category = $_GET['category'] ?? null;
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);
        
        $query = "
            SELECT 
                ar.id as review_id,
                ar.image_id,
                ar.image_type,
                ar.user_id,
                ar.tutorial_id,
                ar.category,
                ar.evaluation_status,
                ar.flagged_reason,
                ar.similar_image_info,
                ar.ai_warning,
                ar.admin_decision,
                ar.admin_notes,
                ar.reviewed_by,
                ar.reviewed_at,
                ar.flagged_at,
                u.first_name,
                u.last_name,
                u.email as user_email,
                t.title as tutorial_title,
                ia.phash,
                ia.metadata_notes,
                ia.ai_labels,
                pu.images as practice_images,
                pu.description as practice_description
            FROM admin_review_v2 ar
            LEFT JOIN users u ON ar.user_id = u.id
            LEFT JOIN tutorials t ON ar.tutorial_id = t.id
            LEFT JOIN image_authenticity_v2 ia ON ar.image_id = ia.image_id AND ar.image_type = ia.image_type
            LEFT JOIN practice_uploads pu ON ar.tutorial_id = pu.tutorial_id AND ar.user_id = pu.user_id
            WHERE ar.admin_decision = ?
        ";
        
        $params = [$status];
        
        if ($category) {
            $query .= " AND ar.category = ?";
            $params[] = $category;
        }
        
        $query .= " ORDER BY ar.flagged_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM admin_review_v2 WHERE admin_decision = ?";
        $countParams = [$status];
        
        if ($category) {
            $countQuery .= " AND category = ?";
            $countParams[] = $category;
        }
        
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($countParams);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Process reviews to include image URLs
        foreach ($reviews as &$review) {
            if ($review['practice_images']) {
                $images = json_decode($review['practice_images'], true);
                $review['image_urls'] = array_map(function($img) {
                    return [
                        'url' => '../../' . $img['file_path'],
                        'original_name' => $img['original_name'],
                        'file_size' => $img['file_size']
                    ];
                }, $images);
            }
            
            if ($review['similar_image_info']) {
                $review['similar_image_info'] = json_decode($review['similar_image_info'], true);
            }
            
            if ($review['ai_labels']) {
                $review['ai_labels'] = json_decode($review['ai_labels'], true);
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'reviews' => $reviews,
                'total_count' => $totalCount,
                'current_page' => floor($offset / $limit) + 1,
                'per_page' => $limit,
                'has_more' => ($offset + $limit) < $totalCount
            ]
        ]);
        exit;
    }
    
    // Handle POST request - Submit admin decision
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $imageId = $_POST['image_id'] ?? '';
        $imageType = $_POST['image_type'] ?? 'practice_upload';
        $decision = $_POST['decision'] ?? '';
        $adminNotes = $_POST['admin_notes'] ?? '';
        
        if (empty($imageId) || empty($decision)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'image_id and decision are required'
            ]);
            exit;
        }
        
        if (!in_array($decision, ['approved', 'rejected'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid decision. Must be "approved" or "rejected"'
            ]);
            exit;
        }
        
        // Update admin decision
        $success = $authenticityService->updateAdminDecision($imageId, $imageType, $decision, $adminId, $adminNotes);
        
        if ($success) {
            echo json_encode([
                'status' => 'success',
                'message' => "Image {$decision} successfully",
                'data' => [
                    'image_id' => $imageId,
                    'decision' => $decision,
                    'reviewed_by' => $adminId,
                    'reviewed_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update admin decision'
            ]);
        }
        exit;
    }
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    
} catch (Exception $e) {
    error_log("Admin review error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Operation failed: ' . $e->getMessage()
    ]);
}
?>
