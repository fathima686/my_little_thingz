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
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // inputs
    $artworkId = isset($_GET['artwork_id']) ? (int)$_GET['artwork_id'] : 0;
    $userId    = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $limit     = isset($_GET['limit']) ? max(1, min(20, (int)$_GET['limit'])) : 8;

    // Check if offer columns exist for effective price computation
    $hasOfferCols = false;
    try {
        $col = $db->query("SHOW COLUMNS FROM artworks LIKE 'offer_price'");
        if ($col && $col->rowCount() > 0) { $hasOfferCols = true; }
    } catch (Throwable $e) { $hasOfferCols = false; }

    $selectCols = "a.id, a.title, a.description, a.price, a.image_url, a.category_id, a.availability, a.created_at, c.name as category_name";
    if ($hasOfferCols) {
        $selectCols .= ", a.offer_price, a.offer_percent, a.offer_starts_at, a.offer_ends_at";
        try {
            $col2 = $db->query("SHOW COLUMNS FROM artworks LIKE 'force_offer_badge'");
            if ($col2 && $col2->rowCount() > 0) {
                $selectCols .= ", a.force_offer_badge";
            }
        } catch (Throwable $e) {}
    }

    // Strategy:
    // - If artwork_id provided: find similar by category first, then price closeness, then title similarity
    // - Else if user_id provided: recommend recent/popular items in user's preferred categories (from wishlist or orders if available)
    // - Else: return recent active artworks

    $baseWhere = "a.status = 'active'";
    $params = [];

    if ($artworkId > 0) {
        // Fetch the anchor artwork
        $stmt = $db->prepare("SELECT a.id, a.category_id, a.price, a.title, c.name AS category_name
                               FROM artworks a LEFT JOIN categories c ON a.category_id = c.id
                               WHERE a.id = :id AND a.status='active' LIMIT 1");
        $stmt->bindValue(':id', $artworkId, PDO::PARAM_INT);
        $stmt->execute();
        $anchor = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$anchor) {
            echo json_encode(['status' => 'success', 'recommendations' => []]);
            exit;
        }

        // --- Rule Engine (predefined classification rules) ---
        // You can modify these rules to control when certain categories appear.
        // Each rule may provide preferred categories or title keyword affinities.
        // All keys are canonical lowercase category names.
        $SYNONYMS = [
            'gift box' => 'gift boxes',
            'gift boxes' => 'gift boxes',
            'giftbox' => 'gift boxes',
            'bouquets' => 'bouquets',
            'boquetes' => 'bouquets',
            'polaroids' => 'polaroids',
            'frames' => 'frames',
            'albums' => 'albums',
            'chocolates' => 'chocolates',
        ];

        $normalizeCategory = function($name) use ($SYNONYMS) {
            $n = strtolower(trim((string)$name));
            return $SYNONYMS[$n] ?? $n;
        };

        $RULES = [
            // When anchor is Polaroids, promote Frames and Albums
            'by_anchor_category' => [
                'polaroids' => [
                    'prefer_categories' => ['frames', 'albums'],
                    'bonus' => 0.4 // larger bonus = more likely to surface
                ],
                'frames' => [
                    'prefer_categories' => ['polaroids', 'albums'],
                    'bonus' => 0.35
                ],
                'gift boxes' => [
                    'prefer_categories' => ['bouquets'],
                    'bonus' => 0.45
                ],
                'chocolates' => [
                    'prefer_categories' => ['gift boxes', 'bouquets'],
                    'bonus' => 0.2
                ],
            ],
            // Keyword-based affinities in titles
            'by_anchor_keywords' => [
                'polaroid' => [
                    'prefer_categories' => ['frames', 'albums'],
                    'bonus' => 0.35
                ],
                'frame' => [
                    'prefer_categories' => ['polaroids', 'albums'],
                    'bonus' => 0.3
                ],
                'gift' => [
                    'prefer_categories' => ['gift boxes', 'bouquets'],
                    'bonus' => 0.3
                ],
                'giftbox' => [
                    'prefer_categories' => ['gift boxes', 'bouquets'],
                    'bonus' => 0.4
                ],
                'gift box' => [
                    'prefer_categories' => ['gift boxes', 'bouquets'],
                    'bonus' => 0.4
                ],
            ],
        ];

        // Fetch candidates (exclude itself)
        $sql = "SELECT $selectCols
                FROM artworks a
                LEFT JOIN categories c ON a.category_id = c.id
                WHERE $baseWhere AND a.id <> :aid";
        $params[':aid'] = $artworkId;

        // Prefer same category if available, but allow others
        // We'll compute a score in PHP: lower is better
        $sql .= " ORDER BY a.created_at DESC LIMIT 200"; // cap to 200 for scoring
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stmt->execute();
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $scored = [];
        // Prepare rule preference lists for quick checks
        $anchorCategoryName = $normalizeCategory($anchor['category_name'] ?? '');
        $anchorTitle = strtolower((string)($anchor['title'] ?? ''));
        $preferByCategory = [];
        $preferByKeywords = [];
        if ($anchorCategoryName !== '' && isset($RULES['by_anchor_category'][$anchorCategoryName])) {
            $preferByCategory = $RULES['by_anchor_category'][$anchorCategoryName]['prefer_categories'] ?? [];
            $bonusByCategory = (float)($RULES['by_anchor_category'][$anchorCategoryName]['bonus'] ?? 0.0);
        } else {
            $bonusByCategory = 0.0;
        }
        foreach ($RULES['by_anchor_keywords'] as $kw => $cfg) {
            if ($kw !== '' && strpos($anchorTitle, $kw) !== false) {
                foreach (($cfg['prefer_categories'] ?? []) as $pc) {
                    $preferByKeywords[$pc] = max((float)($cfg['bonus'] ?? 0.0), $preferByKeywords[$pc] ?? 0.0);
                }
            }
        }

        foreach ($candidates as $cand) {
            $priceCand = isset($cand['price']) ? (float)$cand['price'] : 0.0;
            $priceAnchor = (float)$anchor['price'];
            $priceDiff = abs($priceCand - $priceAnchor);
            $priceNorm = $priceAnchor > 0 ? ($priceDiff / max(1.0, $priceAnchor)) : ($priceDiff > 0 ? 1.0 : 0.0);

            $sameCategory = ((string)$cand['category_id'] === (string)$anchor['category_id']) ? 0.0 : 1.0;

            // simple title similarity: Jaccard on tokens
            $titleA = strtolower($anchor['title'] ?? '');
            $titleB = strtolower($cand['title'] ?? '');
            $tokensA = array_unique(array_filter(preg_split('/\W+/', $titleA)));
            $tokensB = array_unique(array_filter(preg_split('/\W+/', $titleB)));
            $inter = count(array_intersect($tokensA, $tokensB));
            $union = max(1, count(array_unique(array_merge($tokensA, $tokensB))));
            $jaccard = 1.0 - ($inter / $union); // distance (0=identical)

            // Weighted distance akin to KNN distance metric
            $distance = (0.6 * $sameCategory) + (0.3 * $priceNorm) + (0.1 * $jaccard);

            // Apply predefined rule bonuses (reduce distance to boost ranking)
            $ruleBonus = 0.0;
            $candCat = $normalizeCategory($cand['category_name'] ?? '');
            if (!empty($preferByCategory) && in_array($candCat, $preferByCategory, true)) {
                $ruleBonus = max($ruleBonus, $bonusByCategory);
            }
            if (!empty($preferByKeywords) && isset($preferByKeywords[$candCat])) {
                $ruleBonus = max($ruleBonus, (float)$preferByKeywords[$candCat]);
            }
            // Cap bonus to avoid negative distances
            $distance = max(0.0, $distance - min(0.5, $ruleBonus));
            $cand['__distance'] = $distance;
            $scored[] = $cand;
        }

        usort($scored, function($a, $b) {
            if ($a['__distance'] == $b['__distance']) return 0;
            return ($a['__distance'] < $b['__distance']) ? -1 : 1;
        });

        $recommendations = array_slice($scored, 0, $limit);
    } elseif ($userId > 0) {
        // Try get user's wishlist categories or orders (if tables exist)
        $preferredCategoryIds = [];
        try {
            $hasWishlist = $db->query("SHOW TABLES LIKE 'wishlist'");
            if ($hasWishlist && $hasWishlist->rowCount() > 0) {
                $st = $db->prepare("SELECT a.category_id, COUNT(*) cnt
                  FROM wishlist w JOIN artworks a ON w.artwork_id = a.id
                  WHERE w.user_id = :uid AND a.status='active'
                  GROUP BY a.category_id ORDER BY cnt DESC LIMIT 3");
                $st->bindValue(':uid', $userId, PDO::PARAM_INT);
                $st->execute();
                $preferredCategoryIds = array_values(array_filter(array_map(function($r){ return $r['category_id']; }, $st->fetchAll(PDO::FETCH_ASSOC))));
            }
        } catch (Throwable $e) {}

        $sql = "SELECT $selectCols FROM artworks a LEFT JOIN categories c ON a.category_id = c.id WHERE $baseWhere";
        if (!empty($preferredCategoryIds)) {
            $in = implode(',', array_map('intval', $preferredCategoryIds));
            $sql .= " AND a.category_id IN ($in)";
        }
        $sql .= " ORDER BY a.created_at DESC LIMIT :lim";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Generic popular/newest
        $stmt = $db->prepare("SELECT $selectCols FROM artworks a LEFT JOIN categories c ON a.category_id = c.id WHERE $baseWhere ORDER BY a.created_at DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // normalize pricing + offer flags mirroring artworks.php
    $now = new DateTime('now');
    foreach ($recommendations as &$artwork) {
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

        $effective = $artwork['price'] ?? 0.0;
        if ($isWindowOk) {
            if ($offerPrice !== null && $offerPrice > 0 && $offerPrice < $effective) {
                $effective = $offerPrice;
            } elseif ($offerPercent !== null && $offerPercent > 0 && $offerPercent <= 100) {
                $disc = round(($artwork['price'] ?? 0) * ($offerPercent / 100), 2);
                $candidate = max(0, ($artwork['price'] ?? 0) - $disc);
                if ($candidate < $effective) $effective = $candidate;
            }
        }
        $artwork['effective_price'] = (float)$effective;
        $artwork['is_on_offer'] = $isWindowOk && ($effective < (float)($artwork['price'] ?? 0));
        if (isset($artwork['force_offer_badge']) && (int)$artwork['force_offer_badge'] === 1) {
            $artwork['is_on_offer'] = true;
        }

        unset($artwork['__distance']);
    }

    echo json_encode([
        'status' => 'success',
        'recommendations' => array_values($recommendations),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>


