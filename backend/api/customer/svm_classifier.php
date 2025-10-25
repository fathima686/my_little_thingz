<?php
header('Content-Type: application/json');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost:5173', 'http://127.0.0.1:5173'];
if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

require_once '../../config/database.php';
require_once '../../services/SVMGiftClassifier.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $svmClassifier = new SVMGiftClassifier($db);
    
    // Load existing model if available
    $svmClassifier->loadModel();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle training request
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        if ($action === 'train') {
            $trainingResult = $svmClassifier->trainModel();
            $saveResult = $svmClassifier->saveModel();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'SVM model trained successfully',
                'training_result' => $trainingResult,
                'saved' => $saveResult,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;
        }
    }
    
    // Handle classification requests
    $giftId = isset($_GET['gift_id']) ? (int)$_GET['gift_id'] : 0;
    $batch = isset($_GET['batch']) ? filter_var($_GET['batch'], FILTER_VALIDATE_BOOLEAN) : false;
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
    
    if ($giftId > 0) {
        // Classify single gift
        $stmt = $db->prepare("
            SELECT 
                a.id, a.title, a.description, a.price, a.category_id, 
                a.availability, a.image_url, a.created_at,
                c.name as category_name
            FROM artworks a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.id = :id AND a.status = 'active'
        ");
        $stmt->bindValue(':id', $giftId, PDO::PARAM_INT);
        $stmt->execute();
        
        $gift = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$gift) {
            throw new Exception('Gift not found');
        }
        
        $classification = $svmClassifier->classifyGift($gift);
        
        echo json_encode([
            'status' => 'success',
            'algorithm' => 'SVM',
            'classification' => $classification,
            'gift_info' => [
                'id' => (int)$gift['id'],
                'title' => $gift['title'],
                'price' => (float)$gift['price'],
                'category_name' => $gift['category_name']
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } elseif ($batch) {
        // Batch classify multiple gifts
        $stmt = $db->prepare("
            SELECT 
                a.id, a.title, a.description, a.price, a.category_id, 
                a.availability, a.image_url, a.created_at,
                c.name as category_name
            FROM artworks a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'active'
            ORDER BY a.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $gifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $classifications = $svmClassifier->batchClassify($gifts);
        
        // Format results
        $results = [];
        foreach ($gifts as $index => $gift) {
            $results[] = [
                'gift_info' => [
                    'id' => (int)$gift['id'],
                    'title' => $gift['title'],
                    'price' => (float)$gift['price'],
                    'category_name' => $gift['category_name']
                ],
                'classification' => $classifications[$index]
            ];
        }
        
        // Calculate statistics
        $premiumCount = 0;
        $budgetCount = 0;
        foreach ($classifications as $classification) {
            if ($classification['prediction'] === 'Premium') {
                $premiumCount++;
            } else {
                $budgetCount++;
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'algorithm' => 'SVM',
            'results' => $results,
            'statistics' => [
                'total_classified' => count($results),
                'premium_count' => $premiumCount,
                'budget_count' => $budgetCount,
                'premium_percentage' => count($results) > 0 ? round(($premiumCount / count($results)) * 100, 2) : 0
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } else {
        // Get model statistics
        $modelStats = $svmClassifier->getModelStats();
        
        echo json_encode([
            'status' => 'success',
            'algorithm' => 'SVM',
            'model_stats' => $modelStats,
            'endpoints' => [
                'single_classification' => '?gift_id=123',
                'batch_classification' => '?batch=true&limit=20',
                'train_model' => 'POST with {"action": "train"}'
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

















