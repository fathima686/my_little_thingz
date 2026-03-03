<?php
require_once 'backend/config/database.php';

$db = new Database();
$pdo = $db->getConnection();

echo "Custom items in cart:\n";
$stmt = $pdo->query("
    SELECT c.id, c.user_id, c.artwork_id, a.title, a.price, a.category, a.description
    FROM cart c 
    JOIN artworks a ON c.artwork_id = a.id 
    WHERE a.category = 'custom' OR a.title LIKE '%Custom Design%'
    ORDER BY c.id DESC LIMIT 10
");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($items as $item) {
    echo "Cart {$item['id']}: User {$item['user_id']} - Artwork {$item['artwork_id']} ({$item['title']}) - Price: ₹{$item['price']}\n";
    
    // Check if linked to custom request
    if (preg_match('/Request #(\d+)/', $item['description'], $matches)) {
        $requestId = $matches[1];
        echo "  -> Linked to Request #$requestId\n";
        
        // Get the final_price for this request
        $reqStmt = $pdo->prepare("SELECT final_price FROM custom_requests WHERE id = ?");
        $reqStmt->execute([$requestId]);
        $reqData = $reqStmt->fetch(PDO::FETCH_ASSOC);
        if ($reqData && $reqData['final_price']) {
            echo "  -> Request final_price: ₹{$reqData['final_price']} (should override artwork price ₹{$item['price']})\n";
        }
    }
}

if (empty($items)) {
    echo "No custom items found in cart. Let's check all cart items:\n";
    $allStmt = $pdo->query("SELECT c.id, c.user_id, c.artwork_id, a.title, a.price FROM cart c JOIN artworks a ON c.artwork_id = a.id ORDER BY c.id DESC LIMIT 5");
    $allItems = $allStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allItems as $item) {
        echo "Cart {$item['id']}: User {$item['user_id']} - Artwork {$item['artwork_id']} ({$item['title']}) - Price: ₹{$item['price']}\n";
    }
}
?>