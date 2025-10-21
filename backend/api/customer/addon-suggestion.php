<?php
/**
 * Add-on Suggestion API Endpoint
 * 
 * GET: Get suggested add-ons based on cart total
 * POST: Not used for this feature
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';
require_once '../../services/DecisionTreeAddonSuggester.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    // Get user ID from headers
    $user_id = null;
    if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') {
        $user_id = $_SERVER['HTTP_X_USER_ID'];
    }

    if (!$user_id && function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $key => $value) {
            if (strtolower(trim($key)) === 'x-user-id' && $value !== '') {
                $user_id = $value;
                break;
            }
        }
    }

    if (!$user_id && isset($_GET['user_id']) && $_GET['user_id'] !== '') {
        $user_id = $_GET['user_id'];
    }

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'User ID required']);
        exit;
    }

    // Connect to database
    $database = new Database();
    $db = $database->getConnection();

    // Fetch cart items
    $cartQuery = "SELECT c.id as cart_id, c.artwork_id, c.quantity, a.price, a.offer_price, a.offer_percent, a.offer_starts_at, a.offer_ends_at
                  FROM cart c 
                  JOIN artworks a ON c.artwork_id = a.id 
                  WHERE c.user_id = ? AND a.status = 'active'";
    
    $cartStmt = $db->prepare($cartQuery);
    $cartStmt->execute([$user_id]);
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$cartItems || count($cartItems) === 0) {
        echo json_encode([
            'status' => 'success',
            'suggested_addons' => [],
            'message' => 'Cart is empty'
        ]);
        exit;
    }

    // Calculate cart total (accounting for offers)
    $now = new DateTime('now');
    $cartTotal = 0.0;

    foreach ($cartItems as $item) {
        $base = (float)$item['price'];
        $effective = $base;

        $offerPrice = isset($item['offer_price']) && $item['offer_price'] !== null ? (float)$item['offer_price'] : null;
        $offerPercent = isset($item['offer_percent']) && $item['offer_percent'] !== null ? (float)$item['offer_percent'] : null;
        $startsAt = isset($item['offer_starts_at']) && $item['offer_starts_at'] ? new DateTime($item['offer_starts_at']) : null;
        $endsAt = isset($item['offer_ends_at']) && $item['offer_ends_at'] ? new DateTime($item['offer_ends_at']) : null;

        $isWindowOk = true;
        if ($startsAt && $now < $startsAt) $isWindowOk = false;
        if ($endsAt && $now > $endsAt) $isWindowOk = false;

        if ($isWindowOk) {
            if ($offerPrice !== null && $offerPrice > 0 && $offerPrice < $effective) {
                $effective = $offerPrice;
            } elseif ($offerPercent !== null && $offerPercent > 0 && $offerPercent <= 100) {
                $disc = round($base * ($offerPercent / 100), 2);
                $candidate = max(0, $base - $disc);
                if ($candidate < $effective) $effective = $candidate;
            }
        }

        $cartTotal += $effective * ((int)$item['quantity']);
    }

    // Use Decision Tree to suggest add-ons
    $suggestionResult = DecisionTreeAddonSuggester::suggestAddons($cartTotal, $cartItems);

    // Return success with suggestions
    echo json_encode([
        'status' => 'success',
        'cart_total' => round($cartTotal, 2),
        'suggested_addons' => $suggestionResult['suggested_addons'],
        'applied_rule' => $suggestionResult['applied_rule'],
        'reasoning' => $suggestionResult['reasoning']
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to get suggestions: ' . $e->getMessage()
    ]);
}
?>