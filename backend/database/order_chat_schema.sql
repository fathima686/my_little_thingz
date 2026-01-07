-- Product/Item Chat System Database Schema
-- This creates the message storage structure for product-specific customization conversations

CREATE TABLE IF NOT EXISTS product_chat_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    cart_item_id INT UNSIGNED NULL, -- For specific cart items with customizations
    user_id INT UNSIGNED NOT NULL,
    sender_type ENUM('admin', 'user') NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    message_content TEXT NOT NULL,
    message_type ENUM('text', 'image', 'customization_request') DEFAULT 'text',
    customization_details JSON NULL, -- Store customization requirements
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for efficient querying
    INDEX idx_product_id (product_id),
    INDEX idx_cart_item_id (cart_item_id),
    INDEX idx_user_id (user_id),
    INDEX idx_sender (sender_type, sender_id),
    INDEX idx_created_at (created_at),
    INDEX idx_read_status (is_read),
    
    -- Composite index for product-specific message retrieval
    INDEX idx_product_messages (product_id, user_id, created_at),
    INDEX idx_cart_messages (cart_item_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create a view for easier message retrieval with sender information
CREATE OR REPLACE VIEW product_chat_view AS
SELECT 
    pcm.id,
    pcm.product_id,
    pcm.cart_item_id,
    pcm.user_id,
    pcm.sender_type,
    pcm.sender_id,
    pcm.message_content,
    pcm.message_type,
    pcm.customization_details,
    pcm.is_read,
    pcm.created_at,
    pcm.updated_at,
    CASE 
        WHEN pcm.sender_type = 'admin' THEN 'Admin'
        WHEN pcm.sender_type = 'user' THEN COALESCE(u.name, u.email, 'User')
        ELSE 'Unknown'
    END as sender_name,
    p.name as product_name,
    p.image as product_image
FROM product_chat_messages pcm
LEFT JOIN users u ON pcm.sender_type = 'user' AND pcm.sender_id = u.id
LEFT JOIN products p ON pcm.product_id = p.id
ORDER BY pcm.product_id, pcm.user_id, pcm.created_at ASC;