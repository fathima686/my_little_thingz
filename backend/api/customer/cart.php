<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user ID from headers
    $user_id = $_SERVER['HTTP_X_USER_ID'] ?? null;
    
    if (!$user_id) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User ID required'
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch user's cart items
        $query = "SELECT 
                    c.id,
                    c.artwork_id,
                    c.quantity,
                    c.added_at,
                    a.title,
                    a.price,
                    a.image_url,
                    a.availability,
                    u.name as artist_name
                  FROM cart c
                  JOIN artworks a ON c.artwork_id = a.id
                  LEFT JOIN users u ON a.artist_id = u.id
                  WHERE c.user_id = ? AND a.status = 'active'
                  ORDER BY c.added_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = 0;
        foreach ($cart_items as &$item) {
            $item_total = $item['price'] * $item['quantity'];
            $item['item_total'] = number_format($item_total, 2);
            $item['price'] = number_format($item['price'], 2);
            $total += $item_total;
        }

        echo json_encode([
            'status' => 'success',
            'cart_items' => $cart_items,
            'total' => number_format($total, 2)
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Add item to cart
        $input = json_decode(file_get_contents('php://input'), true);
        $artwork_id = $input['artwork_id'] ?? null;
        $quantity = $input['quantity'] ?? 1;

        if (!$artwork_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Artwork ID required'
            ]);
            exit;
        }

        // Check if artwork exists and is available
        $artwork_query = "SELECT id, availability FROM artworks WHERE id = ? AND status = 'active'";
        $artwork_stmt = $db->prepare($artwork_query);
        $artwork_stmt->execute([$artwork_id]);
        $artwork = $artwork_stmt->fetch();

        if (!$artwork) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Artwork not found'
            ]);
            exit;
        }

        if ($artwork['availability'] !== 'available') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Artwork is not available'
            ]);
            exit;
        }

        // Check if item already exists in cart
        $check_query = "SELECT id, quantity FROM cart WHERE user_id = ? AND artwork_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$user_id, $artwork_id]);
        $existing_item = $check_stmt->fetch();

        if ($existing_item) {
            // Update quantity
            $new_quantity = $existing_item['quantity'] + $quantity;
            $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $result = $update_stmt->execute([$new_quantity, $existing_item['id']]);
        } else {
            // Add new item
            $insert_query = "INSERT INTO cart (user_id, artwork_id, quantity, added_at) VALUES (?, ?, ?, NOW())";
            $insert_stmt = $db->prepare($insert_query);
            $result = $insert_stmt->execute([$user_id, $artwork_id, $quantity]);
        }

        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Item added to cart'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to add item to cart'
            ]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Update cart item quantity
        $input = json_decode(file_get_contents('php://input'), true);
        $cart_id = $input['cart_id'] ?? null;
        $quantity = $input['quantity'] ?? null;

        if (!$cart_id || !$quantity) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Cart ID and quantity required'
            ]);
            exit;
        }

        $update_query = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if ($update_stmt->execute([$quantity, $cart_id, $user_id])) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Cart updated'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update cart'
            ]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Remove item from cart
        $input = json_decode(file_get_contents('php://input'), true);
        $cart_id = $input['cart_id'] ?? null;

        if (!$cart_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Cart ID required'
            ]);
            exit;
        }

        $delete_query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        $delete_stmt = $db->prepare($delete_query);
        
        if ($delete_stmt->execute([$cart_id, $user_id])) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Item removed from cart'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to remove item from cart'
            ]);
        }

    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Method not allowed'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>