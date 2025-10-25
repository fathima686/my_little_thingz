#!/usr/bin/env python3
"""
Simple test for Enhanced Search functionality
"""

import requests
import json

def test_ml_service():
    """Test the ML service directly"""
    print("🧠 Testing ML Service...")
    
    try:
        # Test ML service health
        response = requests.get("http://localhost:5001/api/ml/health", timeout=5)
        if response.status_code == 200:
            print("✅ ML Service is running!")
            data = response.json()
            print(f"   Status: {data.get('status')}")
            print(f"   Algorithms: {data.get('algorithms')}")
        else:
            print(f"❌ ML Service HTTP {response.status_code}")
            return False
    except Exception as e:
        print(f"❌ ML Service error: {str(e)}")
        return False
    
    # Test Bayesian classifier
    try:
        ml_url = "http://localhost:5001/api/ml/bayesian/search-recommendations"
        ml_data = {
            "keyword": "sweet",
            "limit": 3,
            "confidence_threshold": 0.6
        }
        
        response = requests.post(ml_url, json=ml_data, timeout=10)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                print("✅ Bayesian Classifier working!")
                print(f"   Predicted: {data.get('predicted_category')}")
                print(f"   Confidence: {data.get('confidence_percent', 0):.1f}%")
                print(f"   Algorithm: {data.get('algorithm')}")
                return True
            else:
                print(f"❌ Classifier error: {data.get('error')}")
        else:
            print(f"❌ Classifier HTTP {response.status_code}")
    except Exception as e:
        print(f"❌ Classifier error: {str(e)}")
    
    return False

def test_enhanced_search_api():
    """Test the enhanced search API"""
    print("\n🔍 Testing Enhanced Search API...")
    
    # Test with correct path
    test_urls = [
        "http://localhost/backend/api/customer/enhanced-search.php",
        "http://localhost/my_little_thingz/backend/api/customer/enhanced-search.php"
    ]
    
    for url in test_urls:
        try:
            params = {
                'action': 'search',
                'term': 'sweet',
                'limit': 3
            }
            
            response = requests.get(url, params=params, timeout=5)
            print(f"   Testing: {url}")
            print(f"   Status: {response.status_code}")
            
            if response.status_code == 200:
                data = response.json()
                print(f"   Response: {data.get('status')}")
                if data.get('status') == 'success':
                    results = data.get('data', {})
                    print(f"   ✅ Found {len(results.get('artworks', []))} results")
                    return True
            else:
                print(f"   ❌ HTTP {response.status_code}")
                
        except Exception as e:
            print(f"   ❌ Error: {str(e)}")
    
    return False

if __name__ == "__main__":
    print("🚀 Testing Enhanced Search System")
    print("=" * 50)
    
    ml_working = test_ml_service()
    api_working = test_enhanced_search_api()
    
    print("\n📊 Summary:")
    print(f"   ML Service: {'✅ Working' if ml_working else '❌ Not Working'}")
    print(f"   Enhanced API: {'✅ Working' if api_working else '❌ Not Working'}")
    
    if ml_working and api_working:
        print("\n🎉 Enhanced Search System is ready!")
        print("   You can now search with keywords like 'sweet', 'chocolate', 'flower'")
        print("   The system will use AI to predict categories and find relevant products")
    else:
        print("\n⚠️  Some components need attention")
        if not ml_working:
            print("   - Start the Python ML service: cd python_ml_service && python app.py")
        if not api_working:
            print("   - Check the API path and ensure XAMPP is running")



