#!/usr/bin/env python3
"""
Add sample sweet products to database for testing
"""

import requests
import json

def add_sample_products():
    print("🍫 Adding sample sweet products for testing...")
    
    # Sample products with sweet/chocolate terms
    sample_products = [
        {
            "title": "Sweet Chocolate Box",
            "description": "Delicious sweet chocolate assortment with various flavors",
            "price": 299.99,
            "category_id": 3,  # Assuming custom chocolate category
            "image_url": "/images/sweet-chocolate.jpg"
        },
        {
            "title": "Premium Sweet Treats",
            "description": "Premium collection of sweet treats and chocolates",
            "price": 499.99,
            "category_id": 1,  # Assuming Gift box category
            "image_url": "/images/sweet-treats.jpg"
        },
        {
            "title": "Sweet Nuts Hamper",
            "description": "Sweet and savory nuts hamper with chocolate covered almonds",
            "price": 399.99,
            "category_id": 1,  # Gift box category
            "image_url": "/images/sweet-nuts.jpg"
        },
        {
            "title": "Custom Sweet Chocolate",
            "description": "Personalized sweet chocolate with custom message",
            "price": 199.99,
            "category_id": 3,  # Custom chocolate category
            "image_url": "/images/custom-sweet-chocolate.jpg"
        }
    ]
    
    # Note: This would require a POST endpoint to add products
    # For now, let's just show what we would add
    print("📦 Sample products to add:")
    for i, product in enumerate(sample_products, 1):
        print(f"   {i}. {product['title']} - ₹{product['price']}")
        print(f"      Description: {product['description']}")
        print(f"      Category ID: {product['category_id']}")
        print()
    
    print("💡 These products would make 'sweet' search work!")
    print("   They contain keywords like: sweet, chocolate, treats, nuts")
    
    # Let's also check what categories exist
    print("\n🔍 Checking existing categories...")
    
    try:
        # Try to get categories (this might not exist as an endpoint)
        url = "http://localhost/my_little_thingz/backend/api/customer/categories.php"
        response = requests.get(url, timeout=10)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('status') == 'success':
                categories = data.get('categories', [])
                print(f"✅ Found {len(categories)} categories:")
                for category in categories:
                    print(f"   • {category.get('name', 'N/A')} (ID: {category.get('id', 'N/A')})")
            else:
                print(f"❌ Categories API Error: {data.get('message')}")
        else:
            print(f"❌ Categories HTTP {response.status_code}")
    except Exception as e:
        print(f"❌ Categories Error: {e}")
    
    print("\n🎯 To fix the search:")
    print("   1. Add products with 'sweet', 'chocolate', 'treats' in titles/descriptions")
    print("   2. Ensure products are in correct categories")
    print("   3. Test the enhanced search again")

if __name__ == "__main__":
    add_sample_products()



