<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id, X-Admin-Email");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

try {
    require_once "../../config/database.php";
    $database = new Database();
    $db = $database->getConnection();
    
    // Ensure live_subjects table exists
    $db->exec("CREATE TABLE IF NOT EXISTS live_subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        color VARCHAR(7) DEFAULT '#667eea',
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert default subjects if table is empty
    $countStmt = $db->query("SELECT COUNT(*) as count FROM live_subjects");
    $count = $countStmt->fetch(PDO::FETCH_ASSOC)["count"];
    
    if ($count == 0) {
        $defaultSubjects = [
            ["Hand Embroidery", "Live classes for Hand Embroidery", "#FFB6C1"],
            ["Resin Art", "Live classes for Resin Art", "#B0E0E6"],
            ["Gift Making", "Live classes for Gift Making", "#FFDAB9"],
            ["Mylanchi / Mehandi Art", "Live classes for Mylanchi / Mehandi Art", "#E6E6FA"],
            ["Candle Making", "Live classes for Candle Making", "#F0E68C"]
        ];
        
        $insertStmt = $db->prepare("INSERT INTO live_subjects (name, description, color) VALUES (?, ?, ?)");
        foreach ($defaultSubjects as $subject) {
            $insertStmt->execute($subject);
        }
    }
    
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        $stmt = $db->prepare("SELECT * FROM live_subjects WHERE is_active = 1 ORDER BY name");
        $stmt->execute();
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "status" => "success",
            "subjects" => $subjects
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>