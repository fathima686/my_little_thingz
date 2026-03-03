<?php
require_once 'backend/config/database.php';

$db = new Database();
$pdo = $db->getConnection();

echo "All custom requests:\n";
$stmt = $pdo->query('SELECT id, title, final_price, budget_min, budget_max, status FROM custom_requests ORDER BY id DESC LIMIT 10');
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($requests as $req) {
    echo "Request {$req['id']}: {$req['title']} - Status: {$req['status']}\n";
    echo "  Final: ₹" . ($req['final_price'] ?? 'NULL') . ", Budget: ₹{$req['budget_min']}-₹{$req['budget_max']}\n";
    
    // Check if there's an artwork for this request
    $artworkStmt = $pdo->prepare("SELECT id, title, price FROM artworks WHERE description LIKE ?");
    $artworkStmt->execute(["%Request #{$req['id']}%"]);
    $artwork = $artworkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($artwork) {
        echo "  -> Artwork {$artwork['id']}: {$artwork['title']} - Price: ₹{$artwork['price']}\n";
        
        // Check if artwork is in any cart
        $cartStmt = $pdo->prepare("SELECT id, user_id FROM cart WHERE artwork_id = ?");
        $cartStmt->execute([$artwork['id']]);
        $cartItem = $cartStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cartItem) {
            echo "  -> In cart {$cartItem['id']} for user {$cartItem['user_id']}\n";
        }
    }
    echo "\n";
}
?>