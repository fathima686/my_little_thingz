<?php
/**
 * Admin Gift Classifier Management
 * Manage classification confidence thresholds, review suggestions
 * GET: Get classification stats
 * POST: Apply bulk classifications, adjust thresholds
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
$mysqli->set_charset('utf8mb4');

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

require_once __DIR__ . '/../../services/GiftCategoryClassifier.php';
$classifier = new GiftCategoryClassifier($mysqli);

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Get classification statistics
        $action = $_GET['action'] ?? 'stats';
        
        if ($action === 'stats') {
            // Show stats about gifts with/without categories
            $stats = $mysqli->query("
                SELECT 
                    COUNT(*) as total_artworks,
                    SUM(CASE WHEN category_id IS NOT NULL THEN 1 ELSE 0 END) as categorized,
                    SUM(CASE WHEN category_id IS NULL THEN 1 ELSE 0 END) as uncategorized,
                    COUNT(DISTINCT category_id) as unique_categories
                FROM artworks
            ")->fetch_assoc();
            
            echo json_encode([
                "status" => "success",
                "stats" => $stats,
                "categorization_rate" => round(($stats['categorized'] / max(1, $stats['total_artworks'])) * 100, 2) . '%'
            ]);
            
        } elseif ($action === 'uncategorized') {
            // Get uncategorized artworks for classification
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            
            $artworks = $mysqli->query("
                SELECT id, title, description, price, is_combo, status
                FROM artworks
                WHERE category_id IS NULL
                LIMIT $limit
            ")->fetch_all(MYSQLI_ASSOC);
            
            // Classify them
            $classified = [];
            foreach ($artworks as $artwork) {
                $prediction = $classifier->classifyGift($artwork['title'], 0.7);
                $artwork['prediction'] = $prediction;
                $classified[] = $artwork;
            }
            
            echo json_encode([
                "status" => "success",
                "uncategorized_count" => count($artworks),
                "artworks" => $classified
            ]);
            
        } elseif ($action === 'categories') {
            // Get available categories
            $categories = $mysqli->query("
                SELECT id, name, description, COUNT(DISTINCT a.id) as artwork_count
                FROM categories c
                LEFT JOIN artworks a ON a.category_id = c.id
                WHERE c.status = 'active'
                GROUP BY c.id
                ORDER BY artwork_count DESC
            ")->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode([
                "status" => "success",
                "categories" => $categories
            ]);
        }
        
    } elseif ($method === 'POST') {
        // Apply classifications
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $body['action'] ?? '';
        
        if ($action === 'apply_classification') {
            // Apply predicted category to artwork
            $artwork_id = (int)($body['artwork_id'] ?? 0);
            $category_name = trim($body['category_name'] ?? '');
            
            if ($artwork_id <= 0 || empty($category_name)) {
                http_response_code(422);
                echo json_encode(["status" => "error", "message" => "artwork_id and category_name required"]);
                exit;
            }
            
            // Get category ID
            $catRes = $mysqli->query("SELECT id FROM categories WHERE name='$category_name' AND status='active' LIMIT 1");
            if (!$catRes || $catRes->num_rows === 0) {
                http_response_code(422);
                echo json_encode(["status" => "error", "message" => "Category not found"]);
                exit;
            }
            $catRow = $catRes->fetch_assoc();
            $category_id = $catRow['id'];
            
            // Update artwork
            $stmt = $mysqli->prepare("UPDATE artworks SET category_id=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param('ii', $category_id, $artwork_id);
            $stmt->execute();
            
            echo json_encode([
                "status" => "success",
                "message" => "Artwork classified successfully",
                "artwork_id" => $artwork_id,
                "category_id" => $category_id,
                "category_name" => $category_name
            ]);
            
        } elseif ($action === 'apply_bulk_classification') {
            // Bulk apply classifications with high confidence
            $confidence_threshold = (float)($body['confidence_threshold'] ?? 0.8);
            $dry_run = !empty($body['dry_run']);
            
            // Get uncategorized artworks
            $artworks = $mysqli->query("
                SELECT id, title FROM artworks WHERE category_id IS NULL
            ")->fetch_all(MYSQLI_ASSOC);
            
            $applied = [];
            $skipped = [];
            $updated_count = 0;
            
            foreach ($artworks as $artwork) {
                $prediction = $classifier->classifyGift($artwork['title'], $confidence_threshold);
                
                if ($prediction['action'] === 'auto_assign' && $prediction['predicted_category']) {
                    // Get category ID
                    $catRes = $mysqli->query("
                        SELECT id FROM categories 
                        WHERE name='{$prediction['predicted_category']}' AND status='active' 
                        LIMIT 1
                    ");
                    
                    if ($catRes && $catRes->num_rows > 0) {
                        $catRow = $catRes->fetch_assoc();
                        
                        if (!$dry_run) {
                            $stmt = $mysqli->prepare("UPDATE artworks SET category_id=?, updated_at=NOW() WHERE id=?");
                            $stmt->bind_param('ii', $catRow['id'], $artwork['id']);
                            $stmt->execute();
                            $updated_count++;
                        }
                        
                        $applied[] = [
                            'artwork_id' => $artwork['id'],
                            'title' => $artwork['title'],
                            'predicted_category' => $prediction['predicted_category'],
                            'confidence' => $prediction['confidence_percent']
                        ];
                    }
                } else {
                    $skipped[] = [
                        'artwork_id' => $artwork['id'],
                        'title' => $artwork['title'],
                        'confidence' => $prediction['confidence_percent'],
                        'reason' => $prediction['reason']
                    ];
                }
            }
            
            echo json_encode([
                "status" => "success",
                "mode" => $dry_run ? "dry_run" : "applied",
                "total_processed" => count($artworks),
                "applied_count" => count($applied),
                "skipped_count" => count($skipped),
                "actually_updated" => $dry_run ? 0 : $updated_count,
                "applied" => $applied,
                "skipped" => array_slice($skipped, 0, 10) // Show first 10 skipped
            ]);
            
        } else {
            http_response_code(422);
            echo json_encode([
                "status" => "error",
                "message" => "Unknown action",
                "available_actions" => ["apply_classification", "apply_bulk_classification"]
            ]);
        }
        
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