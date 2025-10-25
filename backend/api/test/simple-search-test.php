<?php
// Simple enhanced search test
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
    $mysqli->set_charset('utf8mb4');
    
    if ($mysqli->connect_error) {
        throw new Exception("Database connection failed: " . $mysqli->connect_error);
    }
    
    $searchTerm = $_GET['term'] ?? 'sweet';
    $limit = intval($_GET['limit'] ?? 20);
    
    echo "Testing search for: " . $searchTerm . "\n";
    
    // Simple search query
    $sql = "SELECT DISTINCT a.*, c.name as category_name, c.id as category_id
            FROM artworks a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'active' 
            AND a.availability != 'out_of_stock'
            AND (a.title LIKE ? OR a.description LIKE ? OR c.name LIKE ?)
            ORDER BY a.title ASC
            LIMIT ?";
    
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $searchPattern = "%{$searchTerm}%";
        $stmt->bind_param("sssi", $searchPattern, $searchPattern, $searchPattern, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $artworks = [];
        while ($row = $result->fetch_assoc()) {
            $artworks[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'price' => $row['price'],
                'image_url' => $row['image_url'],
                'category_name' => $row['category_name'],
                'category_id' => $row['category_id']
            ];
        }
        
        $stmt->close();
        
        echo json_encode([
            'status' => 'success',
            'search_term' => $searchTerm,
            'total_found' => count($artworks),
            'artworks' => $artworks
        ], JSON_PRETTY_PRINT);
        
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'SQL prepare failed: ' . $mysqli->error
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>



