-- Artwork Gallery schema for admin-managed artworks
-- Run this after creating the base schema

START TRANSACTION;

-- Categories table (if missing)
CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_category_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed a few categories if empty
INSERT INTO categories (name, description, status)
SELECT * FROM (
  SELECT 'Polaroids', 'Polaroid prints and packs', 'active' UNION ALL
  SELECT 'Chocolates', 'Custom chocolates and packs', 'active' UNION ALL
  SELECT 'Frames', 'Photo frames of various sizes', 'active' UNION ALL
  SELECT 'Albums', 'Handmade albums', 'active' UNION ALL
  SELECT 'Wedding Cards', 'Invitations and cards', 'active' UNION ALL
  SELECT 'Birthday Theme Boxes', 'Curated theme boxes', 'active' UNION ALL
  SELECT 'Wedding Hampers', 'Curated wedding hampers', 'active' UNION ALL
  SELECT 'Gift Boxes', 'Single and set gift boxes', 'active' UNION ALL
  SELECT 'Bouquets', 'Gift bouquets', 'active'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM categories);

-- Artworks table
CREATE TABLE IF NOT EXISTS artworks (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(180) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  image_url VARCHAR(255) NOT NULL,
  category_id INT UNSIGNED NULL,
  artist_id INT UNSIGNED NULL,
  availability ENUM('in_stock','out_of_stock','made_to_order') NOT NULL DEFAULT 'in_stock',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_artworks_category (category_id),
  KEY idx_artworks_status (status),
  CONSTRAINT fk_artworks_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_artworks_artist FOREIGN KEY (artist_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wishlist table (if missing)
CREATE TABLE IF NOT EXISTS wishlist (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  artwork_id INT UNSIGNED NOT NULL,
  added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_artwork (user_id, artwork_id),
  KEY idx_wishlist_user (user_id),
  KEY idx_wishlist_artwork (artwork_id),
  CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_wishlist_artwork FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;