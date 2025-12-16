"""
Quick Sentiment Check - Analyze customer review
Run with: python quick_sentiment_check.py
"""

from gift_review_sentiment_analysis import GiftReviewSentimentAnalyzer

# Initialize
analyzer = GiftReviewSentimentAnalyzer()

# Train
print("Training model...")
reviews, labels = analyzer.load_sample_data()
analyzer.train(reviews, labels)
print("Model ready!\n")

# Analyze the three dashboard reviews
test_reviews = [
    "good product",
    "super boqutes i like very much", 
    "delay of order lag in date"
]

print("=" * 70)
print("ADMIN DASHBOARD REVIEW SENTIMENT ANALYSIS")
print("=" * 70)

for i, review in enumerate(test_reviews, 1):
    sentiment, probs = analyzer.predict(review)
    
    # Visual sentiment indicator
    if sentiment == "Positive":
        indicator = "✓ POSITIVE"
        color = "✓"
    elif sentiment == "Negative":
        indicator = "✗ NEGATIVE"
        color = "✗"
    else:
        indicator = "○ NEUTRAL"
        color = "○"
    
    print(f"\nReview: \"{review}\"")
    print(f"Sentiment: {indicator}")
    print(f"Confidence: {probs[sentiment]:.1%}")
    print(f"Details:")
    for s, p in sorted(probs.items(), key=lambda x: x[1], reverse=True):
        print(f"  {s}: {p:.1%}")

print("\n" + "=" * 70)
print("Analysis Complete!")
print("=" * 70)


