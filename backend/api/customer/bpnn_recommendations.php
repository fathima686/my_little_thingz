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
require_once '../../services/BPNNDataProcessor.php';
require_once '../../services/UserBehaviorTracker.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $trainer = new BPNNTrainer($db);
    $dataProcessor = new BPNNDataProcessor($db);
    $behaviorTracker = new UserBehaviorTracker($db);

    // Get request parameters
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $limit = isset($_GET['limit']) ? max(1, min(20, (int)$_GET['limit'])) : 8;
    $minConfidence = isset($_GET['min_confidence']) ? max(0.0, min(1.0, (float)$_GET['min_confidence'])) : 0.3;
    $useCache = isset($_GET['use_cache']) ? filter_var($_GET['use_cache'], FILTER_VALIDATE_BOOLEAN) : true;

    if ($userId <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Valid user_id is required'
        ]);
        exit;
    }

    // Check if user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE id = :user_id");
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found'
        ]);
        exit;
    }

    // Try to get cached predictions first
    $recommendations = [];
    if ($useCache) {
        $recommendations = getCachedRecommendations($db, $userId, $limit, $minConfidence);
    }

    // If no cached recommendations or cache disabled, generate new ones
    if (empty($recommendations)) {
        $recommendations = generateNewRecommendations($db, $trainer, $dataProcessor, $userId, $limit, $minConfidence);
    }

    // Add additional artwork information
    $recommendations = enrichRecommendations($db, $recommendations);

    echo json_encode([
        'status' => 'success',
        'recommendations' => $recommendations,
        'count' => count($recommendations),
        'user_id' => $userId,
        'generated_at' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log("BPNN Recommendations Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to generate recommendations',
        'debug' => $e->getMessage()
    ]);
}

/**
 * Get cached recommendations
 */
function getCachedRecommendations($db, $userId, $limit, $minConfidence)
{
    $stmt = $db->prepare("
        SELECT 
            p.artwork_id,
            p.prediction_score,
            p.created_at
        FROM bpnn_predictions p
        WHERE p.user_id = :user_id 
        AND p.prediction_score >= :min_confidence
        AND p.expires_at > NOW()
        ORDER BY p.prediction_score DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':min_confidence', $minConfidence, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $recommendations = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $recommendations[] = [
            'artwork_id' => (int)$row['artwork_id'],
            'prediction_score' => (float)$row['prediction_score'],
            'confidence' => (float)$row['prediction_score'],
            'cached' => true,
            'cached_at' => $row['created_at']
        ];
    }

    return $recommendations;
}

/**
 * Generate new recommendations using BPNN
 */
function generateNewRecommendations($db, $trainer, $dataProcessor, $userId, $limit, $minConfidence)
{
    // Load the active BPNN model
    $nn = $trainer->loadActiveModel();
    if (!$nn) {
        throw new Exception('No trained BPNN model available');
    }

    // Get all active artworks
    $stmt = $db->prepare("
        SELECT id FROM artworks 
        WHERE status = 'active' 
        ORDER BY created_at DESC
        LIMIT 200
    ");
    $stmt->execute();
    $artworks = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($artworks)) {
        return [];
    }

    $predictions = [];
    $normalizationParams = $dataProcessor->getNormalizationParams();

    // Generate predictions for each artwork
    foreach ($artworks as $artworkId) {
        try {
            // Extract features
            $features = $dataProcessor->extractFeatures($userId, $artworkId);
            if (!$features) {
                continue;
            }

            // Normalize features
            $normalizedFeatures = BPNNNeuralNetwork::normalizeInputs(
                $features,
                $normalizationParams['minValues'],
                $normalizationParams['maxValues']
            );

            // Make prediction
            $prediction = $nn->predict($normalizedFeatures);
            $score = $prediction['prediction'];

            if ($score >= $minConfidence) {
                $predictions[] = [
                    'artwork_id' => (int)$artworkId,
                    'prediction_score' => $score,
                    'confidence' => $prediction['confidence'],
                    'cached' => false
                ];
            }

        } catch (Exception $e) {
            error_log("Prediction error for artwork $artworkId: " . $e->getMessage());
            continue;
        }
    }

    // Sort by prediction score
    usort($predictions, function($a, $b) {
        return $b['prediction_score'] <=> $a['prediction_score'];
    });

    // Take top recommendations
    $recommendations = array_slice($predictions, 0, $limit);

    // Cache the predictions
    cachePredictions($db, $userId, $recommendations);

    return $recommendations;
}

