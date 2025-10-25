#!/usr/bin/env python3
"""
Test Enhanced Search with Comprehensive Results
"""

import requests
import json

def test_comprehensive_search():
    """Test the enhanced search with comprehensive results"""
    
    print("🔍 Testing Comprehensive Enhanced Search")
    print("=" * 60)
    
    # Test cases that should show multiple categories
    test_cases = [
        {
            'keyword': 'sweet',
            'expected_categories': ['chocolate', 'custom chocolate', 'nuts'],
            'description': 'Should show chocolate, custom chocolate, and nuts'
        },
        {
            'keyword': 'gift',
            'expected_categories': ['gift box', 'bouquet', 'chocolate', 'custom chocolate'],
            'description': 'Should show various gift categories'
        },
        {
            'keyword': 'romantic',
            'expected_categories': ['bouquet', 'chocolate', 'custom chocolate'],
            'description': 'Should show romantic gift options'
        },
        {
            'keyword': 'premium',
            'expected_categories': ['gift box', 'chocolate', 'custom chocolate'],
            'description': 'Should show premium/luxury items'
        }
    ]
    
    for test in test_cases:
        keyword = test['keyword']
        expected = test['expected_categories']
        description = test['description']
        
        print(f"\n🔍 Testing: '{keyword}'")
        print(f"   Expected: {', '.join(expected)}")
        print(f"   Description: {description}")
        
        try:
            # Test enhanced search API
            url = "http://localhost/my_little_thingz/backend/api/customer/enhanced-search.php"
            params = {
                'action': 'search',
                'term': keyword,
                'limit': 20
            }
            
            response = requests.get(url, params=params, timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                if data.get('status') == 'success':
                    results = data.get('data', {})
                    artworks = results.get('artworks', [])
                    category_groups = results.get('category_groups', {})
                    related_categories = results.get('related_categories', [])
                    
                    print(f"   ✅ Found {len(artworks)} total results")
                    print(f"   📦 Categories found: {', '.join(related_categories)}")
                    
                    # Show category breakdown
                    if category_groups:
                        print("   📊 Category breakdown:")
                        for category, products in category_groups.items():
                            print(f"      • {category}: {len(products)} products")
                    
                    # Check if expected categories are found
                    found_expected = [cat for cat in expected if cat in related_categories]
                    if found_expected:
                        print(f"   ✅ Found expected categories: {', '.join(found_expected)}")
                    else:
                        print(f"   ⚠️  Expected categories not found: {', '.join(expected)}")
                    
                    # Show first few results
                    if artworks:
                        print("   🎯 Sample results:")
                        for i, artwork in enumerate(artworks[:3]):
                            print(f"      {i+1}. {artwork.get('title', 'N/A')} - ₹{artwork.get('price', 0)} ({artwork.get('category_name', 'N/A')})")
                    
                else:
                    print(f"   ❌ API Error: {data.get('message', 'Unknown error')}")
            else:
                print(f"   ❌ HTTP {response.status_code}")
                
        except requests.exceptions.RequestException as e:
            print(f"   ❌ Network error: {str(e)}")
        except Exception as e:
            print(f"   ❌ Error: {str(e)}")
        
        print("-" * 50)
    
    print("\n🎯 Summary:")
    print("The enhanced search should now:")
    print("• Show related products across multiple categories")
    print("• Group results by category for better organization")
    print("• Expand keywords to find more relevant products")
    print("• Display distinct products for each category")
    print("\n💡 Try searching for 'sweet' - you should see:")
    print("   🍫 Chocolate products")
    print("   🍫✨ Custom chocolate products") 
    print("   🥜 Nut products")
    print("   All grouped by category!")

if __name__ == "__main__":
    test_comprehensive_search()



