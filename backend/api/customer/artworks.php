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

        // Base query (include offer columns if present)
        $hasOfferCols = false;
        try {
            $col = $db->query("SHOW COLUMNS FROM artworks LIKE 'offer_price'");
            if ($col && $col->rowCount() > 0) { $hasOfferCols = true; }
        } catch (Throwable $e) { $hasOfferCols = false; }

        $selectCols = "a.id, a.title, a.description, a.price, a.image_url, a.category_id, a.availability, a.created_at, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS artist_name, c.name as category_name";
        if ($hasOfferCols) {
            $selectCols .= ", a.offer_price, a.offer_percent, a.offer_starts_at, a.offer_ends_at";
            try {
                $col2 = $db->query("SHOW COLUMNS FROM artworks LIKE 'force_offer_badge'");
                if ($col2 && $col2->rowCount() > 0) {
                    $selectCols .= ", a.force_offer_badge";
                }
            } catch (Throwable $e) {}
        }

        $sql = "SELECT $selectCols
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

        // Keep price numeric and compute effective offer price + flags if columns exist
        $now = new DateTime('now');
        foreach ($artworks as &$artwork) {
            if (isset($artwork['price'])) {
                $artwork['price'] = (float)$artwork['price'];
            }
            if (isset($artwork['created_at'])) {
                $artwork['created_at'] = date('Y-m-d H:i:s', strtotime($artwork['created_at']));
            }

            $offerPrice = isset($artwork['offer_price']) && $artwork['offer_price'] !== null ? (float)$artwork['offer_price'] : null;
            $offerPercent = isset($artwork['offer_percent']) && $artwork['offer_percent'] !== null ? (float)$artwork['offer_percent'] : null;
            $startsAt = isset($artwork['offer_starts_at']) && $artwork['offer_starts_at'] ? new DateTime($artwork['offer_starts_at']) : null;
            $endsAt = isset($artwork['offer_ends_at']) && $artwork['offer_ends_at'] ? new DateTime($artwork['offer_ends_at']) : null;

            $isWindowOk = true;
            if ($startsAt && $now < $startsAt) $isWindowOk = false;
            if ($endsAt && $now > $endsAt) $isWindowOk = false;

            $effective = $artwork['price'];
            if ($isWindowOk) {
                if ($offerPrice !== null && $offerPrice > 0 && $offerPrice < $effective) {
                    $effective = $offerPrice;
                } elseif ($offerPercent !== null && $offerPercent > 0 && $offerPercent <= 100) {
                    $disc = round($artwork['price'] * ($offerPercent / 100), 2);
                    $candidate = max(0, $artwork['price'] - $disc);
                    if ($candidate < $effective) $effective = $candidate;
                }
            }
            $artwork['effective_price'] = (float)$effective;
            $artwork['is_on_offer'] = $isWindowOk && ($effective < (float)$artwork['price']);
            // If force flag present, show banner regardless of price change
            if (isset($artwork['force_offer_badge']) && (int)$artwork['force_offer_badge'] === 1) {
                $artwork['is_on_offer'] = true;
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