/**
 * Cache predictions in database
 */
function cachePredictions($db, $userId, $recommendations)
{
    try {
        // Get active model ID
        $stmt = $db->prepare("
            SELECT id FROM bpnn_models 
            WHERE model_name = 'gift_preference_predictor' AND is_active = 1
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute();
        $modelId = $stmt->fetchColumn();

        if (!$modelId) {
            return;
        }

        // Clear existing predictions for this user
        $stmt = $db->prepare("DELETE FROM bpnn_predictions WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        // Insert new predictions
        $stmt = $db->prepare("
            INSERT INTO bpnn_predictions 
            (user_id, artwork_id, prediction_score, model_id, expires_at)
            VALUES (:user_id, :artwork_id, :score, :model_id, :expires_at)
        ");

        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // Cache for 1 hour

        foreach ($recommendations as $rec) {
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':artwork_id', $rec['artwork_id'], PDO::PARAM_INT);
            $stmt->bindValue(':score', $rec['prediction_score'], PDO::PARAM_STR);
            $stmt->bindValue(':model_id', $modelId, PDO::PARAM_INT);
            $stmt->bindValue(':expires_at', $expiresAt, PDO::PARAM_STR);
            $stmt->execute();
        }

    } catch (Exception $e) {
        error_log("Cache predictions error: " . $e->getMessage());
    }
}

/**
 * Enrich recommendations with artwork details
 */
function enrichRecommendations($db, $recommendations)
{
    if (empty($recommendations)) {
        return [];
    }

    $artworkIds = array_column($recommendations, 'artwork_id');
    $placeholders = str_repeat('?,', count($artworkIds) - 1) . '?';

    $stmt = $db->prepare("
        SELECT 
            a.id, a.title, a.description, a.price, a.image_url, 
            a.category_id, a.availability, a.created_at,
            a.offer_price, a.offer_percent, a.offer_starts_at, a.offer_ends_at,
            a.force_offer_badge,
            c.name as category_name
        FROM artworks a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.id IN ($placeholders)
        AND a.status = 'active'
    ");
    $stmt->execute($artworkIds);

    $artworkData = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $artworkData[$row['id']] = $row;
    }

    // Merge recommendations with artwork data
    $enrichedRecommendations = [];
    foreach ($recommendations as $rec) {
        $artworkId = $rec['artwork_id'];
        if (isset($artworkData[$artworkId])) {
            $artwork = $artworkData[$artworkId];
            
            // Calculate effective price
            $effectivePrice = $artwork['price'];
            if ($artwork['offer_price'] && 
                $artwork['offer_starts_at'] <= date('Y-m-d H:i:s') && 
                $artwork['offer_ends_at'] >= date('Y-m-d H:i:s')) {
                $effectivePrice = $artwork['offer_price'];
            }

            $enrichedRecommendations[] = array_merge($rec, [
                'title' => $artwork['title'],
                'description' => $artwork['description'],
                'price' => (float)$artwork['price'],
                'effective_price' => (float)$effectivePrice,
                'image_url' => $artwork['image_url'],
                'category_id' => (int)$artwork['category_id'],
                'category_name' => $artwork['category_name'],
                'availability' => $artwork['availability'],
                'created_at' => $artwork['created_at'],
                'has_offer' => !empty($artwork['offer_price']),
                'offer_price' => $artwork['offer_price'] ? (float)$artwork['offer_price'] : null,
                'offer_percent' => $artwork['offer_percent'] ? (float)$artwork['offer_percent'] : null,
                'force_offer_badge' => (bool)$artwork['force_offer_badge']
            ]);
        }
    }

    return $enrichedRecommendations;
}

















