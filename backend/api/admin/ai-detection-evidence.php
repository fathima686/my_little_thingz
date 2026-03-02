<?php
/**
 * AI Detection Evidence API
 * 
 * Retrieves detailed AI detection evidence for flagged images
 * Used by admin dashboard to review AI-flagged submissions
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get image_id from query parameter
    $imageId = $_GET['image_id'] ?? null;
    
    if (!$imageId) {
        echo json_encode([
            'success' => false,
            'error' => 'image_id parameter required'
        ]);
        exit();
    }
    
    // Retrieve AI detection evidence
    $stmt = $pdo->prepare("
        SELECT 
            id,
            image_id,
            image_type,
            user_id,
            tutorial_id,
            predicted_category,
            prediction_confidence,
            ai_risk_score,
            ai_risk_level,
            ai_detection_decision,
            ai_detection_evidence,
            metadata_ai_keywords,
            exif_camera_present,
            texture_laplacian_variance,
            watermark_detected,
            ai_decision,
            requires_review,
            decision_reasons,
            admin_decision,
            admin_notes,
            created_at,
            updated_at
        FROM craft_image_validation_v2
        WHERE image_id = ?
        LIMIT 1
    ");
    
    $stmt->execute([$imageId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        echo json_encode([
            'success' => false,
            'error' => 'No validation record found for this image'
        ]);
        exit();
    }
    
    // Parse JSON fields
    if ($record['ai_detection_evidence']) {
        $record['ai_detection_evidence'] = json_decode($record['ai_detection_evidence'], true);
    }
    
    if ($record['metadata_ai_keywords']) {
        $record['metadata_ai_keywords'] = json_decode($record['metadata_ai_keywords'], true);
    }
    
    if ($record['decision_reasons']) {
        $record['decision_reasons'] = json_decode($record['decision_reasons'], true);
    }
    
    // Build human-readable summary
    $summary = [
        'risk_assessment' => [
            'score' => $record['ai_risk_score'],
            'level' => $record['ai_risk_level'],
            'decision' => $record['ai_detection_decision'],
            'interpretation' => getInterpretation($record['ai_risk_score'], $record['ai_risk_level'])
        ],
        'detection_layers' => []
    ];
    
    // Layer 1: Metadata Analysis
    if ($record['metadata_ai_keywords']) {
        $keywords = $record['metadata_ai_keywords'];
        $summary['detection_layers'][] = [
            'layer' => 'Metadata Analysis',
            'status' => count($keywords) > 0 ? 'suspicious' : 'clean',
            'details' => count($keywords) > 0 
                ? 'Found AI generator keywords: ' . implode(', ', array_column($keywords, 'keyword'))
                : 'No AI generator keywords found in metadata',
            'risk_contribution' => count($keywords) > 0 ? 'High (50 points)' : 'None'
        ];
    }
    
    // Layer 2: EXIF Analysis
    if ($record['exif_camera_present'] !== null) {
        $summary['detection_layers'][] = [
            'layer' => 'EXIF Camera Metadata',
            'status' => $record['exif_camera_present'] ? 'present' : 'missing',
            'details' => $record['exif_camera_present'] 
                ? 'Camera metadata found - typical of real photos'
                : 'No camera metadata - typical of AI-generated images',
            'risk_contribution' => !$record['exif_camera_present'] ? 'Moderate (20 points)' : 'None'
        ];
    }
    
    // Layer 3: Texture Analysis
    if ($record['texture_laplacian_variance'] !== null) {
        $variance = floatval($record['texture_laplacian_variance']);
        $isSmooth = $variance < 100.0;
        $summary['detection_layers'][] = [
            'layer' => 'Texture Smoothness',
            'status' => $isSmooth ? 'overly_smooth' : 'normal',
            'details' => sprintf(
                'Laplacian variance: %.2f %s',
                $variance,
                $isSmooth ? '(below threshold - synthetic appearance)' : '(normal texture)'
            ),
            'risk_contribution' => $isSmooth ? 'Moderate (25 points)' : 'None'
        ];
    }
    
    // Layer 4: Watermark Detection
    if ($record['watermark_detected']) {
        $summary['detection_layers'][] = [
            'layer' => 'Watermark Detection',
            'status' => 'detected',
            'details' => 'Possible AI platform watermark detected in bottom-right corner',
            'risk_contribution' => 'Weak (15 points)'
        ];
    } else {
        $summary['detection_layers'][] = [
            'layer' => 'Watermark Detection',
            'status' => 'not_detected',
            'details' => 'No obvious watermark pattern detected',
            'risk_contribution' => 'None'
        ];
    }
    
    // Add recommendation
    $summary['recommendation'] = getRecommendation($record);
    
    echo json_encode([
        'success' => true,
        'validation_record' => $record,
        'summary' => $summary,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

/**
 * Get human-readable interpretation of risk score
 */
function getInterpretation($score, $level) {
    if ($level === 'high' || $score >= 70) {
        return 'High probability of AI generation - multiple detection layers triggered';
    } elseif ($level === 'medium' || $score >= 40) {
        return 'Moderate probability of AI generation - requires manual review';
    } else {
        return 'Low probability of AI generation - likely authentic';
    }
}

/**
 * Get recommendation for admin action
 */
function getRecommendation($record) {
    $score = intval($record['ai_risk_score']);
    $decision = $record['ai_detection_decision'];
    
    if ($decision === 'reject' || $score >= 70) {
        return [
            'action' => 'reject',
            'reason' => 'Strong evidence of AI generation - recommend rejection',
            'confidence' => 'high'
        ];
    } elseif ($decision === 'flag' || $score >= 40) {
        return [
            'action' => 'manual_review',
            'reason' => 'Moderate evidence of AI generation - careful review recommended',
            'confidence' => 'medium'
        ];
    } else {
        return [
            'action' => 'approve',
            'reason' => 'Low evidence of AI generation - likely authentic',
            'confidence' => 'high'
        ];
    }
}
?>
