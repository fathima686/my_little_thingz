-- Customer Dashboard Database Tables
-- Run this script to create the necessary tables for customer functionality

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample categories
INSERT INTO categories (name, description) VALUES
('Keychains', 'Custom keychains and accessories'),
('Photo Frames', 'Personalized photo frames'),
('Mugs', 'Custom printed mugs and drinkware'),
('Jewelry', 'Handcrafted jewelry pieces'),
('Home Decor', 'Decorative items for home'),
('Gifts', 'Special occasion gifts')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Artworks table
CREATE TABLE IF NOT EXISTS artworks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(500),
    category_id INT,
    artist_id INT,
    availability ENUM('available', 'out_of_stock') DEFAULT 'available',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (artist_id) REFERENCES users(id)
);

-- Sample artworks
INSERT INTO artworks (title, description, price, category_id, artist_id, image_url) VALUES
('Custom Name Keychain', 'Personalized acrylic keychain with your name', 15.99, 1, 1, '/api/placeholder/300/300'),
('Wooden Photo Frame', 'Handcrafted wooden frame for your memories', 29.99, 2, 1, '/api/placeholder/300/300'),
('Ceramic Coffee Mug', 'Custom printed ceramic mug', 19.99, 3, 1, '/api/placeholder/300/300'),
('Silver Pendant Necklace', 'Elegant silver pendant with custom engraving', 49.99, 4, 1, '/api/placeholder/300/300'),
('Decorative Wall Art', 'Beautiful wall art for your home', 39.99, 5, 1, '/api/placeholder/300/300'),
('Anniversary Gift Set', 'Special gift set for anniversaries', 79.99, 6, 1, '/api/placeholder/300/300')
ON DUPLICATE KEY UPDATE title=VALUES(title);

-- Wishlist table
CREATE TABLE IF NOT EXISTS wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    artwork_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (user_id, artwork_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE
);

-- Custom requests table
CREATE TABLE IF NOT EXISTS custom_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category_id INT,
    budget_min DECIMAL(10,2),
    budget_max DECIMAL(10,2),
    deadline DATE,
    special_instructions TEXT,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Custom request images table
CREATE TABLE IF NOT EXISTS custom_request_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES custom_requests(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2),
    tax_amount DECIMAL(10,2),
    shipping_cost DECIMAL(10,2),
    shipping_address TEXT,
    tracking_number VARCHAR(100),
    estimated_delivery DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    artwork_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (artwork_id) REFERENCES artworks(id)
);

-- Cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    artwork_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart (user_id, artwork_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE
);

-- Sample orders for testing
INSERT INTO orders (user_id, order_number, status, total_amount, subtotal, shipping_address) VALUES
(1, 'ORD-2024-001', 'delivered', 45.98, 39.98, '123 Main St, City, State 12345'),
(1, 'ORD-2024-002', 'shipped', 29.99, 29.99, '123 Main St, City, State 12345'),
(1, 'ORD-2024-003', 'processing', 79.99, 79.99, '123 Main St, City, State 12345')
ON DUPLICATE KEY UPDATE order_number=VALUES(order_number);

-- Sample order items
INSERT INTO order_items (order_id, artwork_id, quantity, price) VALUES
(1, 1, 2, 15.99),
(1, 3, 1, 19.99),
(2, 2, 1, 29.99),
(3, 6, 1, 79.99)
ON DUPLICATE KEY UPDATE order_id=VALUES(order_id);

-- Create indexes for better performance
CREATE INDEX idx_artworks_category ON artworks(category_id);
CREATE INDEX idx_artworks_status ON artworks(status);
CREATE INDEX idx_wishlist_user ON wishlist(user_id);
CREATE INDEX idx_custom_requests_user ON custom_requests(user_id);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_cart_user ON cart(user_id);

SELECT 'Customer dashboard tables created successfully!' as message;