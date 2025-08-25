-- Seed categories and products for gallery search with images and INR prices

-- Categories
INSERT INTO categories (name, description, status)
VALUES 
  ('Polaroids', 'Printed polaroid-style photos', 'active'),
  ('Chocolates', 'Custom chocolates and boxes', 'active'),
  ('Wedding Hampers', 'Curated hamper gifts for weddings', 'active'),
  ('Gift Boxes', 'Gift boxes and combos', 'active'),
  ('Bouquets', 'Gift bouquets and arrangements', 'active'),
  ('Albums', 'Photo albums and scrapbooks', 'active')
ON DUPLICATE KEY UPDATE description=VALUES(description), status='active';

-- Products (artworks)
-- Note: adjust artist_id to a valid user id that represents the seller (using 1)
-- Prices in INR
INSERT INTO artworks (title, description, price, image_url, category_id, artist_id, status)
VALUES
  ('Polaroids (Single Page)', 'Polaroid print – per page', 5.00, '/images/poloroid.png', (SELECT id FROM categories WHERE name='Polaroids'), 1, 'active'),
  ('Polaroids Pack', 'Pack of polaroid prints', 100.00, '/images/poloroid (2).png', (SELECT id FROM categories WHERE name='Polaroids'), 1, 'active'),
  ('Custom Chocolate', 'Personalized chocolate with name', 30.00, '/images/custom chocolate.png', (SELECT id FROM categories WHERE name='Chocolates'), 1, 'active'),
  ('Wedding Hamper', 'Curated wedding gift hamper', 500.00, '/images/Wedding hamper.jpg', (SELECT id FROM categories WHERE name='Wedding Hampers'), 1, 'active'),
  ('Gift Box', 'Single gift box', 150.00, '/images/gift box.png', (SELECT id FROM categories WHERE name='Gift Boxes'), 1, 'active'),
  ('Gift Box Set', 'Premium gift box set', 300.00, '/images/gift box set.png', (SELECT id FROM categories WHERE name='Gift Boxes'), 1, 'active'),
  ('Bouquets', 'Gift bouquet arrangement', 200.00, '/images/boaqutes.png', (SELECT id FROM categories WHERE name='Bouquets'), 1, 'active'),
  ('Photo Album', 'Handmade photo album', 400.00, '/images/album.png', (SELECT id FROM categories WHERE name='Albums'), 1, 'active'),
  ('Drawings', 'Custom portrait drawing', 250.00, '/images/drawings.png', (SELECT id FROM categories WHERE name='Home Decor'), 1, 'active')
ON DUPLICATE KEY UPDATE price=VALUES(price), image_url=VALUES(image_url), status='active';