"""Test the sentiment analysis API endpoint"""
import requests
import json

# Test the API
test_reviews = [
    "good product",
    "super boqutes i like very much",
    "delay of order lag in date"
]

print("Testing Sentiment Analysis API...\n")
print("=" * 70)

for review in test_reviews:
    try:
        response = requests.post(
            'http://localhost:5001/api/ml/sentiment/analyze',
            json={'review_text': review},
            headers={'Content-Type': 'application/json'}
        )
        
        if response.status_code == 200:
            data = response.json()
            print(f"\nReview: \"{review}\"")
            print(f"Sentiment: {data['sentiment'].upper()} ({data['confidence_percent']}%)")
            print(f"Recommended Action: {data['recommended_action']}")
        else:
            print(f"\nError: {response.status_code}")
            print(response.text)
    except requests.exceptions.ConnectionError:
        print("\n❌ Cannot connect to Flask server. Is it running?")
        print("Start it with: python app.py")
        break
    except Exception as e:
        print(f"\nError: {e}")

print("\n" + "=" * 70)
print("Test complete!")


