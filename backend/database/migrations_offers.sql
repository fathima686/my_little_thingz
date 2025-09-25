-- Migration: add product offers and combos support
-- Run once in MariaDB/MySQL for database `my_little_thingz`

ALTER TABLE artworks
  ADD COLUMN IF NOT EXISTS is_combo TINYINT(1) NOT NULL DEFAULT 0 AFTER image_url,
  ADD COLUMN IF NOT EXISTS offer_price DECIMAL(10,2) NULL AFTER price,
  ADD COLUMN IF NOT EXISTS offer_percent DECIMAL(5,2) NULL AFTER offer_price,
  ADD COLUMN IF NOT EXISTS offer_starts_at DATETIME NULL AFTER offer_percent,
  ADD COLUMN IF NOT EXISTS offer_ends_at DATETIME NULL AFTER offer_starts_at;

CREATE TABLE IF NOT EXISTS combo_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  combo_id INT UNSIGNED NOT NULL,
  artwork_id INT UNSIGNED NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  CONSTRAINT fk_combo_items_combo
    FOREIGN KEY (combo_id) REFERENCES artworks(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_combo_items_artwork
    FOREIGN KEY (artwork_id) REFERENCES artworks(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_combo_items_combo ON combo_items(combo_id);
CREATE INDEX IF NOT EXISTS idx_combo_items_artwork ON combo_items(artwork_id);