#!/usr/bin/env python3
"""
Debug the SQL query issue
"""

import requests
import json

def debug_sql_query():
    print("🔍 Debugging SQL query issue...")
    
    # Let's test with a simple search that we know works
    print("1. Testing simple search that works...")
    
    url = "http://localhost/my_little_thingz/backend/api/customer/enhanced-search.php"
    params = {'action': 'search', 'term': 'treats', 'limit': 5}
    
    try:
        response = requests.get(url, params=params, timeout=10)
        if response.status_code == 200:
            data = response.json()
            if data.get('status') == 'success':
                results = data.get('data', {})
                artworks = results.get('artworks', [])
                print(f"✅ 'treats' search found {len(artworks)} results")
                
                if artworks:
                    for artwork in artworks:
                        print(f"   • {artwork.get('title', 'N/A')} - ₹{artwork.get('price', 0)} ({artwork.get('category_name', 'N/A')})")
            else:
                print(f"❌ Error: {data.get('message')}")
        else:
            print(f"❌ HTTP {response.status_code}")
    except Exception as e:
        print(f"❌ Error: {e}")
    
    # Now let's test with 'sweet' and see what happens
    print("\n2. Testing 'sweet' search...")
    
    params = {'action': 'search', 'term': 'sweet', 'limit': 5}
    
    try:
        response = requests.get(url, params=params, timeout=10)
        if response.status_code == 200:
            data = response.json()
            if data.get('status') == 'success':
                results = data.get('data', {})
                artworks = results.get('artworks', [])
                print(f"❌ 'sweet' search found {len(artworks)} results")
                
                # Let's check what the comprehensive search terms are
                print(f"\n🔧 Debugging comprehensive search terms...")
                
                # Test the comprehensive search terms manually
                comprehensive_terms = [
                    'sweet', 'chocolate', 'candy', 'treat', 'dessert', 'sugar', 'cocoa', 'truffle', 'praline',
                    'ganache', 'fudge', 'brownie', 'cookie', 'biscuit', 'indulgence', 'caramel',
                    'toffee', 'nougat', 'mint', 'vanilla', 'strawberry', 'hazelnut', 'almond',
                    'walnut', 'peanut', 'm&m', 'kitkat', 'snickers', 'twix', 'mars', 'bounty',
                    'ferrero', 'lindt', 'cadbury', 'nestle', 'hershey', 'godiva', 'toblerone',
                    'sweet treat', 'confectionery', 'choc', 'candy bar', 'chocolate bar',
                    'custom chocolate', 'personalized chocolate', 'engraved chocolate'
                ]
                
                print(f"   Comprehensive terms for 'sweet': {len(comprehensive_terms)} terms")
                print(f"   First 10 terms: {comprehensive_terms[:10]}")
                
                # Test if any of these terms match our products
                print(f"\n🔍 Testing if any comprehensive terms match our products...")
                
                # Get all products
                all_url = "http://localhost/my_little_thingz/backend/api/customer/artworks.php"
                all_response = requests.get(all_url, timeout=10)
                
                if all_response.status_code == 200:
                    all_data = all_response.json()
                    if all_data.get('status') == 'success':
                        all_artworks = all_data.get('artworks', [])
                        
                        matches = []
                        for artwork in all_artworks:
                            title = artwork.get('title', '').lower()
                            description = artwork.get('description', '').lower()
                            
                            for term in comprehensive_terms[:10]:  # Test first 10 terms
                                if term in title or term in description:
                                    matches.append((artwork.get('title', 'N/A'), term))
                                    break
                        
                        print(f"   Found {len(matches)} matches with comprehensive terms:")
                        for title, term in matches:
                            print(f"   • {title} (matched: {term})")
                
            else:
                print(f"❌ Error: {data.get('message')}")
        else:
            print(f"❌ HTTP {response.status_code}")
    except Exception as e:
        print(f"❌ Error: {e}")
    
    # Let's also test the regular artworks API to see if it can find sweet products
    print("\n3. Testing regular artworks API...")
    
    try:
        # This should find products with 'sweet' in title/description
        all_url = "http://localhost/my_little_thingz/backend/api/customer/artworks.php"
        all_response = requests.get(all_url, timeout=10)
        
        if all_response.status_code == 200:
            all_data = all_response.json()
            if all_data.get('status') == 'success':
                all_artworks = all_data.get('artworks', [])
                
                sweet_products = []
                for artwork in all_artworks:
                    title = artwork.get('title', '').lower()
                    description = artwork.get('description', '').lower()
                    if 'sweet' in title or 'sweet' in description:
                        sweet_products.append(artwork)
                
                print(f"   Regular API found {len(sweet_products)} products with 'sweet':")
                for product in sweet_products:
                    print(f"   • {product.get('title', 'N/A')} - ₹{product.get('price', 0)} ({product.get('category_name', 'N/A')})")
            else:
                print(f"❌ Regular API Error: {all_data.get('message')}")
        else:
            print(f"❌ Regular API HTTP {all_response.status_code}")
    except Exception as e:
        print(f"❌ Regular API Error: {e}")

if __name__ == "__main__":
    debug_sql_query()



