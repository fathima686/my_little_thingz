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
require_once '../../services/BPNNTrainer.php';
require_once '../../services/UserBehaviorTracker.php';

// Simple authentication check (you may want to implement proper admin authentication)
$isAdmin = true; // Replace with actual admin authentication

if (!$isAdmin) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Admin access required'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $trainer = new BPNNTrainer($db);
    $behaviorTracker = new UserBehaviorTracker($db);

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    switch ($method) {
        case 'GET':
            handleGetRequest($trainer, $action);
            break;
        case 'POST':
            handlePostRequest($trainer, $behaviorTracker, $action);
            break;
        default:
            echo json_encode([
                'status' => 'error',
                'message' => 'Method not allowed'
            ]);
    }

} catch (Exception $e) {
    error_log("BPNN Training API Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'debug' => $e->getMessage()
    ]);
}

function handleGetRequest($trainer, $action)
{
    switch ($action) {
        case 'status':
            getTrainingStatus($trainer);
            break;
        case 'history':
            getTrainingHistory($trainer);
            break;
        case 'performance':
            getModelPerformance($trainer);
            break;
        case 'test':
            testModel($trainer);
            break;
        case 'statistics':
            getBehaviorStatistics($trainer);
            break;
        default:
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action'
            ]);
    }
}

function handlePostRequest($trainer, $behaviorTracker, $action)
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'train':
            trainModel($trainer, $input);
            break;
        case 'retrain':
            retrainModel($trainer, $input);
            break;
        case 'cleanup':
            cleanupData($trainer, $behaviorTracker, $input);
            break;
        default:
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action'
            ]);
    }
}

function getTrainingStatus($trainer)
{
    $performance = $trainer->getModelPerformance();
    
    if (!$performance) {
        echo json_encode([
            'status' => 'success',
            'has_model' => false,
            'message' => 'No trained model found'
        ]);
        return;
    }

    echo json_encode([
        'status' => 'success',
        'has_model' => true,
        'model' => $performance
    ]);
}

function getTrainingHistory($trainer)
{
    $history = $trainer->getModelHistory(10);
    
    echo json_encode([
        'status' => 'success',
        'history' => $history
    ]);
}

function getModelPerformance($trainer)
{
    $performance = $trainer->getModelPerformance();
    
    if (!$performance) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No model found'
        ]);
        return;
    }

    echo json_encode([
        'status' => 'success',
        'performance' => $performance
    ]);
}

function testModel($trainer)
{
    try {
        $results = $trainer->testModel();
        
        echo json_encode([
            'status' => 'success',
            'test_results' => $results
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Test failed: ' . $e->getMessage()
        ]);
    }
}

function getBehaviorStatistics($trainer)
{
    global $db;
    
    $behaviorTracker = new UserBehaviorTracker($db);
    $stats = $behaviorTracker->getBehaviorStatistics();
    
    echo json_encode([
        'status' => 'success',
        'statistics' => $stats
    ]);
}

function trainModel($trainer, $config)
{
    try {
        $results = $trainer->trainModel($config);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Model trained successfully',
            'results' => $results
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Training failed: ' . $e->getMessage()
        ]);
    }
}

function retrainModel($trainer, $config)
{
    try {
        $results = $trainer->retrainModel($config);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Model retrained successfully',
            'results' => $results
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Retraining failed: ' . $e->getMessage()
        ]);
    }
}

function cleanupData($trainer, $behaviorTracker, $config)
{
    try {
        $days = $config['days'] ?? 365;
        $keepModels = $config['keep_models'] ?? 5;
        
        // Clean up old behavior data
        $deletedBehaviors = $behaviorTracker->cleanupOldData($days);
        
        // Clean up old models
        $trainer->cleanupOldModels($keepModels);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Cleanup completed',
            'deleted_behaviors' => $deletedBehaviors,
            'days_kept' => $days,
            'models_kept' => $keepModels
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Cleanup failed: ' . $e->getMessage()
        ]);
    }
}

