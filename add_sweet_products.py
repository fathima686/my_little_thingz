#!/usr/bin/env python3
"""
Add sample sweet products to make search work
"""

import requests
import json

def add_sweet_products():
    print("🍫 Adding sample sweet products to make search work...")
    
    # First, let's check what products exist
    print("🔍 Checking current products...")
    
    url = "http://localhost/my_little_thingz/backend/api/customer/artworks.php"
    response = requests.get(url, timeout=10)
    
    if response.status_code == 200:
        data = response.json()
        if data.get('status') == 'success':
            artworks = data.get('artworks', [])
            print(f"✅ Found {len(artworks)} existing products")
            
            # Show some existing products
            print("\n📦 Current products:")
            for i, artwork in enumerate(artworks[:5]):
                print(f"   {i+1}. {artwork.get('title', 'N/A')} - ₹{artwork.get('price', 0)} ({artwork.get('category_name', 'N/A')})")
    
    # Now let's create a simple SQL script to add sweet products
    print("\n🍫 Creating SQL script to add sweet products...")
    
    sql_script = """
-- Add sample sweet products to make search work
INSERT INTO artworks (title, description, price, category_id, image_url, status, availability) VALUES
('Sweet Chocolate Box', 'Delicious sweet chocolate assortment with various flavors including milk chocolate, dark chocolate, and white chocolate truffles', 299.99, 5, '/images/sweet-chocolate-box.jpg', 'active', 'available'),
('Premium Sweet Treats', 'Premium collection of sweet treats and chocolates perfect for gifting', 499.99, 1, '/images/premium-sweet-treats.jpg', 'active', 'available'),
('Sweet Nuts Hamper', 'Sweet and savory nuts hamper with chocolate covered almonds, cashews, and walnuts', 399.99, 1, '/images/sweet-nuts-hamper.jpg', 'active', 'available'),
('Custom Sweet Chocolate', 'Personalized sweet chocolate with custom message and your choice of flavors', 199.99, 5, '/images/custom-sweet-chocolate.jpg', 'active', 'available'),
('Sweet Gift Basket', 'Beautiful gift basket filled with sweet treats, chocolates, and confectionery', 599.99, 1, '/images/sweet-gift-basket.jpg', 'active', 'available'),
('Chocolate Sweet Hearts', 'Sweet chocolate hearts perfect for romantic occasions and valentines', 149.99, 5, '/images/chocolate-sweet-hearts.jpg', 'active', 'available'),
('Sweet Dessert Box', 'Gourmet sweet dessert box with mini cakes, cookies, and sweet treats', 349.99, 1, '/images/sweet-dessert-box.jpg', 'active', 'available'),
('Personalized Sweet Chocolate Bar', 'Custom sweet chocolate bar with your name and message engraved', 89.99, 5, '/images/personalized-sweet-chocolate.jpg', 'active', 'available');
"""
    
    # Write SQL script to file
    with open('add_sweet_products.sql', 'w') as f:
        f.write(sql_script)
    
    print("✅ Created add_sweet_products.sql file")
    print("\n📋 SQL Script created with these products:")
    print("   1. Sweet Chocolate Box - ₹299.99 (custom chocolate)")
    print("   2. Premium Sweet Treats - ₹499.99 (Gift box)")
    print("   3. Sweet Nuts Hamper - ₹399.99 (Gift box)")
    print("   4. Custom Sweet Chocolate - ₹199.99 (custom chocolate)")
    print("   5. Sweet Gift Basket - ₹599.99 (Gift box)")
    print("   6. Chocolate Sweet Hearts - ₹149.99 (custom chocolate)")
    print("   7. Sweet Dessert Box - ₹349.99 (Gift box)")
    print("   8. Personalized Sweet Chocolate Bar - ₹89.99 (custom chocolate)")
    
    print("\n🚀 To add these products:")
    print("   1. Open phpMyAdmin or MySQL command line")
    print("   2. Select your 'my_little_thingz' database")
    print("   3. Run the SQL script from add_sweet_products.sql")
    print("   4. Test the search again!")
    
    print("\n🎯 After adding these products, searching for 'sweet' should show:")
    print("   • Sweet Chocolate Box")
    print("   • Premium Sweet Treats") 
    print("   • Sweet Nuts Hamper")
    print("   • Custom Sweet Chocolate")
    print("   • Sweet Gift Basket")
    print("   • Chocolate Sweet Hearts")
    print("   • Sweet Dessert Box")
    print("   • Personalized Sweet Chocolate Bar")
    
    # Also create a simple test script
    test_script = """
#!/usr/bin/env python3
# Test script to verify sweet search works after adding products

import requests

def test_sweet_search():
    print("🍫 Testing sweet search after adding products...")
    
    url = "http://localhost/my_little_thingz/backend/api/customer/enhanced-search.php"
    params = {'action': 'search', 'term': 'sweet', 'limit': 20}
    
    try:
        response = requests.get(url, params=params, timeout=10)
        if response.status_code == 200:
            data = response.json()
            if data.get('status') == 'success':
                results = data.get('data', {})
                artworks = results.get('artworks', [])
                category_groups = results.get('category_groups', {})
                
                print(f"✅ Found {len(artworks)} sweet products!")
                
                if artworks:
                    print("\\n📦 Sweet products found:")
                    for artwork in artworks:
                        print(f"   • {artwork.get('title', 'N/A')} - ₹{artwork.get('price', 0)} ({artwork.get('category_name', 'N/A')})")
                
                if category_groups:
                    print("\\n📂 Categories with sweet products:")
                    for category, products in category_groups.items():
                        print(f"   • {category}: {len(products)} products")
            else:
                print(f"❌ Error: {data.get('message')}")
        else:
            print(f"❌ HTTP {response.status_code}")
    except Exception as e:
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    test_sweet_search()
"""
    
    with open('test_sweet_search_after.py', 'w') as f:
        f.write(test_script)
    
    print("\n✅ Created test_sweet_search_after.py to test after adding products")

if __name__ == "__main__":
    add_sweet_products()



