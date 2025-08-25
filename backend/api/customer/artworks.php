<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch all available artworks
        $query = "SELECT 
                    a.id,
                    a.title,
                    a.description,
                    a.price,
                    a.image_url,
                    a.category_id,
                    a.availability,
                    a.created_at,
                    CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS artist_name,
                    c.name as category_name
                  FROM artworks a
                  LEFT JOIN users u ON a.artist_id = u.id
                  LEFT JOIN categories c ON a.category_id = c.id
                  WHERE a.status = 'active'
                  ORDER BY a.created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();
        $artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the data
        foreach ($artworks as &$artwork) {
            $artwork['price'] = number_format($artwork['price'], 2);
            $artwork['created_at'] = date('Y-m-d H:i:s', strtotime($artwork['created_at']));
        }

        echo json_encode([
            'status' => 'success',
            'artworks' => $artworks
        ]);

    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Method not allowed'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>