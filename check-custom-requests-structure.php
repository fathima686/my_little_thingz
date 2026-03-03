<?php
require_once 'backend/config/database.php';

$db = new Database();
$pdo = $db->getConnection();

echo "Custom requests table structure:\n";
$stmt = $pdo->query('DESCRIBE custom_requests');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo $col['Field'] . ' (' . $col['Type'] . ")\n";
}

echo "\nSample custom requests with final_price:\n";
$stmt = $pdo->query('SELECT id, title, final_price FROM custom_requests WHERE final_price IS NOT NULL ORDER BY id DESC LIMIT 5');
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($requests as $req) {
    echo "Request {$req['id']}: {$req['title']} - Final Price: ₹{$req['final_price']}\n";
}

echo "\nCustom artworks:\n";
$artworkStmt = $pdo->query("
    SELECT a.id, a.title, a.price, a.description, a.category
    FROM artworks a 
    WHERE a.category = 'custom' OR a.title LIKE '%Custom Design%'
    ORDER BY a.id DESC LIMIT 5
");
$artworks = $artworkStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($artworks as $artwork) {
    echo "Artwork {$artwork['id']}: {$artwork['title']} - Price: ₹{$artwork['price']} - Category: {$artwork['category']}\n";
    
    // Check if this artwork is linked to a custom request
    if (preg_match('/Request #(\d+)/', $artwork['description'], $matches)) {
        $requestId = $matches[1];
        echo "  -> Linked to Request #$requestId\n";
        
        // Get the final_price for this request
        $reqStmt = $pdo->prepare("SELECT final_price FROM custom_requests WHERE id = ?");
        $reqStmt->execute([$requestId]);
        $reqData = $reqStmt->fetch(PDO::FETCH_ASSOC);
        if ($reqData && $reqData['final_price']) {
            echo "  -> Request final_price: ₹{$reqData['final_price']}\n";
        }
    }
}
?>