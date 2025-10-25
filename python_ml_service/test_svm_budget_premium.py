#!/usr/bin/env python3
"""
Test script for SVM Budget vs Premium Classification
Tests the integration with the ML service
"""

import requests
import time
import json

def test_svm_budget_premium():
    """Test SVM Budget vs Premium Classification"""
    
    print("🎯 Testing SVM Budget vs Premium Classification")
    print("=" * 60)
    
    # Test products with different price ranges and features
    test_products = [
        {
            'title': 'Simple Photo Frame',
            'description': 'Basic wooden frame',
            'price': 299,
            'category_id': 1,
            'availability': 'in_stock'
        },
        {
            'title': 'Luxury Designer Frame',
            'description': 'Premium wooden frame with gold accents and handcrafted details',
            'price': 1299,
            'category_id': 1,
            'availability': 'limited'
        },
        {
            'title': 'Custom Engraved Gift Box',
            'description': 'Personalized gift box with laser engraving and premium materials',
            'price': 899,
            'category_id': 2,
            'availability': 'limited'
        },
        {
            'title': 'Standard Wedding Card',
            'description': 'Simple invitation card',
            'price': 149,
            'category_id': 3,
            'availability': 'in_stock'
        },
        {
            'title': 'Deluxe Premium Hamper',
            'description': 'Exclusive luxury gift hamper with artisan chocolates and premium packaging',
            'price': 2499,
            'category_id': 2,
            'availability': 'limited'
        },
        {
            'title': 'Basic Gift Box',
            'description': 'Regular gift packaging',
            'price': 199,
            'category_id': 2,
            'availability': 'in_stock'
        }
    ]
    
    # Wait for service to start
    print("⏳ Waiting for ML service to start...")
    time.sleep(3)
    
    # Test each product
    for i, product in enumerate(test_products, 1):
        print(f"\n🔍 Test {i}: {product['title']}")
        print("-" * 40)
        print(f"   Price: ₹{product['price']}")
        print(f"   Description: {product['description']}")
        
        try:
            # Test the SVM classifier
            response = requests.post(
                'http://localhost:5001/api/ml/svm/budget-premium',
                json={
                    'product_data': product
                },
                timeout=10
            )
            
            if response.status_code == 200:
                data = response.json()
                
                if data.get('success'):
                    prediction = data.get('prediction', 'Unknown')
                    confidence = data.get('confidence_percent', 0)
                    reasoning = data.get('reasoning', [])
                    algorithm = data.get('algorithm', 'Unknown')
                    
                    print(f"✅ Classification: {prediction}")
                    print(f"📊 Confidence: {confidence:.1f}%")
                    print(f"🤖 Algorithm: {algorithm}")
                    print(f"💡 Reasoning:")
                    for reason in reasoning:
                        print(f"   • {reason}")
                    
                    # Check if classification makes sense
                    price = product['price']
                    if price >= 1000 and prediction == 'Premium':
                        print("🎯 CORRECT: High price correctly classified as Premium")
                    elif price < 1000 and prediction == 'Budget':
                        print("🎯 CORRECT: Low price correctly classified as Budget")
                    else:
                        print("⚠️ UNEXPECTED: Classification doesn't match price range")
                        
                else:
                    print(f"❌ Classification failed: {data.get('error', 'Unknown error')}")
                    
            else:
                print(f"❌ HTTP Error: {response.status_code}")
                
        except requests.exceptions.ConnectionError:
            print("❌ Connection Error: ML service not running")
            print("💡 Start the service with: python app.py")
            break
        except requests.exceptions.Timeout:
            print("❌ Timeout: Service took too long to respond")
        except Exception as e:
            print(f"❌ Error: {str(e)}")
    
    print("\n" + "=" * 60)
    print("🎉 SVM Budget vs Premium Classification Testing Complete!")
    print("\n🚀 How SVM Works:")
    print("1. SVM draws a line/boundary to separate gifts into Budget vs Premium")
    print("2. It analyzes multiple features: price, luxury keywords, premium indicators")
    print("3. It considers availability, customization level, material quality")
    print("4. It provides reasoning for each classification")
    print("\n💡 Integration:")
    print("• API Endpoint: /api/ml/svm/budget-premium")
    print("• Input: Product data (title, description, price, etc.)")
    print("• Output: Budget/Premium classification with confidence and reasoning")

if __name__ == "__main__":
    test_svm_budget_premium()


