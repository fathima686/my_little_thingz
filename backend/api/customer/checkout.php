<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Get user ID robustly from headers or query (align with wishlist/cart)
    $user_id = null;
    if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') {
        $user_id = $_SERVER['HTTP_X_USER_ID'];
    }
    if (!$user_id) {
        $altKeys = ['REDIRECT_HTTP_X_USER_ID', 'X_USER_ID', 'HTTP_X_USERID'];
        foreach ($altKeys as $k) {
            if (isset($_SERVER[$k]) && $_SERVER[$k] !== '') {
                $user_id = $_SERVER[$k];
                break;
            }
        }
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

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $shipping_address = $input['shipping_address'] ?? null;
    $selected_addons = $input['selected_addons'] ?? [];

    // Begin transaction
    $db->beginTransaction();

    // Fetch cart items with latest artwork data, offer columns, and pricing schema (if exists)
    $hasPricing = false;
    try {
        $chk = $db->query("SHOW COLUMNS FROM artworks LIKE 'pricing_schema'");
        $hasPricing = $chk && $chk->rowCount() > 0;
    } catch (Throwable $e) { $hasPricing = false; }

    $selectCols = "c.id as cart_id, c.artwork_id, c.quantity, a.price, a.title, a.image_url, a.offer_price, a.offer_percent, a.offer_starts_at, a.offer_ends_at";
    if ($hasPricing) { $selectCols .= ", a.pricing_schema"; }
    $cartQuery = "SELECT $selectCols FROM cart c JOIN artworks a ON c.artwork_id = a.id WHERE c.user_id = ? AND a.status = 'active'";
    $cartStmt = $db->prepare($cartQuery);
    $cartStmt->execute([$user_id]);
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$cartItems || count($cartItems) === 0) {
        $db->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
        exit;
    }

    // Option-aware recomputation: allow client to pass selected_options per artwork
    $clientItems = [];
    if (!empty($input['items']) && is_array($input['items'])) {
        foreach ($input['items'] as $ci) {
            if (isset($ci['artwork_id'])) { $clientItems[(int)$ci['artwork_id']] = $ci; }
        }
    }

    $computeWithOptions = function ($base, $schemaJson, $selected) {
        $subtotal = (float)$base;
        if (!$schemaJson) return $subtotal;
        $schema = json_decode($schemaJson, true);
        if (!is_array($schema) || empty($schema['options'])) return $subtotal;
        $options = $schema['options'];
        foreach ($options as $key => $spec) {
            if (!isset($spec['type'])) continue;
            if ($spec['type'] === 'select') {
                $val = $selected[$key] ?? null;
                if ($val === null) continue;
                $found = null;
                foreach (($spec['values'] ?? []) as $v) { if ((string)$v['value'] === (string)$val) { $found = $v; break; } }
                if ($found && isset($found['delta'])) {
                    $d = $found['delta'];
                    if (($d['type'] ?? '') === 'flat') $subtotal += (float)($d['value'] ?? 0);
                    if (($d['type'] ?? '') === 'percent') $subtotal += round($base * ((float)($d['value'] ?? 0) / 100), 2);
                }
            } elseif ($spec['type'] === 'range') {
                // Ignore character-length based pricing
                $unit = isset($spec['unit']) ? strtolower((string)$spec['unit']) : '';
                if ($unit === 'chars' || in_array($key, ['messageLength','textLength'], true)) { continue; }
                $val = isset($selected[$key]) ? (float)$selected[$key] : null;
                if ($val === null) continue;
                $tiers = $spec['tiers'] ?? [];
                $applied = null;
                foreach ($tiers as $t) { if ($val <= (float)$t['max']) { $applied = $t; break; } }
                if ($applied && isset($applied['delta'])) {
                    $d = $applied['delta'];
                    if (($d['type'] ?? '') === 'flat') $subtotal += (float)($d['value'] ?? 0);
                    if (($d['type'] ?? '') === 'percent') $subtotal += round($base * ((float)($d['value'] ?? 0) / 100), 2);
                }
            }
        }
        return $subtotal;
    };

    // Compute totals with effective offer prices + options (+ rush by deliveryDate)
    $now = new DateTime('now');
    $subtotal = 0.0;
    foreach ($cartItems as &$item) {
        if (!$hasPricing) { $item['pricing_schema'] = null; }
        $base = (float)$item['price'];
        $effective = $base;
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
                $disc = round($base * ($offerPercent / 100), 2);
                $candidate = max(0, $base - $disc);
                if ($candidate < $effective) $effective = $candidate;
            }
        }
        // Apply option deltas if present
        $sel = $clientItems[(int)$item['artwork_id']]['selected_options'] ?? [];
        if (!empty($sel)) {
            $effective = $computeWithOptions($effective, $item['pricing_schema'] ?? null, $sel);
        }
        // Rush fee if deliveryDate within 2 days
        if (!empty($sel['deliveryDate'])) {
            try {
                $selDate = new DateTime($sel['deliveryDate']);
                $today = new DateTime('today');
                $diff = (int)$today->diff($selDate)->format('%r%a');
                if ($diff >= 0 && $diff <= 2) { $effective += 50.0; }
            } catch (Throwable $e) {}
        }
        $item['effective_price'] = $effective;
        $subtotal += $effective * ((int)$item['quantity']);
    }
    unset($item);

    // Calculate addon total
    $addon_total = 0.0;
    foreach ($selected_addons as $addon) {
        if (isset($addon['price']) && is_numeric($addon['price'])) {
            $addon_total += (float)$addon['price'];
        }
    }

    $tax = 0.0; // extend later if needed
    $shipping = 0.0; // extend later if needed
    $total = $subtotal + $tax + $shipping + $addon_total;

    // Generate order number
    $order_number = 'ORD-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

    // Insert order
    $orderInsert = "INSERT INTO orders (user_id, order_number, status, total_amount, subtotal, tax_amount, shipping_cost, shipping_address, created_at)
                    VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, NOW())";
    $orderStmt = $db->prepare($orderInsert);
    $orderStmt->execute([$user_id, $order_number, $total, $subtotal, $tax, $shipping, $shipping_address]);
    $order_id = (int)$db->lastInsertId();

    // Insert order items (persist selected_options if column exists)
    $hasSelCol = false;
    try { $chk = $db->query("SHOW COLUMNS FROM order_items LIKE 'selected_options'"); $hasSelCol = $chk && $chk->rowCount() > 0; } catch (Throwable $e) {}

    if ($hasSelCol) {
        $itemInsert = $db->prepare("INSERT INTO order_items (order_id, artwork_id, quantity, price, selected_options) VALUES (?, ?, ?, ?, ?)");
        foreach ($cartItems as $item) {
            $priceToUse = isset($item['effective_price']) ? $item['effective_price'] : $item['price'];
            $sel = $clientItems[(int)$item['artwork_id']]['selected_options'] ?? [];
            $itemInsert->execute([$order_id, $item['artwork_id'], $item['quantity'], $priceToUse, json_encode($sel)]);
        }
    } else {
        $itemInsert = $db->prepare("INSERT INTO order_items (order_id, artwork_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cartItems as $item) {
            $priceToUse = isset($item['effective_price']) ? $item['effective_price'] : $item['price'];
            $itemInsert->execute([$order_id, $item['artwork_id'], $item['quantity'], $priceToUse]);
        }
    }

    // Insert order addons
    if (!empty($selected_addons)) {
        try {
            // Check if order_addons table exists
            $chk = $db->query("SHOW TABLES LIKE 'order_addons'");
            $hasAddonTable = $chk && $chk->rowCount() > 0;
            
            if ($hasAddonTable) {
                $addonInsert = $db->prepare("INSERT INTO order_addons (order_id, addon_id, addon_name, addon_price) VALUES (?, ?, ?, ?)");
                foreach ($selected_addons as $addon) {
                    $addonId = $addon['id'] ?? 'unknown';
                    $addonName = $addon['name'] ?? 'Unknown Add-on';
                    $addonPrice = isset($addon['price']) ? (float)$addon['price'] : 0;
                    $addonInsert->execute([$order_id, $addonId, $addonName, $addonPrice]);
                }
            }
        } catch (Throwable $e) {
            // Silently ignore if table doesn't exist yet
        }
    }

    // Clear cart
    $clearStmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
    $clearStmt->execute([$user_id]);

    $db->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Order placed successfully',
        'order' => [
            'id' => $order_id,
            'order_number' => $order_number,
            'total_amount' => number_format($total, 2),
            'subtotal' => number_format($subtotal, 2),
            'addon_total' => number_format($addon_total, 2),
            'tax_amount' => number_format($tax, 2),
            'shipping_cost' => number_format($shipping, 2),
            'addons' => $selected_addons
        ]
    ]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Checkout error: ' . $e->getMessage()
    ]);
}
