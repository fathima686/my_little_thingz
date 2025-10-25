#!/usr/bin/env python3
"""
Simple test to check API response
"""

import requests

def test_api_response():
    print("🔍 Testing API Response...")
    
    url = "http://localhost/my_little_thingz/backend/api/customer/enhanced-search.php"
    params = {'action': 'search', 'term': 'sweet', 'limit': 5}
    
    try:
        response = requests.get(url, params=params, timeout=10)
        print(f"Status Code: {response.status_code}")
        print(f"Headers: {dict(response.headers)}")
        print(f"Content: {response.text[:500]}...")
        
        if response.status_code == 200:
            try:
                data = response.json()
                print(f"JSON Data: {data}")
            except:
                print("Failed to parse JSON")
        else:
            print("Non-200 status code")
            
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    test_api_response()



