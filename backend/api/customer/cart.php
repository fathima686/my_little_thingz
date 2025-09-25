<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user ID robustly from headers or query
    $user_id = null;
    // Standard header mapping
    if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') {
        $user_id = $_SERVER['HTTP_X_USER_ID'];
    }
    // Alternative server mappings
    if (!$user_id) {
        $altKeys = ['REDIRECT_HTTP_X_USER_ID', 'X_USER_ID', 'HTTP_X_USERID'];
        foreach ($altKeys as $k) {
            if (isset($_SERVER[$k]) && $_SERVER[$k] !== '') {
                $user_id = $_SERVER[$k];
                break;
            }
        }
    }
    // getallheaders fallback
    if (!$user_id && function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $key => $value) {
            if (strtolower(trim($key)) === 'x-user-id' && $value !== '') {
                $user_id = $value;
                break;
            }
        }
    }
    // Query param fallback for manual testing
    if (!$user_id && isset($_GET['user_id']) && $_GET['user_id'] !== '') {
        $user_id = $_GET['user_id'];
    }

    if (!$user_id) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User ID required'
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch user's cart items (include offer columns if present)
        $hasOfferCols = false;
        try {
            $col = $db->query("SHOW COLUMNS FROM artworks LIKE 'offer_price'");
            if ($col && $col->rowCount() > 0) { $hasOfferCols = true; }
        } catch (Throwable $e) { $hasOfferCols = false; }

        $selectCols = "c.id, c.artwork_id, c.quantity, c.added_at, a.title, a.price, a.image_url, a.availability, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS artist_name";
        if ($hasOfferCols) {
            $selectCols .= ", a.offer_price, a.offer_percent, a.offer_starts_at, a.offer_ends_at";
            try {
                $col2 = $db->query("SHOW COLUMNS FROM artworks LIKE 'force_offer_badge'");
                if ($col2 && $col2->rowCount() > 0) {
                    $selectCols .= ", a.force_offer_badge";
                }
            } catch (Throwable $e) {}
        }

        $query = "SELECT $selectCols
                  FROM cart c
                  JOIN artworks a ON c.artwork_id = a.id
                  LEFT JOIN users u ON a.artist_id = u.id
                  WHERE c.user_id = ? AND a.status = 'active'
                  ORDER BY c.added_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Compute effective prices and totals
        $now = new DateTime('now');
        $total_effective = 0.0;
        foreach ($cart_items as &$item) {
            $basePrice = isset($item['price']) ? (float)$item['price'] : 0.0;
            $effective = $basePrice;

            if ($hasOfferCols) {
                $offerPrice   = isset($item['offer_price']) && $item['offer_price'] !== null ? (float)$item['offer_price'] : null;
                $offerPercent = isset($item['offer_percent']) && $item['offer_percent'] !== null ? (float)$item['offer_percent'] : null;
                $startsAt = isset($item['offer_starts_at']) && $item['offer_starts_at'] ? new DateTime($item['offer_starts_at']) : null;
                $endsAt   = isset($item['offer_ends_at']) && $item['offer_ends_at'] ? new DateTime($item['offer_ends_at']) : null;

                $isWindowOk = true;
                if ($startsAt && $now < $startsAt) $isWindowOk = false;
                if ($endsAt && $now > $endsAt) $isWindowOk = false;

                if ($isWindowOk) {
                    if ($offerPrice !== null && $offerPrice > 0 && $offerPrice < $effective) {
                        $effective = $offerPrice;
                    } elseif ($offerPercent !== null && $offerPercent > 0 && $offerPercent <= 100) {
                        $disc = round($basePrice * ($offerPercent / 100), 2);
                        $candidate = max(0, $basePrice - $disc);
                        if ($candidate < $effective) $effective = $candidate;
                    }
                }

                $item['is_on_offer'] = ($effective < $basePrice) && $isWindowOk;
                // If admin forced badge, show it even without price drop
                if (isset($item['force_offer_badge']) && (int)$item['force_offer_badge'] === 1) {
                    $item['is_on_offer'] = true;
                }
            } else {
                $item['is_on_offer'] = false;
            }

            $item['price'] = number_format($basePrice, 2); // keep original for display
            $item['effective_price'] = (float)$effective;
            $item_total_effective = $effective * ((int)$item['quantity']);
            $item['item_total'] = number_format($item_total_effective, 2);
            $total_effective += $item_total_effective;
        }

        echo json_encode([
            'status' => 'success',
            'cart_items' => $cart_items,
            'total' => number_format($total_effective, 2)
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Add item to cart
        $input = json_decode(file_get_contents('php://input'), true);
        $artwork_id = $input['artwork_id'] ?? null;
        $quantity = $input['quantity'] ?? 1;

        if (!$artwork_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Artwork ID required'
            ]);
            exit;
        }

        // Check if artwork exists and is available
        $artwork_query = "SELECT id, availability FROM artworks WHERE id = ? AND status = 'active'";
        $artwork_stmt = $db->prepare($artwork_query);
        $artwork_stmt->execute([$artwork_id]);
        $artwork = $artwork_stmt->fetch();

        if (!$artwork) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Artwork not found'
            ]);
            exit;
        }

        // Treat 'out_of_stock' (and similar) as not available; allow 'available', 'in_stock', 'made_to_order'
        $availability = isset($artwork['availability']) ? strtolower($artwork['availability']) : '';
        $notAvailable = in_array($availability, ['out_of_stock', 'unavailable', 'discontinued'], true);
        if ($notAvailable) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Artwork is not available'
            ]);
            exit;
        }

        // Check if item already exists in cart
        $check_query = "SELECT id, quantity FROM cart WHERE user_id = ? AND artwork_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$user_id, $artwork_id]);
        $existing_item = $check_stmt->fetch();

        if ($existing_item) {
            // Update quantity
            $new_quantity = $existing_item['quantity'] + $quantity;
            $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $result = $update_stmt->execute([$new_quantity, $existing_item['id']]);
        } else {
            // Add new item
            $insert_query = "INSERT INTO cart (user_id, artwork_id, quantity, added_at) VALUES (?, ?, ?, NOW())";
            $insert_stmt = $db->prepare($insert_query);
            $result = $insert_stmt->execute([$user_id, $artwork_id, $quantity]);
        }

        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Item added to cart'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to add item to cart'
            ]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Update cart item quantity
        $input = json_decode(file_get_contents('php://input'), true);
        $cart_id = $input['cart_id'] ?? null;
        $quantity = $input['quantity'] ?? null;

        if (!$cart_id || !$quantity) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Cart ID and quantity required'
            ]);
            exit;
        }

        $update_query = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if ($update_stmt->execute([$quantity, $cart_id, $user_id])) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Cart updated'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update cart'
            ]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Remove item from cart
        $input = json_decode(file_get_contents('php://input'), true);
        $cart_id = $input['cart_id'] ?? null;

        if (!$cart_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Cart ID required'
            ]);
            exit;
        }

        $delete_query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        $delete_stmt = $db->prepare($delete_query);
        
        if ($delete_stmt->execute([$cart_id, $user_id])) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Item removed from cart'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to remove item from cart'
            ]);
        }

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