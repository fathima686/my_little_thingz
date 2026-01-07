<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Predefined category colors (matching frontend TUTORIAL_CATEGORIES)
$categoryColors = [
    'Hand Embroidery' => '#FFB6C1',
    'Resin Art' => '#B0E0E6',
    'Gift Making' => '#FFDAB9',
    'Mylanchi / Mehandi Art' => '#E6E6FA',
    'Mehandi Art' => '#E6E6FA',
    'Mylanchi' => '#E6E6FA',
    'Candle Making' => '#F0E68C',
    'Jewelry Making' => '#FFC0CB',
    'Jewelry' => '#FFC0CB',
    'Clay Modeling' => '#DDA0DD',
    'Clay' => '#DDA0DD',
    'default' => '#667eea'
];

try {
    // Get unique categories from tutorials table
    $stmt = $db->prepare("
        SELECT DISTINCT category 
        FROM tutorials 
        WHERE category IS NOT NULL 
          AND category != '' 
          AND category != '0'
          AND is_active = 1
        ORDER BY category ASC
    ");
    $stmt->execute();
    $tutorialCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Check if live_sessions table exists to count sessions
    $tableCheck = $db->query("SHOW TABLES LIKE 'live_sessions'");
    $hasSessionsTable = $tableCheck->rowCount() > 0;
    
    // Ensure live_subjects table exists and sync with tutorial categories
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS live_subjects (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            icon_url VARCHAR(255),
            color VARCHAR(7) DEFAULT '#667eea',
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_active (is_active),
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {
        // Table might already exist
    }
    
    // Sync tutorial categories to live_subjects table (case-insensitive deduplication)
    $normalizedCategories = [];
    $seen = [];
    foreach ($tutorialCategories as $categoryName) {
        $trimmed = trim($categoryName);
        if (empty($trimmed) || $trimmed === '0') continue;
        
        // Case-insensitive deduplication
        $normalized = strtolower($trimmed);
        if (!isset($seen[$normalized])) {
            $seen[$normalized] = $trimmed; // Store original case
            $normalizedCategories[] = $trimmed;
        }
    }
    
    foreach ($normalizedCategories as $categoryName) {
        $color = $categoryColors[$categoryName] ?? $categoryColors['default'];
        
        // Insert or update subject (UNIQUE constraint on name prevents duplicates)
        $syncStmt = $db->prepare("
            INSERT INTO live_subjects (name, description, color, is_active)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
                description = VALUES(description),
                color = VALUES(color),
                is_active = 1,
                updated_at = CURRENT_TIMESTAMP
        ");
        $syncStmt->execute([
            $categoryName,
            "Live classes for {$categoryName}",
            $color
        ]);
    }
    
    // Get all active subjects with session count (ensuring no duplicates)
    if ($hasSessionsTable) {
        $stmt = $db->prepare("
            SELECT 
                ls.*,
                COUNT(DISTINCT lses.id) as session_count
            FROM live_subjects ls
            LEFT JOIN live_sessions lses ON ls.id = lses.subject_id 
                AND lses.status IN ('scheduled', 'live')
            WHERE ls.is_active = 1
            GROUP BY ls.id, ls.name
            ORDER BY ls.name ASC
        ");
    } else {
        $stmt = $db->prepare("
            SELECT 
                ls.*,
                0 as session_count
            FROM live_subjects ls
            WHERE ls.is_active = 1
            GROUP BY ls.id, ls.name
            ORDER BY ls.name ASC
        ");
    }
    
    $stmt->execute();
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Remove any duplicates by name (case-insensitive)
    $uniqueSubjects = [];
    $seenNames = [];
    foreach ($subjects as $subject) {
        $normalizedName = strtolower(trim($subject['name']));
        if (!isset($seenNames[$normalizedName])) {
            $seenNames[$normalizedName] = true;
            $uniqueSubjects[] = $subject;
        }
    }
    $subjects = $uniqueSubjects;
    
    // If no categories found, return empty array
    if (empty($subjects)) {
        $subjects = [];
    }
    
    echo json_encode([
        'status' => 'success',
        'subjects' => $subjects
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

