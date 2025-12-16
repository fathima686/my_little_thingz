#!/usr/bin/env python3
"""
Test script for Python ML Microservice
Tests all API endpoints to ensure they're working correctly
"""

import requests
import json
import time

# Configuration
BASE_URL = "http://localhost:5000"
API_BASE = f"{BASE_URL}/api/ml"

def test_health_check():
    """Test health check endpoint"""
    print("🔍 Testing Health Check...")
    try:
        response = requests.get(f"{API_BASE}/health")
        if response.status_code == 200:
            print("✅ Health check passed")
            print(f"   Response: {response.json()}")
            return True
        else:
            print(f"❌ Health check failed: {response.status_code}")
            return False
    except Exception as e:
        print(f"❌ Health check error: {str(e)}")
        return False

def test_knn_recommendations():
    """Test KNN recommendations endpoint"""
    print("\n🔍 Testing KNN Recommendations...")
    try:
        data = {
            "product_id": 1,
            "user_id": 1,
            "k": 5
        }
        response = requests.post(f"{API_BASE}/knn/recommendations", json=data)
        if response.status_code == 200:
            result = response.json()
            print("✅ KNN recommendations passed")
            print(f"   Algorithm: {result.get('algorithm', 'N/A')}")
            print(f"   Recommendations: {len(result.get('recommendations', []))}")
            return True
        else:
            print(f"❌ KNN failed: {response.status_code}")
            print(f"   Response: {response.text}")
            return False
    except Exception as e:
        print(f"❌ KNN error: {str(e)}")
        return False

def test_bayesian_classify():
    """Test Bayesian classification endpoint"""
    print("\n🔍 Testing Bayesian Classification...")
    try:
        data = {
            "gift_name": "Custom Chocolate Box",
            "confidence_threshold": 0.75
        }
        response = requests.post(f"{API_BASE}/bayesian/classify", json=data)
        if response.status_code == 200:
            result = response.json()
            print("✅ Bayesian classification passed")
            print(f"   Predicted Category: {result.get('predicted_category', 'N/A')}")
            print(f"   Confidence: {result.get('confidence_percent', 'N/A')}%")
            print(f"   Action: {result.get('action', 'N/A')}")
            return True
        else:
            print(f"❌ Bayesian failed: {response.status_code}")
            print(f"   Response: {response.text}")
            return False
    except Exception as e:
        print(f"❌ Bayesian error: {str(e)}")
        return False

def test_decision_tree_addon():
    """Test Decision Tree addon suggestions endpoint"""
    print("\n🔍 Testing Decision Tree Addon Suggestions...")
    try:
        data = {
            "cart_total": 1500,
            "cart_items": [
                {"id": 1, "price": 1000},
                {"id": 2, "price": 500}
            ]
        }
        response = requests.post(f"{API_BASE}/decision-tree/addon-suggestion", json=data)
        if response.status_code == 200:
            result = response.json()
            print("✅ Decision Tree addon suggestions passed")
            print(f"   Suggested Addons: {len(result.get('suggested_addons', []))}")
            print(f"   Confidence: {result.get('confidence', 'N/A')}")
            print(f"   Reasoning: {result.get('reasoning', 'N/A')}")
            return True
        else:
            print(f"❌ Decision Tree failed: {response.status_code}")
            print(f"   Response: {response.text}")
            return False
    except Exception as e:
        print(f"❌ Decision Tree error: {str(e)}")
        return False

def test_svm_classify():
    """Test SVM classification endpoint"""
    print("\n🔍 Testing SVM Classification...")
    try:
        data = {
            "gift_data": {
                "price": 1200,
                "category_id": 3,
                "title": "Premium Gift Box",
                "description": "Luxury handmade gift box",
                "availability": "limited",
                "rating": 4.5,
                "popularity": 0.8
            }
        }
        response = requests.post(f"{API_BASE}/svm/classify", json=data)
        if response.status_code == 200:
            result = response.json()
            print("✅ SVM classification passed")
            print(f"   Prediction: {result.get('prediction', 'N/A')}")
            print(f"   Confidence: {result.get('confidence', 'N/A')}")
            print(f"   Score: {result.get('score', 'N/A')}")
            return True
        else:
            print(f"❌ SVM failed: {response.status_code}")
            print(f"   Response: {response.text}")
            return False
    except Exception as e:
        print(f"❌ SVM error: {str(e)}")
        return False

def test_bpnn_predict():
    """Test BPNN preference prediction endpoint"""
    print("\n🔍 Testing BPNN Preference Prediction...")
    try:
        data = {
            "user_data": {
                "age": 25,
                "purchase_frequency": 0.5,
                "avg_order_value": 800,
                "preferred_categories": 3,
                "session_duration": 1200,
                "page_views": 15,
                "time_on_site": 1800,
                "device_type": 1,
                "location_score": 0.7
            },
            "product_data": {
                "price": 500,
                "category_id": 2,
                "rating": 4.5,
                "popularity": 0.6,
                "stock_level": 0.8,
                "discount_percentage": 0.1
            }
        }
        response = requests.post(f"{API_BASE}/bpnn/predict-preference", json=data)
        if response.status_code == 200:
            result = response.json()
            print("✅ BPNN preference prediction passed")
            print(f"   Preference Score: {result.get('preference_score', 'N/A')}")
            print(f"   Confidence: {result.get('confidence', 'N/A')}")
            print(f"   Recommendation: {result.get('recommendation', 'N/A')}")
            return True
        else:
            print(f"❌ BPNN failed: {response.status_code}")
            print(f"   Response: {response.text}")
            return False
    except Exception as e:
        print(f"❌ BPNN error: {str(e)}")
        return False

def test_train_models():
    """Test model training endpoint"""
    print("\n🔍 Testing Model Training...")
    try:
        data = {
            "algorithm": "all"
        }
        response = requests.post(f"{API_BASE}/models/train", json=data)
        if response.status_code == 200:
            result = response.json()
            print("✅ Model training passed")
            print(f"   Message: {result.get('message', 'N/A')}")
            print(f"   Results: {result.get('results', {})}")
            return True
        else:
            print(f"❌ Model training failed: {response.status_code}")
            print(f"   Response: {response.text}")
            return False
    except Exception as e:
        print(f"❌ Model training error: {str(e)}")
        return False

def run_all_tests():
    """Run all tests"""
    print("🚀 Starting Python ML Microservice Tests")
    print("=" * 50)
    
    tests = [
        ("Health Check", test_health_check),
        ("KNN Recommendations", test_knn_recommendations),
        ("Bayesian Classification", test_bayesian_classify),
        ("Decision Tree Addon Suggestions", test_decision_tree_addon),
        ("SVM Classification", test_svm_classify),
        ("BPNN Preference Prediction", test_bpnn_predict),
        ("Model Training", test_train_models)
    ]
    
    passed = 0
    total = len(tests)
    
    for test_name, test_func in tests:
        print(f"\n{'='*20} {test_name} {'='*20}")
        if test_func():
            passed += 1
        time.sleep(1)  # Brief pause between tests
    
    print("\n" + "=" * 50)
    print(f"🏁 Test Results: {passed}/{total} tests passed")
    
    if passed == total:
        print("🎉 All tests passed! Your Python ML Microservice is working correctly.")
    else:
        print("⚠️  Some tests failed. Check the service logs for details.")
    
    return passed == total

if __name__ == "__main__":
    print("Python ML Microservice Test Suite")
    print("Make sure the service is running on http://localhost:5000")
    print("Press Enter to start tests...")
    input()
    
    success = run_all_tests()
    exit(0 if success else 1)


