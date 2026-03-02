<?php
/**
 * Fix cart customization issue for testing
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Fixing cart customization issue...\n\n";
    
    // Check current cart items
    $stmt = $db->prepare("
        SELECT c.*, a.title, a.requires_customization 
        FROM cart c 
        JOIN artworks a ON c.artwork_id = a.id 
        WHERE c.user_id = 5
    ");
    $stmt->execute();
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current cart items:\n";
    foreach ($cartItems as $item) {
        echo "- {$item['title']} (requires_customization: " . ($item['requires_customization'] ? 'YES' : 'NO') . ")\n";
    }
    
    // Update artworks to not require customization for testing
    $updateStmt = $db->prepare("UPDATE artworks SET requires_customization = 0 WHERE id IN (SELECT artwork_id FROM cart WHERE user_id = 5)");
    $updateStmt->execute();
    
    echo "\n✓ Updated artworks to not require customization\n";
    
    // Verify the change
    $stmt->execute();
    $updatedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nUpdated cart items:\n";
    foreach ($updatedItems as $item) {
        echo "- {$item['title']} (requires_customization: " . ($item['requires_customization'] ? 'YES' : 'NO') . ")\n";
    }
    
    echo "\n=== CART FIX COMPLETE ===\n";
    echo "You can now test payment without customization approval.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>