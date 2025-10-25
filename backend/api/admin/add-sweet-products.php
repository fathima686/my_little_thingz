<?php
// Add sweet products to database
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
    $mysqli->set_charset('utf8mb4');
    
    if ($mysqli->connect_error) {
        throw new Exception("Database connection failed: " . $mysqli->connect_error);
    }
    
    // Sample sweet products
    $sweet_products = [
        [
            'title' => 'Sweet Chocolate Box',
            'description' => 'Delicious sweet chocolate assortment with various flavors including milk chocolate, dark chocolate, and white chocolate truffles',
            'price' => 299.99,
            'category_id' => 5, // custom chocolate
            'image_url' => '/images/sweet-chocolate-box.jpg'
        ],
        [
            'title' => 'Premium Sweet Treats',
            'description' => 'Premium collection of sweet treats and chocolates perfect for gifting',
            'price' => 499.99,
            'category_id' => 1, // Gift box
            'image_url' => '/images/premium-sweet-treats.jpg'
        ],
        [
            'title' => 'Sweet Nuts Hamper',
            'description' => 'Sweet and savory nuts hamper with chocolate covered almonds, cashews, and walnuts',
            'price' => 399.99,
            'category_id' => 1, // Gift box
            'image_url' => '/images/sweet-nuts-hamper.jpg'
        ],
        [
            'title' => 'Custom Sweet Chocolate',
            'description' => 'Personalized sweet chocolate with custom message and your choice of flavors',
            'price' => 199.99,
            'category_id' => 5, // custom chocolate
            'image_url' => '/images/custom-sweet-chocolate.jpg'
        ],
        [
            'title' => 'Sweet Gift Basket',
            'description' => 'Beautiful gift basket filled with sweet treats, chocolates, and confectionery',
            'price' => 599.99,
            'category_id' => 1, // Gift box
            'image_url' => '/images/sweet-gift-basket.jpg'
        ],
        [
            'title' => 'Chocolate Sweet Hearts',
            'description' => 'Sweet chocolate hearts perfect for romantic occasions and valentines',
            'price' => 149.99,
            'category_id' => 5, // custom chocolate
            'image_url' => '/images/chocolate-sweet-hearts.jpg'
        ],
        [
            'title' => 'Sweet Dessert Box',
            'description' => 'Gourmet sweet dessert box with mini cakes, cookies, and sweet treats',
            'price' => 349.99,
            'category_id' => 1, // Gift box
            'image_url' => '/images/sweet-dessert-box.jpg'
        ],
        [
            'title' => 'Personalized Sweet Chocolate Bar',
            'description' => 'Custom sweet chocolate bar with your name and message engraved',
            'price' => 89.99,
            'category_id' => 5, // custom chocolate
            'image_url' => '/images/personalized-sweet-chocolate.jpg'
        ]
    ];
    
    $added_count = 0;
    $errors = [];
    
    foreach ($sweet_products as $product) {
        // Check if product already exists
        $check_sql = "SELECT id FROM artworks WHERE title = ?";
        $check_stmt = $mysqli->prepare($check_sql);
        $check_stmt->bind_param("s", $product['title']);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Insert new product
            $insert_sql = "INSERT INTO artworks (title, description, price, category_id, image_url, status, availability, created_at) VALUES (?, ?, ?, ?, ?, 'active', 'available', NOW())";
            $insert_stmt = $mysqli->prepare($insert_sql);
            $insert_stmt->bind_param("ssdis", 
                $product['title'], 
                $product['description'], 
                $product['price'], 
                $product['category_id'], 
                $product['image_url']
            );
            
            if ($insert_stmt->execute()) {
                $added_count++;
            } else {
                $errors[] = "Failed to add {$product['title']}: " . $insert_stmt->error;
            }
            $insert_stmt->close();
        } else {
            $errors[] = "Product '{$product['title']}' already exists";
        }
        $check_stmt->close();
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => "Added {$added_count} sweet products successfully",
        'added_count' => $added_count,
        'errors' => $errors,
        'products_added' => $added_count > 0
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>



