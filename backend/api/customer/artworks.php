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
        // Read query params for filtering/sorting
        $search      = isset($_GET['search']) ? trim($_GET['search']) : '';
        $categoryId  = isset($_GET['category_id']) ? trim($_GET['category_id']) : '';
        $minPrice    = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
        $maxPrice    = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
        $sort        = isset($_GET['sort']) ? strtolower(trim($_GET['sort'])) : '';

        // Base query
        $sql = "SELECT 
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
                WHERE a.status = 'active'";

        $params = [];

        // Filters
        if ($categoryId !== '') {
            $sql .= " AND a.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }
        if ($search !== '') {
            // Search in title/description/category
            $sql .= " AND (a.title LIKE :q OR a.description LIKE :q OR c.name LIKE :q)";
            $params[':q'] = '%' . $search . '%';
        }
        if ($minPrice !== null) {
            $sql .= " AND a.price >= :min_price";
            $params[':min_price'] = $minPrice;
        }
        if ($maxPrice !== null) {
            $sql .= " AND a.price <= :max_price";
            $params[':max_price'] = $maxPrice;
        }

        // Sorting
        switch ($sort) {
            case 'price_asc':
                $sql .= " ORDER BY a.price ASC, a.created_at DESC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY a.price DESC, a.created_at DESC";
                break;
            case 'newest':
            default:
                $sql .= " ORDER BY a.created_at DESC";
                break;
        }

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            // Bind with automatic type detection
            if (is_int($v) || is_float($v)) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR); // use STR to avoid locale issues
            } else {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
        }
        $stmt->execute();
        $artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Keep price numeric (float) and format dates consistently
        foreach ($artworks as &$artwork) {
            if (isset($artwork['price'])) {
                $artwork['price'] = (float)$artwork['price'];
            }
            if (isset($artwork['created_at'])) {
                $artwork['created_at'] = date('Y-m-d H:i:s', strtotime($artwork['created_at']));
            }
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