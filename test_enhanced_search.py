#!/usr/bin/env python3
"""
Test script for Enhanced Search API
"""

import requests
import json

def test_enhanced_search():
    """Test the enhanced search functionality"""
    
    # Test cases
    test_cases = [
        "sweet",
        "chocolate", 
        "flower",
        "gift",
        "wedding",
        "custom",
        "premium",
        "romantic"
    ]
    
    print("🔍 Testing Enhanced Search API")
    print("=" * 50)
    
    for keyword in test_cases:
        try:
            # Test the enhanced search API
            url = f"http://localhost/backend/api/customer/enhanced-search.php"
            params = {
                'action': 'search',
                'term': keyword,
                'limit': 5
            }
            
            response = requests.get(url, params=params, timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                if data.get('status') == 'success':
                    results = data.get('data', {})
                    artworks = results.get('artworks', [])
                    ml_insights = results.get('ml_insights', {})
                    
                    print(f"✅ '{keyword}' → {len(artworks)} results")
                    if ml_insights:
                        predicted = ml_insights.get('predicted_category', 'unknown')
                        confidence = ml_insights.get('confidence_percent', 0)
                        print(f"   🧠 ML Prediction: {predicted} ({confidence:.1f}%)")
                    
                    # Show first result if available
                    if artworks:
                        first = artworks[0]
                        print(f"   📦 First result: {first.get('title', 'N/A')} - ₹{first.get('price', 0)}")
                else:
                    print(f"❌ '{keyword}' → Error: {data.get('message', 'Unknown error')}")
            else:
                print(f"❌ '{keyword}' → HTTP {response.status_code}")
                
        except requests.exceptions.RequestException as e:
            print(f"❌ '{keyword}' → Network error: {str(e)}")
        except Exception as e:
            print(f"❌ '{keyword}' → Error: {str(e)}")
        
        print("-" * 30)
    
    print("\n🎯 Testing ML Prediction API")
    print("=" * 50)
    
    # Test ML prediction directly
    try:
        ml_url = "http://localhost:5001/api/ml/bayesian/search-recommendations"
        ml_data = {
            "keyword": "sweet",
            "limit": 5,
            "confidence_threshold": 0.6
        }
        
        response = requests.post(ml_url, json=ml_data, timeout=10)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                print("✅ ML Service is working!")
                print(f"   Predicted Category: {data.get('predicted_category')}")
                print(f"   Confidence: {data.get('confidence_percent', 0):.1f}%")
                print(f"   Algorithm: {data.get('algorithm')}")
                print(f"   Recommendations: {len(data.get('recommendations', []))}")
            else:
                print(f"❌ ML Service error: {data.get('error', 'Unknown error')}")
        else:
            print(f"❌ ML Service HTTP {response.status_code}")
            
    except requests.exceptions.RequestException as e:
        print(f"❌ ML Service network error: {str(e)}")
    except Exception as e:
        print(f"❌ ML Service error: {str(e)}")

if __name__ == "__main__":
    test_enhanced_search()



