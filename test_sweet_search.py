#!/usr/bin/env python3
"""
Test sweet search specifically
"""

import requests
import json

def test_sweet_search():
    print("🔍 Testing 'sweet' search specifically...")
    
    # Test enhanced search for 'sweet'
    url = "http://localhost/my_little_thingz/backend/api/customer/enhanced-search.php"
    params = {'action': 'search', 'term': 'sweet', 'limit': 20}
    
    try:
        response = requests.get(url, params=params, timeout=10)
        print(f"Status Code: {response.status_code}")
        
        if response.status_code == 200:
            data = response.json()
            print(f"Response: {json.dumps(data, indent=2)}")
            
            if data.get('status') == 'success':
                results = data.get('data', {})
                artworks = results.get('artworks', [])
                category_groups = results.get('category_groups', {})
                related_categories = results.get('related_categories', [])
                
                print(f"\n📊 Results Summary:")
                print(f"   Total found: {len(artworks)}")
                print(f"   Related categories: {related_categories}")
                print(f"   Category groups: {list(category_groups.keys())}")
                
                if artworks:
                    print(f"\n📦 Found products:")
                    for artwork in artworks:
                        print(f"   • {artwork.get('title', 'N/A')} - ₹{artwork.get('price', 0)} ({artwork.get('category_name', 'N/A')})")
                else:
                    print(f"\n❌ No products found")
                    
                    # Let's check what products exist that might match 'sweet'
                    print(f"\n🔍 Checking all products for 'sweet' related terms...")
                    
                    all_url = "http://localhost/my_little_thingz/backend/api/customer/artworks.php"
                    all_response = requests.get(all_url, timeout=10)
                    
                    if all_response.status_code == 200:
                        all_data = all_response.json()
                        if all_data.get('status') == 'success':
                            all_artworks = all_data.get('artworks', [])
                            
                            sweet_related = []
                            for artwork in all_artworks:
                                title = artwork.get('title', '').lower()
                                description = artwork.get('description', '').lower()
                                category = artwork.get('category_name', '').lower()
                                
                                # Check for sweet-related terms
                                sweet_terms = ['sweet', 'chocolate', 'candy', 'treat', 'dessert', 'sugar', 'cocoa', 'nuts']
                                
                                for term in sweet_terms:
                                    if term in title or term in description or term in category:
                                        sweet_related.append(artwork)
                                        break
                            
                            print(f"   Found {len(sweet_related)} potentially sweet-related products:")
                            for artwork in sweet_related:
                                print(f"   • {artwork.get('title', 'N/A')} - ₹{artwork.get('price', 0)} ({artwork.get('category_name', 'N/A')})")
                                
                                # Show description to see what terms are there
                                desc = artwork.get('description', '')
                                if desc:
                                    print(f"     Description: {desc[:100]}...")
            else:
                print(f"❌ API Error: {data.get('message')}")
        else:
            print(f"❌ HTTP {response.status_code}")
            
    except Exception as e:
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    test_sweet_search()



