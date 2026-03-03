<?php
/**
 * Fix orphaned completed requests that were not added to cart
 */
require_once 'backend/config/database.php';

$db = new Database();
$pdo = $db->getConnection();

echo "=== FIXING ORPHANED COMPLETED REQUESTS ===\n\n";

// Find completed requests without artworks
$orphanedStmt = $pdo->query("
    SELECT cr.id, cr.title, cr.status, cr.customer_id, cr.user_id, cr.description, 
           cr.budget_min, cr.budget_max, cr.final_price, cr.updated_at
    FROM custom_requests cr
    WHERE cr.status = 'completed'
    AND cr.id NOT IN (
        SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(a.description, 'Request #', -1), ')', 1) as req_id
        FROM artworks a 
        WHERE a.description LIKE '%Request #%'
        AND SUBSTRING_INDEX(SUBSTRING_INDEX(a.description, 'Request #', -1), ')', 1) REGEXP '^[0-9]+$'
    )
    ORDER BY cr.id DESC
");
$orphanedRequests = $orphanedStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($orphanedRequests)) {
    echo "✅ No orphaned requests found! All completed requests have corresponding artworks.\n";
    exit;
}

echo "Found " . count($orphanedRequests) . " orphaned completed requests:\n\n";

foreach ($orphanedRequests as $request) {
    echo "Processing Request #{$request['id']}: {$request['title']}\n";
    
    try {
        // Determine customer ID
        $customerId = $request['customer_id'] ?: $request['user_id'];
        if (!$customerId) {
            echo "  ❌ No customer ID found, skipping\n\n";
            continue;
        }
        
        // Determine price
        $finalPrice = 50.00; // Default
        if ($request['final_price'] && is_numeric($request['final_price'])) {
            $finalPrice = (float)$request['final_price'];
        } elseif ($request['budget_min'] && is_numeric($request['budget_min'])) {
            $finalPrice = (float)$request['budget_min'];
        } elseif ($request['budget_max'] && is_numeric($request['budget_max'])) {
            $finalPrice = (float)$request['budget_max'];
        }
        
        echo "  Customer ID: $customerId\n";
        echo "  Price: ₹$finalPrice\n";
        
        // Create artwork
        $artworkTitle = "Custom Design: " . ($request['title'] ?: "Request #{$request['id']}");
        $artworkDescription = ($request['description'] ?: "Custom designed item") . " (Request #{$request['id']})";
        
        $insertArtworkStmt = $pdo->prepare("
            INSERT INTO artworks (
                title, description, price, image_url, 
                status, availability, artist_id, category,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, 'active', 'available', 1, 'custom', NOW(), NOW())
        ");
        
        $insertArtworkStmt->execute([
            $artworkTitle,
            $artworkDescription,
            $finalPrice,
            'uploads/designs/default-custom.jpg'
        ]);
        
        $artworkId = $pdo->lastInsertId();
        echo "  ✅ Created artwork #$artworkId\n";
        
        // Add to cart if not already there
        $checkCartStmt = $pdo->prepare("
            SELECT id FROM cart 
            WHERE user_id = ? AND artwork_id = ?
        ");
        $checkCartStmt->execute([$customerId, $artworkId]);
        $existingCartItem = $checkCartStmt->fetch();
        
        if (!$existingCartItem) {
            $insertCartStmt = $pdo->prepare("
                INSERT INTO cart (user_id, artwork_id, quantity, added_at)
                VALUES (?, ?, 1, NOW())
            ");
            $insertCartStmt->execute([$customerId, $artworkId]);
            echo "  ✅ Added to cart for customer #$customerId\n";
        } else {
            echo "  ℹ️  Already in cart\n";
        }
        
        // Update request workflow stage
        $updateRequestStmt = $pdo->prepare("
            UPDATE custom_requests 
            SET workflow_stage = 'design_completed', 
                design_completed_at = NOW(),
                final_price = ?
            WHERE id = ?
        ");
        $updateRequestStmt->execute([$finalPrice, $request['id']]);
        echo "  ✅ Updated workflow stage to 'design_completed'\n";
        
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== VERIFICATION ===\n";

// Check results
$verifyStmt = $pdo->query("
    SELECT COUNT(*) as count
    FROM custom_requests cr
    WHERE cr.status = 'completed'
    AND cr.id NOT IN (
        SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(a.description, 'Request #', -1), ')', 1) as req_id
        FROM artworks a 
        WHERE a.description LIKE '%Request #%'
        AND SUBSTRING_INDEX(SUBSTRING_INDEX(a.description, 'Request #', -1), ')', 1) REGEXP '^[0-9]+$'
    )
");
$remainingOrphaned = $verifyStmt->fetchColumn();

echo "Remaining orphaned requests: $remainingOrphaned\n";

if ($remainingOrphaned == 0) {
    echo "✅ All completed requests now have corresponding artworks!\n";
} else {
    echo "⚠️  Some requests still need manual attention.\n";
}

// Show cart summary
$cartSummaryStmt = $pdo->query("
    SELECT COUNT(*) as count
    FROM cart c
    JOIN artworks a ON c.artwork_id = a.id
    WHERE a.category = 'custom' OR a.title LIKE '%Custom Design%'
");
$customCartItems = $cartSummaryStmt->fetchColumn();

echo "Custom designs in cart: $customCartItems\n";
echo "\n=== FIX COMPLETE ===\n";
?>