#!/usr/bin/env python3
"""
Test with actual product titles from database
"""

import requests
import json

def test_with_actual_products():
    print("🔍 Testing with actual product titles...")
    
    # Get actual products
    url = "http://localhost/my_little_thingz/backend/api/customer/artworks.php"
    response = requests.get(url, timeout=10)
    
    if response.status_code == 200:
        data = response.json()
        if data.get('status') == 'success':
            artworks = data.get('artworks', [])
            print(f"✅ Found {len(artworks)} products")
            
            # Test with actual product titles
            test_terms = []
            for artwork in artworks[:10]:
                title = artwork.get('title', '')
                if title:
                    # Extract first word as search term
                    first_word = title.lower().split()[0]
                    test_terms.append(first_word)
            
            print(f"🔍 Testing with terms: {', '.join(set(test_terms))}")
            
            for term in list(set(test_terms))[:5]:  # Test first 5 unique terms
                print(f"\n🔍 Testing search for: '{term}'")
                
                search_url = "http://localhost/my_little_thingz/backend/api/customer/enhanced-search.php"
                params = {'action': 'search', 'term': term, 'limit': 10}
                
                try:
                    response = requests.get(search_url, params=params, timeout=5)
                    if response.status_code == 200:
                        data = response.json()
                        if data.get('status') == 'success':
                            results = data.get('data', {})
                            found = results.get('total_found', 0)
                            print(f"   ✅ Found {found} results")
                            
                            if found > 0:
                                artworks_found = results.get('artworks', [])
                                print(f"   📦 Results:")
                                for artwork in artworks_found[:3]:
                                    print(f"      • {artwork.get('title', 'N/A')} - ₹{artwork.get('price', 0)} ({artwork.get('category_name', 'N/A')})")
                            else:
                                print(f"   ❌ No results found")
                        else:
                            print(f"   ❌ Error: {data.get('message')}")
                    else:
                        print(f"   ❌ HTTP {response.status_code}")
                except Exception as e:
                    print(f"   ❌ Error: {e}")
    
    # Test with specific known terms
    print(f"\n🔍 Testing with specific terms...")
    specific_terms = ['nuts', 'wedding', 'card', 'gift', 'birthday']
    
    for term in specific_terms:
        print(f"\n🔍 Testing search for: '{term}'")
        
        search_url = "http://localhost/my_little_thingz/backend/api/customer/enhanced-search.php"
        params = {'action': 'search', 'term': term, 'limit': 10}
        
        try:
            response = requests.get(search_url, params=params, timeout=5)
            if response.status_code == 200:
                data = response.json()
                if data.get('status') == 'success':
                    results = data.get('data', {})
                    found = results.get('total_found', 0)
                    print(f"   ✅ Found {found} results")
                    
                    if found > 0:
                        artworks_found = results.get('artworks', [])
                        print(f"   📦 Results:")
                        for artwork in artworks_found[:3]:
                            print(f"      • {artwork.get('title', 'N/A')} - ₹{artwork.get('price', 0)} ({artwork.get('category_name', 'N/A')})")
                    else:
                        print(f"   ❌ No results found")
                else:
                    print(f"   ❌ Error: {data.get('message')}")
            else:
                print(f"   ❌ HTTP {response.status_code}")
        except Exception as e:
            print(f"   ❌ Error: {e}")

if __name__ == "__main__":
    test_with_actual_products()
