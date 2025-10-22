-- Premium Gifts Classification Migration
-- This script safely adds category_tier column to artworks table
-- Run this script to enable Premium/Budget classification

START TRANSACTION;

-- Add category_tier column to artworks table
-- This is safe and won't affect existing data
ALTER TABLE artworks 
ADD COLUMN category_tier ENUM('Budget', 'Premium') DEFAULT 'Budget' 
AFTER status;

-- Create index for better performance on filtering
CREATE INDEX idx_artworks_category_tier ON artworks(category_tier);

-- Update existing items based on price thresholds
-- Items above ₹1000 are classified as Premium
UPDATE artworks 
SET category_tier = 'Premium' 
WHERE price >= 1000.00 
AND category_tier = 'Budget';

-- Items above ₹2000 are definitely Premium (double-check)
UPDATE artworks 
SET category_tier = 'Premium' 
WHERE price >= 2000.00;

-- Items with specific luxury keywords in title/description
UPDATE artworks 
SET category_tier = 'Premium' 
WHERE (
    LOWER(title) LIKE '%luxury%' OR 
    LOWER(title) LIKE '%premium%' OR 
    LOWER(title) LIKE '%designer%' OR 
    LOWER(title) LIKE '%exclusive%' OR
    LOWER(title) LIKE '%hamper%' OR
    LOWER(title) LIKE '%portrait%' OR
    LOWER(description) LIKE '%luxury%' OR 
    LOWER(description) LIKE '%premium%' OR 
    LOWER(description) LIKE '%designer%' OR 
    LOWER(description) LIKE '%exclusive%'
) AND category_tier = 'Budget';

-- Verify the changes
SELECT 
    category_tier,
    COUNT(*) as count,
    MIN(price) as min_price,
    MAX(price) as max_price,
    AVG(price) as avg_price
FROM artworks 
WHERE status = 'active'
GROUP BY category_tier;

COMMIT;

-- Optional: Create a view for easy premium filtering
CREATE OR REPLACE VIEW premium_artworks AS
SELECT * FROM artworks 
WHERE category_tier = 'Premium' 
AND status = 'active';

-- Optional: Create a view for budget filtering  
CREATE OR REPLACE VIEW budget_artworks AS
SELECT * FROM artworks 
WHERE category_tier = 'Budget' 
AND status = 'active';




