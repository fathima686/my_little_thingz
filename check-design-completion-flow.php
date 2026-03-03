<?php
require_once 'backend/config/database.php';

$db = new Database();
$pdo = $db->getConnection();

echo "=== DESIGN COMPLETION FLOW ANALYSIS ===\n\n";

// Check custom_request_designs table
echo "1. Custom Request Designs Table:\n";
try {
    $designStmt = $pdo->query("
        SELECT crd.id, crd.request_id, crd.status, crd.created_at, crd.updated_at,
               cr.title, cr.status as request_status, cr.final_price
        FROM custom_request_designs crd
        JOIN custom_requests cr ON crd.request_id = cr.id
        ORDER BY crd.id DESC LIMIT 10
    ");
    $designs = $designStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($designs)) {
        echo "   No design records found!\n";
    } else {
        foreach ($designs as $design) {
            echo "   Design {$design['id']}: Request #{$design['request_id']} ({$design['title']})\n";
            echo "     Design Status: {$design['status']}\n";
            echo "     Request Status: {$design['request_status']}\n";
            echo "     Final Price: ₹" . ($design['final_price'] ?? 'NULL') . "\n";
            echo "     Updated: {$design['updated_at']}\n\n";
        }
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "2. Recent Completed Requests Without Artworks:\n";
$completedStmt = $pdo->query("
    SELECT cr.id, cr.title, cr.status, cr.final_price, cr.updated_at
    FROM custom_requests cr
    WHERE cr.status = 'completed'
    AND cr.id NOT IN (
        SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(a.description, 'Request #', -1), ')', 1) as req_id
        FROM artworks a 
        WHERE a.description LIKE '%Request #%'
        AND SUBSTRING_INDEX(SUBSTRING_INDEX(a.description, 'Request #', -1), ')', 1) REGEXP '^[0-9]+$'
    )
    ORDER BY cr.id DESC LIMIT 5
");
$orphanedRequests = $completedStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($orphanedRequests)) {
    echo "   All completed requests have corresponding artworks!\n";
} else {
    foreach ($orphanedRequests as $req) {
        echo "   Request #{$req['id']}: {$req['title']}\n";
        echo "     Status: {$req['status']}\n";
        echo "     Final Price: ₹" . ($req['final_price'] ?? 'NULL') . "\n";
        echo "     Updated: {$req['updated_at']}\n";
        echo "     ❌ NO ARTWORK FOUND - Design not added to cart!\n\n";
    }
}

echo "3. Cart Items for Custom Designs:\n";
$cartStmt = $pdo->query("
    SELECT c.id, c.user_id, c.artwork_id, a.title, a.price, a.description
    FROM cart c
    JOIN artworks a ON c.artwork_id = a.id
    WHERE a.category = 'custom' OR a.title LIKE '%Custom Design%'
    ORDER BY c.id DESC LIMIT 5
");
$cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cartItems)) {
    echo "   No custom designs in any cart!\n";
} else {
    foreach ($cartItems as $item) {
        echo "   Cart {$item['id']}: User {$item['user_id']} - {$item['title']} (₹{$item['price']})\n";
        if (preg_match('/Request #(\d+)/', $item['description'], $matches)) {
            echo "     -> Linked to Request #{$matches[1]}\n";
        }
    }
}

echo "\n4. Workflow Stage Analysis:\n";
$workflowStmt = $pdo->query("
    SELECT workflow_stage, COUNT(*) as count
    FROM custom_requests
    WHERE status = 'completed'
    GROUP BY workflow_stage
    ORDER BY count DESC
");
$workflowStats = $workflowStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($workflowStats as $stat) {
    echo "   {$stat['workflow_stage']}: {$stat['count']} requests\n";
}

echo "\n=== DIAGNOSIS ===\n";
echo "Checking if design completion flow is working...\n";

// Check the most recent request that should have been completed
$recentStmt = $pdo->query("
    SELECT id, title, status, workflow_stage, final_price, updated_at
    FROM custom_requests
    WHERE status = 'completed'
    ORDER BY updated_at DESC LIMIT 1
");
$recentRequest = $recentStmt->fetch(PDO::FETCH_ASSOC);

if ($recentRequest) {
    $reqId = $recentRequest['id'];
    echo "Most recent completed request: #{$reqId} - {$recentRequest['title']}\n";
    echo "Workflow Stage: {$recentRequest['workflow_stage']}\n";
    echo "Final Price: ₹" . ($recentRequest['final_price'] ?? 'NULL') . "\n";
    
    // Check if it has a design record
    $designCheckStmt = $pdo->prepare("SELECT id, status FROM custom_request_designs WHERE request_id = ?");
    $designCheckStmt->execute([$reqId]);
    $designRecord = $designCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($designRecord) {
        echo "✅ Has design record (ID: {$designRecord['id']}, Status: {$designRecord['status']})\n";
    } else {
        echo "❌ NO design record found - request completed without design editor!\n";
    }
    
    // Check if it has an artwork
    $artworkCheckStmt = $pdo->prepare("SELECT id, title, price FROM artworks WHERE description LIKE ?");
    $artworkCheckStmt->execute(["%Request #$reqId%"]);
    $artworkRecord = $artworkCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($artworkRecord) {
        echo "✅ Has artwork (ID: {$artworkRecord['id']}, Price: ₹{$artworkRecord['price']})\n";
    } else {
        echo "❌ NO artwork found - not added to cart!\n";
    }
}
?>