<?php
// Quick utility to check users table schema
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get table structure
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get sample data
    $stmt = $db->query("SELECT * FROM users LIMIT 3");
    $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'table_structure' => $columns,
        'sample_data' => $sampleData,
        'column_names' => array_column($columns, 'Field')
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>