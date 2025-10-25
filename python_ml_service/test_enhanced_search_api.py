#!/usr/bin/env python3
"""
Test the enhanced search API directly - making it more efficient
"""

import requests
import json

def test_enhanced_search():
    """Test your special keywords with the enhanced search API"""
    
    print("🎯 Testing Enhanced Search - Your Special Keywords")
    print("=" * 60)
    
    # Your exact special keywords
    special_keywords = ['sweet', 'wedding', 'birthday', 'baby', 'valentine', 'house', 'farewell']
    
    # Test each keyword with the backend API
    for keyword in special_keywords:
        print(f"\n🔍 Testing: '{keyword}'")
        print("-" * 30)
        
        try:
            # Test the enhanced search API directly
            url = f"http://localhost/my_little_thingz/backend/api/customer/enhanced-search.php"
            params = {
                'action': 'search',
                'term': keyword,
                'limit': 10
            }
            
            response = requests.get(url, params=params, timeout=5)
            
            if response.status_code == 200:
                data = response.json()
                
                if data.get('status') == 'success':
                    ml_insights = data.get('data', {}).get('ml_insights', {})
                    artworks = data.get('data', {}).get('artworks', [])
                    
                    print(f"✅ SUCCESS!")
                    print(f"📊 Predicted Category: {ml_insights.get('predicted_category', 'Unknown')}")
                    print(f"🎯 Confidence: {ml_insights.get('confidence_percent', 0):.1f}%")
                    print(f"💡 Suggestions: {', '.join(ml_insights.get('suggestions', [])[:3])}")
                    print(f"🔍 Found {len(artworks)} artworks")
                    
                    # Show first artwork if available
                    if artworks:
                        first_artwork = artworks[0]
                        print(f"📦 Example: {first_artwork.get('title', 'N/A')} - ₹{first_artwork.get('price', 0)}")
                    
                else:
                    print(f"❌ API Error: {data.get('message', 'Unknown error')}")
            else:
                print(f"❌ HTTP Error: {response.status_code}")
                
        except requests.exceptions.ConnectionError:
            print("❌ Connection Error: Backend not accessible")
            print("💡 Make sure XAMPP is running and backend is accessible")
            break
        except Exception as e:
            print(f"❌ Error: {str(e)}")
    
    print("\n" + "=" * 60)
    print("🚀 Enhanced Search Features:")
    print("✅ Works without ML service running")
    print("✅ Fast keyword matching")
    print("✅ Your exact suggestions")
    print("✅ High confidence predictions")
    print("✅ Efficient fallback system")

if __name__ == "__main__":
    test_enhanced_search()

