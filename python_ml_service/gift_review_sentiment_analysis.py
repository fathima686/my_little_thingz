"""
Gift Review Sentiment Analysis using Naive Bayes Classifier
Classifies reviews as Positive, Neutral, or Negative
"""

import numpy as np
from sklearn.feature_extraction.text import CountVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, confusion_matrix, classification_report
import re

class GiftReviewSentimentAnalyzer:
    def __init__(self):
        self.vectorizer = CountVectorizer(
            lowercase=True,           # Convert to lowercase
            stop_words='english',     # Remove English stopwords
            max_features=1000          # Limit features for efficiency
        )
        self.model = MultinomialNB()
        self.is_trained = False
    
    def preprocess(self, text):
        """Preprocess text by lowercasing and removing special characters"""
        # Convert to lowercase
        text = text.lower()
        # Remove special characters except spaces
        text = re.sub(r'[^a-zA-Z\s]', '', text)
        # Remove extra whitespace
        text = ' '.join(text.split())
        return text
    
    def load_sample_data(self):
        """
        Load sample gift review data for training
        Returns training data (reviews) and labels (sentiments)
        """
        reviews = [
            # Positive reviews
            "Loved the custom mug! Perfect gift.",
            "Amazing quality and design! Highly recommend.",
            "Beautiful gift box set. Very happy with purchase.",
            "Excellent service and fast delivery. Great product.",
            "Love the personalized album. So unique and thoughtful.",
            "Perfect for my friend's birthday. She loved it!",
            "High quality chocolates. Delicious and beautifully wrapped.",
            "Wonderful gift idea. The recipient was thrilled.",
            "Great value for money. Excellent product quality.",
            "Fabulous wedding hamper. Exceeded expectations!",
            "Beautiful handmade drawings. Truly special gift.",
            "Lovely photo frame collection. Great for memories.",
            "Superb customer service. Will order again.",
            "Gorgeous gift packaging. Very impressed.",
            "Fantastic product. Highly recommend this gift shop.",
            "good product",  # Short positive review
            "very nice",  # Short positive review
            "loved it",  # Short positive review
            "i like very much",  # Short positive review
            "super good",  # Short positive review
            "best product",  # Short positive review
            
            # Negative reviews
            "The delivery was delayed. Very disappointed.",
            "Product quality was poor. Not worth the price.",
            "Terrible customer service. No refund provided.",
            "Item arrived damaged. Packaging was inadequate.",
            "Not satisfied with the product at all.",
            "Overpriced for what you get. Not recommended.",
            "Late delivery caused major inconvenience.",
            "Product broke within days. Poor quality.",
            "Customer support was unhelpful. Very frustrating experience.",
            "Item did not match description. False advertising.",
            "Awful gift wrapping. Looked nothing like the photo.",
            "Waste of money. Product was disappointing.",
            "Slow shipping and poor communication.",
            "Bad quality control. Received wrong item.",
            "Not worth buying. Poor overall experience.",
            "delay of order lag in date",  # Delay complaint
            "not good",  # Short negative review
            "poor quality",  # Short negative review
            
            # Neutral reviews
            "Product quality is okay. Nothing special.",
            "The item was decent but could be better.",
            "Average product for the price.",
            "It's alright. Expected something more exciting.",
            "Standard quality. No complaints but nothing impressive.",
            "Received as expected. Pretty standard gift.",
            "Decent gift option. Does the job.",
            "Alright delivery time. Product was fine.",
            "Mediocre quality. Not bad but not great either.",
            "Okay product. Just what I ordered.",
            "Acceptable quality. Nothing remarkable.",
            "Standard service. No issues to report.",
            "Average experience. Nothing to complain about.",
            "Decent enough for the price.",
            "Product is okay. Neutral feelings about it.",
            "its okay",  # Short neutral review
            "nothing special",  # Short neutral review
        ]
        
        labels = ['Positive'] * 21 + ['Negative'] * 18 + ['Neutral'] * 17
        
        return reviews, labels
    
    def train(self, reviews, labels):
        """Train the Naive Bayes model on provided reviews"""
        print("Training Naive Bayes Classifier...")
        print(f"Total reviews: {len(reviews)}")
        
        # Preprocess all reviews
        preprocessed_reviews = [self.preprocess(review) for review in reviews]
        
        # Extract features using CountVectorizer
        X = self.vectorizer.fit_transform(preprocessed_reviews)
        
        # Train the model
        self.model.fit(X, labels)
        self.is_trained = True
        
        print("Model trained successfully!")
    
    def predict(self, review):
        """Predict sentiment for a single review"""
        if not self.is_trained:
            raise ValueError("Model must be trained before making predictions")
        
        # Preprocess the review
        preprocessed = self.preprocess(review)
        
        # Extract features
        X = self.vectorizer.transform([preprocessed])
        
        # Predict
        prediction = self.model.predict(X)[0]
        probabilities = self.model.predict_proba(X)[0]
        
        # Get probabilities for each class
        prob_dict = {}
        classes = self.model.classes_
        for i, cls in enumerate(classes):
            prob_dict[cls] = probabilities[i]
        
        return prediction, prob_dict
    
    def evaluate(self, reviews, labels):
        """Evaluate the model and display accuracy and confusion matrix"""
        # Preprocess all reviews
        preprocessed_reviews = [self.preprocess(review) for review in reviews]
        
        # Extract features
        X = self.vectorizer.transform(preprocessed_reviews)
        
        # Make predictions
        predictions = self.model.predict(X)
        
        # Calculate accuracy
        accuracy = accuracy_score(labels, predictions)
        print(f"\n{'='*60}")
        print(f"MODEL EVALUATION")
        print(f"{'='*60}")
        print(f"\nAccuracy: {accuracy:.2%}")
        
        # Confusion matrix
        print(f"\nConfusion Matrix:")
        print(f"{'='*60}")
        cm = confusion_matrix(labels, predictions, labels=self.model.classes_)
        print(f"{' '*15}", end="")
        for cls in self.model.classes_:
            print(f"{cls:^15}", end="")
        print()
        
        for i, true_label in enumerate(self.model.classes_):
            print(f"{true_label:^15}", end="")
            for j in range(len(self.model.classes_)):
                print(f"{cm[i][j]:^15}", end="")
            print()
        
        # Classification report
        print(f"\n{'='*60}")
        print("Classification Report:")
        print(f"{'='*60}")
        print(classification_report(labels, predictions))
        
        return accuracy, cm


