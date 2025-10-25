#!/usr/bin/env python3
"""
Test script to verify your exact search inputs work correctly
"""

import requests
import time

def test_your_search_inputs():
    """Test your exact search inputs"""
    
    print("🎯 Testing Your Exact Search Inputs")
    print("=" * 60)
    
    # Your exact requirements
    test_cases = [
        {
            'search_term': 'sweet',
            'expected_suggestions': ['Custom Chocolate Box', 'Chocolate Bouquet']
        },
        {
            'search_term': 'wedding',
            'expected_suggestions': ['Wedding Card', 'Couple Frame', 'Wedding Hamper']
        },
        {
            'search_term': 'birthday',
            'expected_suggestions': ['Birthday Cake Topper', 'Birthday Mug', 'Greeting Card']
        },
        {
            'search_term': 'baby',
            'expected_suggestions': ['Baby Rattle', 'Soft Toy', 'Baby Blanket']
        },
        {
            'search_term': 'valentine',
            'expected_suggestions': ['Love Frame', 'Heart Chocolate', 'Couple Lamp']
        },
        {
            'search_term': 'house',
            'expected_suggestions': ['Wall Frame', 'Indoor Plant', 'Name Plate']
        },
        {
            'search_term': 'farewell',
            'expected_suggestions': ['Pen Set', 'Thank You Card', 'Planner Diary']
        }
    ]
    
    # Wait for service to start
    print("⏳ Waiting for ML service to start...")
    time.sleep(3)
    
    # Test each case
    for i, test_case in enumerate(test_cases, 1):
        print(f"\n🔍 Test {i}: '{test_case['search_term']}'")
        print("-" * 40)
        
        try:
            # Test the gift category predictor
            response = requests.post(
                'http://localhost:5001/api/ml/gift-category/predict',
                json={
                    'search_term': test_case['search_term'],
                    'confidence_threshold': 0.6
                },
                timeout=10
            )
            
            if response.status_code == 200:
                data = response.json()
                
                if data.get('success'):
                    predicted_category = data.get('predicted_category', 'unknown')
                    confidence = data.get('confidence', 0)
                    suggestions = data.get('recommendations', [])
                    
                    print(f"✅ Predicted Category: {predicted_category}")
                    print(f"📊 Confidence: {confidence:.1%}")
                    print(f"💡 Suggestions: {', '.join(suggestions)}")
                    
                    # Check if suggestions match your requirements
                    expected = test_case['expected_suggestions']
                    matches = 0
                    for suggestion in suggestions:
                        if suggestion in expected:
                            matches += 1
                    
                    if matches == len(expected):
                        print("🎯 PERFECT MATCH! All suggestions are correct!")
                    elif matches > 0:
                        print(f"✅ Good match: {matches}/{len(expected)} suggestions correct")
                    else:
                        print("⚠️ Suggestions don't match your requirements")
                        
                else:
                    print(f"❌ Prediction failed: {data.get('error', 'Unknown error')}")
                    
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
    print("🎉 Your Search Inputs Testing Complete!")
    print("\n🚀 How to Use:")
    print("1. Start ML service: python app.py")
    print("2. Go to your artwork gallery")
    print("3. Search for 'sweet', 'wedding', 'birthday', etc.")
    print("4. See your exact suggestions appear!")

if __name__ == "__main__":
    test_your_search_inputs()


