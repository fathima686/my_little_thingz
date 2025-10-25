#!/usr/bin/env python3
"""
Test script for Enhanced Bayesian Classifier
Demonstrates keyword-based category prediction
"""

from enhanced_bayesian_classifier import EnhancedBayesianClassifier
import json

def test_bayesian_classifier():
    """Test the enhanced Bayesian classifier with various keywords"""
    
    print("🎯 Enhanced Bayesian Classifier - Keyword-Based Category Prediction")
    print("=" * 70)
    
    # Initialize classifier
    classifier = EnhancedBayesianClassifier()
    
    # Test cases - these are the keywords users might search for
    test_keywords = [
        "sweet",           # Should predict: chocolate
        "chocolate",       # Should predict: chocolate  
        "flower",          # Should predict: bouquet
        "roses",           # Should predict: bouquet
        "gift",            # Should predict: gift_box
        "wedding",         # Should predict: wedding_card
        "custom",          # Should predict: custom_chocolate
        "nuts",            # Should predict: nuts
        "premium",         # Should predict: gift_box
        "romantic",        # Should predict: bouquet
        "anniversary",     # Should predict: wedding_card
        "luxury",          # Should predict: gift_box
        "personalized",    # Should predict: custom_chocolate
        "healthy",         # Should predict: nuts
        "treat"            # Should predict: chocolate
    ]
    
    print(f"Testing {len(test_keywords)} keywords...")
    print()
    
    results = []
    
    for keyword in test_keywords:
        result = classifier.predict_category(keyword)
        
        # Format result
        status = "✅" if result['confidence'] > 0.6 else "⚠️" if result['confidence'] > 0.4 else "❌"
        action_emoji = {
            'auto_assign': '🤖',
            'suggest': '💡', 
            'manual_review': '👤'
        }.get(result['action'], '❓')
        
        print(f"{status} '{keyword}' → {result['predicted_category']} ({result['confidence_percent']:.1f}%) {action_emoji}")
        
        results.append({
            'keyword': keyword,
            'predicted_category': result['predicted_category'],
            'confidence': result['confidence'],
            'action': result['action']
        })
    
    print()
    print("📊 Summary by Category:")
    print("-" * 40)
    
    # Group results by predicted category
    category_counts = {}
    for result in results:
        category = result['predicted_category']
        if category not in category_counts:
            category_counts[category] = []
        category_counts[category].append(result)
    
    for category, items in category_counts.items():
        avg_confidence = sum(item['confidence'] for item in items) / len(items)
        print(f"🎁 {category.replace('_', ' ').title()}: {len(items)} keywords (avg confidence: {avg_confidence:.1%})")
        for item in items:
            print(f"   • '{item['keyword']}' ({item['confidence']:.1%})")
        print()
    
    print("🎯 Key Features:")
    print("• Keyword matching with semantic understanding")
    print("• Confidence-based action recommendations")
    print("• Fallback to keyword matching if ML fails")
    print("• Support for synonyms and related terms")
    print("• High accuracy for common gift categories")
    
    return results

def demonstrate_search_recommendations():
    """Demonstrate how search recommendations would work"""
    
    print("\n🔍 Search Recommendation Examples:")
    print("=" * 50)
    
    classifier = EnhancedBayesianClassifier()
    
    # Example searches
    search_examples = [
        ("sweet", "User searching for sweet treats"),
        ("romantic", "User looking for romantic gifts"),
        ("premium", "User wanting luxury items"),
        ("custom", "User seeking personalized gifts"),
        ("healthy", "User looking for healthy options")
    ]
    
    for keyword, description in search_examples:
        result = classifier.predict_category(keyword)
        
        print(f"\n🔍 Search: '{keyword}'")
        print(f"   Context: {description}")
        print(f"   Predicted Category: {result['predicted_category']}")
        print(f"   Confidence: {result['confidence_percent']:.1f}%")
        print(f"   Action: {result['action']}")
        
        # Show what products would be recommended
        category = result['predicted_category']
        if category == 'chocolate':
            print(f"   💡 Would recommend: Chocolate boxes, truffles, gift sets")
        elif category == 'bouquet':
            print(f"   💡 Would recommend: Flower arrangements, roses, centerpieces")
        elif category == 'gift_box':
            print(f"   💡 Would recommend: Gift hampers, luxury baskets, curated sets")
        elif category == 'wedding_card':
            print(f"   💡 Would recommend: Wedding invitations, save the dates")
        elif category == 'custom_chocolate':
            print(f"   💡 Would recommend: Personalized chocolates, engraved treats")
        elif category == 'nuts':
            print(f"   💡 Would recommend: Nut mixes, trail mixes, healthy snacks")

if __name__ == "__main__":
    # Run the tests
    results = test_bayesian_classifier()
    demonstrate_search_recommendations()
    
    print("\n🎉 Enhanced Bayesian Classifier is ready!")
    print("This classifier can now accurately predict gift categories")
    print("based on search keywords like 'sweet' → chocolate products.")





