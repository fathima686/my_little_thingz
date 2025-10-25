#!/usr/bin/env python3
"""
Test script for Dynamic SVM Recommendation System
Tests all endpoints and functionality
"""

import requests
import json
import time
from datetime import datetime

# Configuration
PYTHON_ML_SERVICE_URL = "http://localhost:5001/api/ml/dynamic-svm"
PHP_API_URL = "http://localhost/my_little_thingz/backend/api/customer/dynamic_svm_recommendations.php"

def test_python_service_health():
    """Test if Python ML service is running"""
    try:
        response = requests.get("http://localhost:5001/api/ml/health", timeout=5)
        if response.status_code == 200:
            data = response.json()
            print("✅ Python ML Service is running")
            print(f"   Dynamic SVM Available: {data.get('dynamic_svm_available', False)}")
            return True
        else:
            print(f"❌ Python ML Service returned status {response.status_code}")
            return False
    except Exception as e:
        print(f"❌ Python ML Service connection failed: {e}")
        return False

def test_dynamic_svm_recommendations():
    """Test dynamic SVM recommendations endpoint"""
    print("\n🧪 Testing Dynamic SVM Recommendations...")
    
    # Test with a sample user ID
    test_data = {
        "user_id": 1,
        "limit": 5
    }
    
    try:
        response = requests.post(
            f"{PYTHON_ML_SERVICE_URL}/recommendations",
            json=test_data,
            timeout=30
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                print("✅ Dynamic SVM Recommendations working")
                print(f"   Recommendations: {len(data.get('recommendations', []))}")
                print(f"   Is New User: {data.get('is_new_user', False)}")
                print(f"   Model Version: {data.get('model_version', 'N/A')}")
                return True
            else:
                print(f"❌ Recommendations failed: {data.get('error', 'Unknown error')}")
                return False
        else:
            print(f"❌ HTTP {response.status_code}: {response.text}")
            return False
            
    except Exception as e:
        print(f"❌ Request failed: {e}")
        return False

def test_behavior_tracking():
    """Test user behavior tracking"""
    print("\n🧪 Testing Behavior Tracking...")
    
    test_data = {
        "user_id": 1,
        "product_id": 1,
        "behavior_type": "view",
        "additional_data": {
            "session_id": "test_session_123",
            "timestamp": datetime.now().isoformat()
        }
    }
    
    try:
        response = requests.post(
            f"{PYTHON_ML_SERVICE_URL}/update-behavior",
            json=test_data,
            timeout=10
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                print("✅ Behavior tracking working")
                return True
            else:
                print(f"❌ Behavior tracking failed: {data.get('error', 'Unknown error')}")
                return False
        else:
            print(f"❌ HTTP {response.status_code}: {response.text}")
            return False
            
    except Exception as e:
        print(f"❌ Request failed: {e}")
        return False

def test_model_prediction():
    """Test model prediction for specific product"""
    print("\n🧪 Testing Model Prediction...")
    
    test_data = {
        "user_id": 1,
        "product_id": 1
    }
    
    try:
        response = requests.post(
            f"{PYTHON_ML_SERVICE_URL}/predict",
            json=test_data,
            timeout=10
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                print("✅ Model prediction working")
                print(f"   Prediction: {data.get('prediction')}")
                print(f"   Confidence: {data.get('confidence', 0):.3f}")
                print(f"   Probability Like: {data.get('probability_like', 0):.3f}")
                return True
            else:
                print(f"❌ Prediction failed: {data.get('error', 'Unknown error')}")
                return False
        else:
            print(f"❌ HTTP {response.status_code}: {response.text}")
            return False
            
    except Exception as e:
        print(f"❌ Request failed: {e}")
        return False

def test_model_info():
    """Test model information endpoint"""
    print("\n🧪 Testing Model Information...")
    
    try:
        response = requests.get(f"{PYTHON_ML_SERVICE_URL}/model-info", timeout=10)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                model_info = data.get('model_info', {})
                print("✅ Model information available")
                print(f"   Model Version: {model_info.get('model_version', 'N/A')}")
                print(f"   Is Trained: {model_info.get('is_trained', False)}")
                print(f"   Total Interactions: {model_info.get('total_interactions', 0)}")
                return True
            else:
                print(f"❌ Model info failed: {data.get('error', 'Unknown error')}")
                return False
        else:
            print(f"❌ HTTP {response.status_code}: {response.text}")
            return False
            
    except Exception as e:
        print(f"❌ Request failed: {e}")
        return False

def test_php_integration():
    """Test PHP integration"""
    print("\n🧪 Testing PHP Integration...")
    
    try:
        # Test GET request for recommendations
        response = requests.get(
            f"{PHP_API_URL}?user_id=1&limit=3",
            timeout=30
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get('status') == 'success':
                print("✅ PHP Integration working")
                print(f"   Recommendations: {len(data.get('recommendations', []))}")
                print(f"   Is New User: {data.get('is_new_user', False)}")
                return True
            else:
                print(f"❌ PHP Integration failed: {data.get('message', 'Unknown error')}")
                return False
        else:
            print(f"❌ HTTP {response.status_code}: {response.text}")
            return False
            
    except Exception as e:
        print(f"❌ Request failed: {e}")
        return False

def test_behavior_tracking_php():
    """Test behavior tracking through PHP"""
    print("\n🧪 Testing PHP Behavior Tracking...")
    
    test_data = {
        "action": "update_behavior",
        "user_id": 1,
        "product_id": 1,
        "behavior_type": "purchase",
        "additional_data": {
            "order_id": 123,
            "quantity": 1,
            "price": 500
        }
    }
    
    try:
        response = requests.post(
            PHP_API_URL,
            json=test_data,
            timeout=10
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get('status') == 'success':
                print("✅ PHP Behavior tracking working")
                return True
            else:
                print(f"❌ PHP Behavior tracking failed: {data.get('message', 'Unknown error')}")
                return False
        else:
            print(f"❌ HTTP {response.status_code}: {response.text}")
            return False
            
    except Exception as e:
        print(f"❌ Request failed: {e}")
        return False

def run_comprehensive_test():
    """Run all tests"""
    print("🚀 Starting Dynamic SVM Recommendation System Tests")
    print("=" * 60)
    
    tests = [
        ("Python Service Health", test_python_service_health),
        ("Dynamic SVM Recommendations", test_dynamic_svm_recommendations),
        ("Behavior Tracking", test_behavior_tracking),
        ("Model Prediction", test_model_prediction),
        ("Model Information", test_model_info),
        ("PHP Integration", test_php_integration),
        ("PHP Behavior Tracking", test_behavior_tracking_php)
    ]
    
    results = []
    
    for test_name, test_func in tests:
        try:
            result = test_func()
            results.append((test_name, result))
        except Exception as e:
            print(f"❌ {test_name} failed with exception: {e}")
            results.append((test_name, False))
    
    # Summary
    print("\n" + "=" * 60)
    print("📊 TEST SUMMARY")
    print("=" * 60)
    
    passed = 0
    total = len(results)
    
    for test_name, result in results:
        status = "✅ PASS" if result else "❌ FAIL"
        print(f"{status} {test_name}")
        if result:
            passed += 1
    
    print(f"\nResults: {passed}/{total} tests passed")
    
    if passed == total:
        print("🎉 All tests passed! Dynamic SVM system is working correctly.")
    else:
        print("⚠️  Some tests failed. Check the logs above for details.")
    
    return passed == total

if __name__ == "__main__":
    run_comprehensive_test()






