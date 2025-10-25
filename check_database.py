#!/usr/bin/env python3
"""
Check database and create sample products for testing
"""

import requests
import json

def check_database():
    print("🔍 Checking Database Products...")
    
    # Check what products exist
    url = "http://localhost/my_little_thingz/backend/api/customer/artworks.php"
    
    try:
        response = requests.get(url, timeout=10)
        if response.status_code == 200:
            data = response.json()
            if data.get('status') == 'success':
                artworks = data.get('artworks', [])
                print(f"✅ Found {len(artworks)} products in database")
                
                if artworks:
                    print("\n📦 Sample products:")
                    for i, artwork in enumerate(artworks[:5]):
                        print(f"   {i+1}. {artwork.get('title', 'N/A')} - ₹{artwork.get('price', 0)} ({artwork.get('category_name', 'N/A')})")
                    
                    # Check categories
                    categories = set()
                    for artwork in artworks:
                        if artwork.get('category_name'):
                            categories.add(artwork.get('category_name'))
                    
                    print(f"\n📂 Categories found: {', '.join(categories)}")
                    
                    # Test search with existing products
                    test_search_with_existing(artworks)
                else:
                    print("❌ No products found in database")
                    print("💡 Need to add sample products for testing")
            else:
                print(f"❌ API Error: {data.get('message')}")
        else:
            print(f"❌ HTTP {response.status_code}")
    except Exception as e:
        print(f"❌ Error: {e}")

def test_search_with_existing(artworks):
    print("\n🔍 Testing search with existing products...")
    
    # Get some sample titles to test with
    sample_titles = [artwork.get('title', '') for artwork in artworks[:3]]
    
    for title in sample_titles:
        if title:
            # Extract a keyword from the title
            words = title.lower().split()
            keyword = words[0] if words else 'test'
            
            print(f"\n🔍 Testing search for: '{keyword}'")
            
            url = "http://localhost/my_little_thingz/backend/api/customer/enhanced-search.php"
            params = {'action': 'search', 'term': keyword, 'limit': 10}
            
            try:
                response = requests.get(url, params=params, timeout=5)
                if response.status_code == 200:
                    data = response.json()
                    if data.get('status') == 'success':
                        results = data.get('data', {})
                        found = results.get('total_found', 0)
                        print(f"   ✅ Found {found} results")
                        
                        if found > 0:
                            artworks_found = results.get('artworks', [])
                            print(f"   📦 Sample results:")
                            for artwork in artworks_found[:2]:
                                print(f"      • {artwork.get('title', 'N/A')} - ₹{artwork.get('price', 0)}")
                    else:
                        print(f"   ❌ Error: {data.get('message')}")
                else:
                    print(f"   ❌ HTTP {response.status_code}")
            except Exception as e:
                print(f"   ❌ Error: {e}")

if __name__ == "__main__":
    check_database()



