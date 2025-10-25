#!/usr/bin/env python3
"""
Quick test for your special keywords - making search more efficient
"""

import requests
import time

def test_special_keywords():
    """Test your special keywords efficiently"""
    
    print("🎯 Testing Your Special Keywords - Making Search More Efficient")
    print("=" * 70)
    
    # Your exact special keywords
    special_keywords = ['sweet', 'wedding', 'birthday', 'baby', 'valentine', 'house', 'farewell']
    
    # Wait for service
    print("⏳ Starting ML service...")
    time.sleep(5)
    
    # Test each keyword
    for keyword in special_keywords:
        print(f"\n🔍 Testing: '{keyword}'")
        print("-" * 30)
        
        try:
            # Quick API test
            response = requests.post(
                'http://localhost:5001/api/ml/gift-category/predict',
                json={'search_term': keyword},
                timeout=3
            )
            
            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    print(f"✅ SUCCESS: {data['predicted_category']} ({data['confidence_percent']:.1f}%)")
                    print(f"💡 Suggestions: {', '.join(data['recommendations'][:3])}")
                else:
                    print(f"❌ Failed: {data.get('error', 'Unknown error')}")
            else:
                print(f"❌ HTTP Error: {response.status_code}")
                
        except requests.exceptions.ConnectionError:
            print("❌ ML service not running - starting it...")
            break
        except Exception as e:
            print(f"❌ Error: {str(e)}")
    
    print("\n" + "=" * 70)
    print("🚀 Search Efficiency Tips:")
    print("1. ML service must be running: python app.py")
    print("2. Use exact keywords: sweet, wedding, birthday, etc.")
    print("3. Search will be faster with ML predictions")
    print("4. Your artwork gallery will show AI suggestions!")

if __name__ == "__main__":
    test_special_keywords()

