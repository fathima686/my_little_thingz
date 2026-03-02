<?php
/**
 * Craft Validation Statistics API
 * Provides statistics for the admin dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    require_once '../../config/database.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get overall statistics
    $stats = [];
    
    // Pending submissions (requiring admin review)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM practice_uploads 
        WHERE (authenticity_status = 'flagged' OR craft_validation_status = 'flagged')
        AND status = 'pending'
    ");
    $stmt->execute();
    $stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Flagged images (from craft validation)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM craft_image_validation 
        WHERE validation_status = 'flagged'
    ");
    $stmt->execute();
    $stats['flagged'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Rejected images (from craft validation)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM craft_image_validation 
        WHERE validation_status = 'rejected'
    ");
    $stmt->execute();
    $stats['rejected'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Approved today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM practice_uploads 
        WHERE status = 'approved' 
        AND DATE(reviewed_date) = CURDATE()
    ");
    $stmt->execute();
    $stats['approved_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Category breakdown
    $stmt = $pdo->prepare("
        SELECT 
            civ.predicted_category,
            COUNT(*) as count,
            AVG(civ.prediction_confidence) as avg_confidence
        FROM craft_image_validation civ
        WHERE civ.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
        GROUP BY civ.predicted_category
        ORDER BY count DESC
    ");
    $stmt->execute();
    $stats['category_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // AI detection statistics
    $stmt = $pdo->prepare("
        SELECT 
            ai_generated_detected,
            ai_confidence,
            COUNT(*) as count
        FROM craft_image_validation 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
        GROUP BY ai_generated_detected, ai_confidence
    ");
    $stmt->execute();
    $stats['ai_detection'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Validation accuracy (category matches)
    $stmt = $pdo->prepare("
        SELECT 
            category_matches,
            COUNT(*) as count,
            AVG(prediction_confidence) as avg_confidence
        FROM craft_image_validation 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
        GROUP BY category_matches
    ");
    $stmt->execute();
    $stats['validation_accuracy'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent activity (last 24 hours)
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(upload_date, '%H:00') as hour,
            COUNT(*) as uploads
        FROM practice_uploads 
        WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY DATE_FORMAT(upload_date, '%H:00')
        ORDER BY hour
    ");
    $stmt->execute();
    $stats['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Craft validation stats error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load statistics: ' . $e->getMessage()
    ]);
}
?>