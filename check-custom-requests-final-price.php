<?php
require_once 'backend/config/database.php';

$db = new Database();
$pdo = $db->getConnection();

echo "Custom requests with final_price:\n";
$stmt = $pdo->query('SELECT id, title, final_price, budget FROM custom_requests WHERE final_price IS NOT NULL ORDER BY id DESC LIMIT 5');
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($requests as $req) {
    echo "Request {$req['id']}: {$req['title']} - Final Price: ₹{$req['final_price']}, Budget: {$req['budget']}\n";
}

echo "\nCustom artworks in cart:\n";
$artworkStmt = $pdo->query("
    SELECT a.id, a.title, a.price, a.description 
    FROM artworks a 
    WHERE a.category = 'custom' 
    ORDER BY a.id DESC LIMIT 5
");
$artworks = $artworkStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($artworks as $artwork) {
    echo "Artwork {$artwork['id']}: {$artwork['title']} - Price: ₹{$artwork['price']}\n";
    
    // Check if this artwork is linked to a custom request
    if (preg_match('/Request #(\d+)/', $artwork['description'], $matches)) {
        $requestId = $matches[1];
        echo "  -> Linked to Request #$requestId\n";
    }
}
?>