#!/usr/bin/env python3
"""
Debug search issue - check if products exist and why search isn't finding them
"""

import requests
import json

def debug_search_issue():
    print("🔍 Debugging search issue...")
    
    # First, check if the sweet products were actually added
    print("1. Checking if sweet products exist in database...")
    
    url = "http://localhost/my_little_thingz/backend/api/customer/artworks.php"
    response = requests.get(url, timeout=10)
    
    if response.status_code == 200:
        data = response.json()
        if data.get('status') == 'success':
            artworks = data.get('artworks', [])
            print(f"✅ Total products in database: {len(artworks)}")
            
            # Look for sweet products
            sweet_products = []
            for artwork in artworks:
                title = artwork.get('title', '').lower()
                description = artwork.get('description', '').lower()
                if 'sweet' in title or 'sweet' in description:
                    sweet_products.append(artwork)
            
            print(f"🍫 Found {len(sweet_products)} products with 'sweet' in title/description:")
            for product in sweet_products:
                print(f"   • {product.get('title', 'N/A')} - ₹{product.get('price', 0)} ({product.get('category_name', 'N/A')})")
                print(f"     Description: {product.get('description', 'N/A')[:100]}...")
    
    # Now test the enhanced search API directly
    print("\n2. Testing enhanced search API...")
    
    search_url = "http://localhost/my_little_thingz/backend/api/customer/enhanced-search.php"
    params = {'action': 'search', 'term': 'sweet', 'limit': 20}
    
    try:
        response = requests.get(search_url, params=params, timeout=10)
        print(f"Status Code: {response.status_code}")
        
        if response.status_code == 200:
            data = response.json()
            print(f"Response: {json.dumps(data, indent=2)}")
            
            if data.get('status') == 'success':
                results = data.get('data', {})
                artworks = results.get('artworks', [])
                print(f"\n📊 Search Results:")
                print(f"   Total found: {len(artworks)}")
                print(f"   Search enhanced: {results.get('search_enhanced', False)}")
                print(f"   Related categories: {results.get('related_categories', [])}")
                
                if artworks:
                    print(f"\n📦 Found products:")
                    for artwork in artworks:
                        print(f"   • {artwork.get('title', 'N/A')} - ₹{artwork.get('price', 0)} ({artwork.get('category_name', 'N/A')})")
                else:
                    print(f"\n❌ No products found in search results")
                    
                    # Check what the search enhancement shows
                    enhancement = results.get('search_enhancement', {})
                    print(f"\n🔧 Search Enhancement:")
                    print(f"   Original search: {enhancement.get('original_search', 'N/A')}")
                    print(f"   ML prediction: {enhancement.get('ml_prediction', 'N/A')}")
                    print(f"   Confidence: {enhancement.get('confidence', 0)}%")
                    print(f"   Algorithm: {enhancement.get('algorithm_used', 'N/A')}")
            else:
                print(f"❌ API Error: {data.get('message')}")
        else:
            print(f"❌ HTTP {response.status_code}")
    except Exception as e:
        print(f"❌ Error: {e}")
    
    # Test with a simple search term that we know works
    print("\n3. Testing with known working search term...")
    
    params = {'action': 'search', 'term': 'treats', 'limit': 10}
    
    try:
        response = requests.get(search_url, params=params, timeout=5)
        if response.status_code == 200:
            data = response.json()
            if data.get('status') == 'success':
                results = data.get('data', {})
                artworks = results.get('artworks', [])
                print(f"✅ 'treats' search found {len(artworks)} results")
                
                if artworks:
                    for artwork in artworks[:3]:
                        print(f"   • {artwork.get('title', 'N/A')} - ₹{artwork.get('price', 0)} ({artwork.get('category_name', 'N/A')})")
            else:
                print(f"❌ Error: {data.get('message')}")
        else:
            print(f"❌ HTTP {response.status_code}")
    except Exception as e:
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    debug_search_issue()



