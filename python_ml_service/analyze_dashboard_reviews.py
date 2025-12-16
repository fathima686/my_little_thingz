"""
Analyze customer reviews from the Admin Dashboard
Shows sentiment classification for actual reviews
"""

from gift_review_sentiment_analysis import GiftReviewSentimentAnalyzer

def main():
    print("=" * 80)
    print("ADMIN DASHBOARD REVIEW SENTIMENT ANALYSIS")
    print("=" * 80)
    
    # Initialize analyzer
    analyzer = GiftReviewSentimentAnalyzer()
    
    # Load and train
    reviews, labels = analyzer.load_sample_data()
    analyzer.train(reviews, labels)
    
    print("\n" + "=" * 80)
    print("ANALYZING ACTUAL CUSTOMER REVIEWS FROM DASHBOARD")
    print("=" * 80)
    
    # Reviews from the dashboard image
    dashboard_reviews = [
        {
            "review_id": 6,
            "product": "wedding card",
            "rating": "5/5",
            "date": "10/27/2025",
            "text": "good product"
        },
        {
            "review_id": 5,
            "product": "boaqutes",
            "rating": "5/5",
            "date": "10/27/2025",
            "text": "super boqutes i like very much"
        },
        {
            "review_id": 4,
            "product": "Hamper",
            "rating": "4/5",
            "date": "10/24/2025",
            "text": "delay of order lag in date"
        }
    ]
    
    print("\nReview Analysis Results:")
    print("-" * 80)
    
    for i, review_data in enumerate(dashboard_reviews, 1):
        review_text = review_data["text"]
        prediction, probabilities = analyzer.predict(review_text)
        max_prob = probabilities[prediction]
        
        # Determine sentiment icon
        sentiment_icon = {
            "Positive": "✓ POSITIVE",
            "Negative": "✗ NEGATIVE",
            "Neutral": "○ NEUTRAL"
        }[prediction]
        
        # Color coding based on sentiment
        sentiment_colors = {
            "Positive": "✓ POSITIVE (Approve Recommended)",
            "Negative": "✗ NEGATIVE (Review with Customer)",
            "Neutral": "○ NEUTRAL (Standard Review)"
        }[prediction]
        
        print(f"\n{'='*80}")
        print(f"REVIEW #{review_data['review_id']}")
        print(f"{'='*80}")
        print(f"Product:     {review_data['product']}")
        print(f"Rating:      {review_data['rating']}")
        print(f"Date:        {review_data['date']}")
        print(f"Review Text: \"{review_text}\"")
        print(f"\nSENTIMENT CLASSIFICATION:")
        print(f"Status:      {sentiment_colors}")
        print(f"Confidence:  {max_prob:.2%}")
        print(f"\nDetailed Probabilities:")
        for sentiment, prob in sorted(probabilities.items(), key=lambda x: x[1], reverse=True):
            bar_length = int(prob * 40)
            bar = "█" * bar_length
            print(f"  {sentiment:^8}: [{bar:<40}] {prob:.2%}")
    
    # Summary statistics
    print(f"\n{'='*80}")
    print("SUMMARY")
    print(f"{'='*80}")
    
    sentiments = []
    for review_data in dashboard_reviews:
        prediction, _ = analyzer.predict(review_data["text"])
        sentiments.append(prediction)
    
    positive_count = sentiments.count("Positive")
    negative_count = sentiments.count("Negative")
    neutral_count = sentiments.count("Neutral")
    
    print(f"\nTotal Reviews Analyzed: {len(dashboard_reviews)}")
    print(f"\nSentiment Distribution:")
    print(f"  Positive: {positive_count} ({positive_count/len(dashboard_reviews)*100:.0f}%)")
    print(f"  Negative: {negative_count} ({negative_count/len(dashboard_reviews)*100:.0f}%)")
    print(f"  Neutral:  {neutral_count} ({neutral_count/len(dashboard_reviews)*100:.0f}%)")
    
    # Recommendations
    print(f"\n{'='*80}")
    print("MODERATION RECOMMENDATIONS")
    print(f"{'='*80}")
    
    for review_data in dashboard_reviews:
        prediction, probabilities = analyzer.predict(review_data["text"])
        print(f"\nReview #{review_data['review_id']} ({review_data['product']}):")
        print(f"  Sentiment: {prediction}")
        print(f"  Action: ", end="")
        
        if prediction == "Positive":
            print("✓ APPROVE - Customer is satisfied")
        elif prediction == "Negative":
            print("⚠ INVESTIGATE - Customer has concerns")
            print("  → Check with customer service about the delay issue")
            print("  → Consider offering compensation or apology")
        else:
            print("○ REVIEW - Standard neutral feedback")
    
    print(f"\n{'='*80}")
    print("Analysis Complete!")
    print(f"{'='*80}")


if __name__ == "__main__":
    main()


