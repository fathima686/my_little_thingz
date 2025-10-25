#!/usr/bin/env python3
"""
Test sweet search after adding products
"""

import requests
import json

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
                related_categories = results.get('related_categories', [])
                
                print(f"✅ Found {len(artworks)} sweet products!")
                print(f"📂 Related categories: {', '.join(related_categories)}")
                
                if artworks:
                    print("\n📦 Sweet products found:")
                    for artwork in artworks:
                        print(f"   • {artwork.get('title', 'N/A')} - ₹{artwork.get('price', 0)} ({artwork.get('category_name', 'N/A')})")
                
                if category_groups:
                    print("\n📂 Categories with sweet products:")
                    for category, products in category_groups.items():
                        print(f"   • {category}: {len(products)} products")
                        for product in products[:2]:  # Show first 2 products per category
                            print(f"     - {product.get('title', 'N/A')} - ₹{product.get('price', 0)}")
                
                # Test ML insights
                ml_insights = results.get('ml_insights', {})
                if ml_insights:
                    print(f"\n🤖 ML Insights:")
                    print(f"   Predicted Category: {ml_insights.get('predicted_category', 'N/A')}")
                    print(f"   Confidence: {ml_insights.get('confidence_percent', 0)}%")
                    print(f"   Algorithm: {ml_insights.get('algorithm', 'N/A')}")
                
            else:
                print(f"❌ Error: {data.get('message')}")
        else:
            print(f"❌ HTTP {response.status_code}")
    except Exception as e:
        print(f"❌ Error: {e}")
    
    print("\n🎯 Now test these other searches:")
    test_terms = ['chocolate', 'gift', 'treats', 'dessert']
    
    for term in test_terms:
        print(f"\n🔍 Testing '{term}' search...")
        params = {'action': 'search', 'term': term, 'limit': 10}
        
        try:
            response = requests.get(url, params=params, timeout=5)
            if response.status_code == 200:
                data = response.json()
                if data.get('status') == 'success':
                    results = data.get('data', {})
                    found = len(results.get('artworks', []))
                    print(f"   ✅ Found {found} results")
                else:
                    print(f"   ❌ Error: {data.get('message')}")
            else:
                print(f"   ❌ HTTP {response.status_code}")
        except Exception as e:
            print(f"   ❌ Error: {e}")

if __name__ == "__main__":
    test_sweet_search()