def main():
    """Main function to demonstrate the sentiment analyzer"""
    print("=" * 60)
    print("Gift Review Sentiment Analysis using Naive Bayes")
    print("=" * 60)
    
    # Initialize analyzer
    analyzer = GiftReviewSentimentAnalyzer()
    
    # Load sample data
    reviews, labels = analyzer.load_sample_data()
    
    # Split data into train and test sets
    X_train, X_test, y_train, y_test = train_test_split(
        reviews, labels, test_size=0.2, random_state=42, stratify=labels
    )
    
    print(f"\nDataset split:")
    print(f"Training samples: {len(X_train)}")
    print(f"Testing samples: {len(X_test)}")
    
    # Train the model
    analyzer.train(X_train, y_train)
    
    # Evaluate on test set
    analyzer.evaluate(X_test, y_test)
    
    # Test on specific sample reviews
    print(f"\n{'='*60}")
    print("TESTING ON SAMPLE REVIEWS")
    print(f"{'='*60}")
    
    test_reviews = [
        "Loved the custom mug!",
        "The delivery was delayed.",
        "Product quality is okay.",
        "Excellent service and beautiful product!",
        "Worst experience ever. Will never order again.",
        "It was fine. Nothing special though."
    ]
    
    print("\nPredictions for sample reviews:")
    print("-" * 60)
    
    for review in test_reviews:
        prediction, probabilities = analyzer.predict(review)
        max_prob = probabilities[prediction]
        
        print(f"\nReview: '{review}'")
        print(f"Sentiment: {prediction}")
        print(f"Confidence: {max_prob:.2%}")
        print(f"Probabilities:")
        for sentiment, prob in sorted(probabilities.items()):
            print(f"  {sentiment}: {prob:.2%}")
        print()
    
    print("=" * 60)
    print("Analysis Complete!")
    print("=" * 60)


if __name__ == "__main__":
    main()

