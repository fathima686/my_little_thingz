<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once '../../config/database.php';
$config = require __DIR__ . '/../../config/razorpay.php';

// Simple signature util without SDK
function rp_sign($data, $secret) {
    return hash_hmac('sha256', $data, $secret);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    // Resolve user_id from headers/query/body
    $user_id = null;
    if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') { $user_id = $_SERVER['HTTP_X_USER_ID']; }
    if (!$user_id && function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) { if (strtolower($k) === 'x-user-id' && $v !== '') { $user_id = $v; break; } }
    }
    if (!$user_id && isset($_GET['user_id']) && $_GET['user_id'] !== '') { $user_id = $_GET['user_id']; }
    if (!$user_id) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        if (!empty($input['user_id'])) { $user_id = $input['user_id']; }
    }
    if (!$user_id) { echo json_encode(['status' => 'error', 'message' => 'User ID required']); exit; }

    $database = new Database();
    $db = $database->getConnection();

    // Load cart with current prices, weight, and offer columns, check for customization requirements. Include pricing_schema if exists.
    $hasPricing = false;
    try { $chk = $db->query("SHOW COLUMNS FROM artworks LIKE 'pricing_schema'"); $hasPricing = $chk && $chk->rowCount() > 0; } catch (Throwable $e) { $hasPricing = false; }
    $selectCols = "c.id as cart_id, c.artwork_id, c.quantity, a.price, a.title, a.weight, a.requires_customization, a.offer_price, a.offer_percent, a.offer_starts_at, a.offer_ends_at";
    if ($hasPricing) { $selectCols .= ", a.pricing_schema"; }
    $stmt = $db->prepare("SELECT $selectCols FROM cart c JOIN artworks a ON c.artwork_id=a.id WHERE c.user_id=? AND a.status='active'");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$cart) { echo json_encode(['status'=>'error','message'=>'Cart is empty']); exit; }

    // Check if any items require customization
    $customization_required = false;
    foreach ($cart as $item) {
        if (!empty($item['requires_customization'])) {
            $customization_required = true;
            break;
        }
    }

    // If customization is required for any cart item, ensure the user has at least one approved custom request
    if ($customization_required) {
        $customization_stmt = $db->prepare("SELECT id FROM custom_requests WHERE user_id=? AND status='completed' ORDER BY created_at DESC LIMIT 1");
        $customization_stmt->execute([$user_id]);
        $customization = $customization_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$customization) {
            echo json_encode(['status' => 'error', 'message' => 'Customization request must be approved by admin before payment.']);
            exit;
        }
    }

    // Option-aware recomputation: allow client to pass selected_options per artwork
    $bodyInput = json_decode(file_get_contents('php://input'), true) ?: [];
    $clientItems = [];
    if (!empty($bodyInput['items']) && is_array($bodyInput['items'])) {
        foreach ($bodyInput['items'] as $ci) {
            if (isset($ci['artwork_id'])) { $clientItems[(int)$ci['artwork_id']] = $ci; }
        }
    }

    // Helper to compute option delta
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

    // Compute effective prices (offer-aware + options + rush) and total weight
    $now = new DateTime('now');
    $subtotal = 0.0;
    $totalWeight = 0.0;
    foreach ($cart as &$it) {
        if (!$hasPricing) { $it['pricing_schema'] = null; }
        $base = (float)$it['price'];
        $effective = $base;
        $offerPrice   = isset($it['offer_price']) && $it['offer_price'] !== null ? (float)$it['offer_price'] : null;
        $offerPercent = isset($it['offer_percent']) && $it['offer_percent'] !== null ? (float)$it['offer_percent'] : null;
        $startsAt = isset($it['offer_starts_at']) && $it['offer_starts_at'] ? new DateTime($it['offer_starts_at']) : null;
        $endsAt   = isset($it['offer_ends_at']) && $it['offer_ends_at'] ? new DateTime($it['offer_ends_at']) : null;
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
        // Apply option deltas if client provided selected_options
        $sel = $clientItems[(int)$it['artwork_id']]['selected_options'] ?? [];
        if (!empty($sel)) {
            $effective = $computeWithOptions($effective, $it['pricing_schema'] ?? null, $sel);
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
        $it['effective_price'] = $effective;
        $subtotal += $effective * ((int)$it['quantity']);
        
        // Calculate total weight
        $itemWeight = isset($it['weight']) && $it['weight'] > 0 ? (float)$it['weight'] : 0.5; // Default 0.5 kg
        $totalWeight += $itemWeight * ((int)$it['quantity']);
    }
    unset($it);

    // Calculate shipping charges: ₹60 per kg, minimum ₹60
    $tax = 0.0;
    $shipping = max(60.0, ceil($totalWeight) * 60.0); // Round up weight to nearest kg
    
    // Extract and calculate addon costs
    $addon_total = 0.0;
    $selected_addons = [];
    if (!empty($bodyInput['selected_addons']) && is_array($bodyInput['selected_addons'])) {
        foreach ($bodyInput['selected_addons'] as $addon) {
            if (isset($addon['id'], $addon['name'], $addon['price'])) {
                $addon_price = (float)$addon['price'];
                $addon_total += $addon_price;
                $selected_addons[] = $addon;
            }
        }
    }
    
    $total = $subtotal + $tax + $shipping + $addon_total;

    // Grab shipping address from request body (if provided)
    $shipping_address = $bodyInput['shipping_address'] ?? null;

    // Create local pending order first
    $db->beginTransaction();
    $order_number = 'ORD-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)),0,6);
    $ins = $db->prepare("INSERT INTO orders (user_id, order_number, status, payment_method, payment_status, total_amount, subtotal, tax_amount, shipping_cost, weight, shipping_address, created_at) VALUES (?, ?, 'pending', 'razorpay', 'pending', ?, ?, ?, ?, ?, ?, NOW())");
    $ins->execute([$user_id, $order_number, $total, $subtotal, $tax, $shipping, $totalWeight, $shipping_address]);
    $order_id = (int)$db->lastInsertId();

    // Insert order items (persist selected_options if column exists)
    $hasSelCol = false;
    try { $chk = $db->query("SHOW COLUMNS FROM order_items LIKE 'selected_options'"); $hasSelCol = $chk && $chk->rowCount() > 0; } catch (Throwable $e) {}

    if ($hasSelCol) {
        $insItem = $db->prepare("INSERT INTO order_items (order_id, artwork_id, quantity, price, selected_options) VALUES (?, ?, ?, ?, ?)");
        foreach ($cart as $it) {
            $sel = $clientItems[(int)$it['artwork_id']]['selected_options'] ?? [];
            $insItem->execute([$order_id, $it['artwork_id'], $it['quantity'], $it['effective_price'] ?? $it['price'], json_encode($sel)]);
        }
    } else {
        $insItem = $db->prepare("INSERT INTO order_items (order_id, artwork_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cart as $it) { $insItem->execute([$order_id, $it['artwork_id'], $it['quantity'], $it['effective_price'] ?? $it['price']]); }
    }

    // Store selected addons if table exists
    $hasAddonsTable = false;
    try { 
        $chk = $db->query("SHOW TABLES LIKE 'order_addons'"); 
        $hasAddonsTable = $chk && $chk->rowCount() > 0; 
    } catch (Throwable $e) {}
    
    if ($hasAddonsTable && !empty($selected_addons)) {
        $insAddon = $db->prepare("INSERT INTO order_addons (order_id, addon_id, addon_name, addon_price, created_at) VALUES (?, ?, ?, ?, NOW())");
        foreach ($selected_addons as $addon) {
            $insAddon->execute([$order_id, $addon['id'], $addon['name'], (float)$addon['price']]);
        }
    }

    // Create Razorpay order via REST (no SDK)
    $amountPaise = (int) round($total * 100);
    $payload = json_encode(['amount' => $amountPaise, 'currency' => $config['currency'], 'receipt' => $order_number, 'payment_capture' => 1]);
    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_USERPWD, $config['key_id'] . ':' . $config['key_secret']);
    $resp = curl_exec($ch);
    if ($resp === false) { throw new Exception('Razorpay API error: ' . curl_error($ch)); }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $rp = json_decode($resp, true);
    if ($code >= 400 || !isset($rp['id'])) { throw new Exception('Failed to create Razorpay order: ' . $resp); }

    // Save rp order id
    $upd = $db->prepare("UPDATE orders SET razorpay_order_id=? WHERE id=?");
    $upd->execute([$rp['id'], $order_id]);

    $db->commit();

    echo json_encode([
        'status' => 'success',
        'order' => [
            'id' => $order_id,
            'order_number' => $order_number,
            'razorpay_order_id' => $rp['id'],
            'amount' => $total,
            'currency' => $config['currency'],
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'addon_total' => $addon_total,
            'weight' => $totalWeight
        ],
        'key_id' => $config['key_id']
    ]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}