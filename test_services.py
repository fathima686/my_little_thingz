#!/usr/bin/env python3
"""
Quick test to verify services are working
"""

import requests
import time

def test_services():
    print("🔍 Testing Services...")
    
    # Test 1: Python ML Service
    print("\n1. Testing Python ML Service...")
    try:
        response = requests.get("http://localhost:5001/api/ml/health", timeout=3)
        if response.status_code == 200:
            print("   ✅ ML Service is running!")
            data = response.json()
            print(f"   Status: {data.get('status')}")
        else:
            print(f"   ❌ ML Service HTTP {response.status_code}")
    except Exception as e:
        print(f"   ❌ ML Service error: {str(e)}")
        print("   💡 Start with: cd python_ml_service && python app.py")
    
    # Test 2: Enhanced Search API
    print("\n2. Testing Enhanced Search API...")
    try:
        url = "http://localhost/my_little_thingz/backend/api/customer/enhanced-search.php"
        params = {'action': 'search', 'term': 'sweet', 'limit': 3}
        response = requests.get(url, params=params, timeout=5)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('status') == 'success':
                results = data.get('data', {})
                print(f"   ✅ Enhanced Search API working!")
                print(f"   Found {len(results.get('artworks', []))} results")
            else:
                print(f"   ❌ API error: {data.get('message')}")
        else:
            print(f"   ❌ API HTTP {response.status_code}")
    except Exception as e:
        print(f"   ❌ API error: {str(e)}")
    
    # Test 3: ML Prediction
    print("\n3. Testing ML Prediction...")
    try:
        ml_url = "http://localhost:5001/api/ml/bayesian/search-recommendations"
        ml_data = {"keyword": "sweet", "limit": 3, "confidence_threshold": 0.6}
        response = requests.post(ml_url, json=ml_data, timeout=5)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                print("   ✅ ML Prediction working!")
                print(f"   Predicted: {data.get('predicted_category')}")
                print(f"   Confidence: {data.get('confidence_percent', 0):.1f}%")
            else:
                print(f"   ❌ ML error: {data.get('error')}")
        else:
            print(f"   ❌ ML HTTP {response.status_code}")
    except Exception as e:
        print(f"   ❌ ML error: {str(e)}")
    
    print("\n🎯 Ready to test Enhanced Search!")
    print("   - Open your website")
    print("   - Look for the AI Enhanced Search button (magic wand)")
    print("   - Try searching with: 'sweet', 'romantic', 'custom', 'premium'")

if __name__ == "__main__":
    test_services()



