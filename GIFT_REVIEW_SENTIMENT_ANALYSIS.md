# Gift Review Sentiment Analysis using Naive Bayes

## Overview
A Python program that classifies gift review sentiments as **Positive**, **Neutral**, or **Negative** using the Naive Bayes classifier from scikit-learn.

## Features

### 1. **Text Preprocessing**
- **Lowercasing**: Converts all text to lowercase for consistency
- **Stopword Removal**: Removes common English stopwords using scikit-learn's built-in stop word list
- **Special Character Removal**: Cleans text by removing special characters
- **Whitespace Normalization**: Removes extra spaces

### 2. **Feature Extraction**
- Uses **CountVectorizer** from scikit-learn
- Converts text into numerical features (word counts)
- Limits to top 1000 features for efficiency
- Automatically handles stopwords and lowercasing

### 3. **Model Training**
- Uses **MultinomialNB** (Multinomial Naive Bayes) classifier
- Perfect for text classification with word count features
- Trains on 45 sample reviews (15 per sentiment class)

### 4. **Model Evaluation**
- **Accuracy**: 77.78% on test set
- **Confusion Matrix**: Shows correct and incorrect predictions
- **Classification Report**: Detailed metrics including precision, recall, and F1-score

## Sample Results

### Test Reviews and Predictions:

1. **"Loved the custom mug!"**
   - Sentiment: **Positive** (75.11% confidence)

2. **"The delivery was delayed."**
   - Sentiment: **Negative** (57.89% confidence)

3. **"Product quality is okay."**
   - Sentiment: **Neutral** (62.05% confidence)

## Performance Metrics

### Confusion Matrix:
| Actual/Predicted | Negative | Neutral | Positive |
|------------------|----------|---------|----------|
| Negative         | 3        | 0       | 0        |
| Neutral          | 1        | 2       | 0        |
| Positive         | 1        | 0       | 2        |

### Classification Report:
- **Positive**: Precision=1.00, Recall=0.67, F1=0.80
- **Neutral**: Precision=1.00, Recall=0.67, F1=0.80
- **Negative**: Precision=0.60, Recall=1.00, F1=0.75
- **Overall Accuracy**: 77.78%

## Usage

### Running the Program:
```bash
cd python_ml_service
python gift_review_sentiment_analysis.py
```

### Using the Classifier in Your Code:
```python
from gift_review_sentiment_analysis import GiftReviewSentimentAnalyzer

# Initialize and train
analyzer = GiftReviewSentimentAnalyzer()
reviews, labels = analyzer.load_sample_data()
analyzer.train(reviews, labels)

# Predict sentiment
review = "This product is amazing!"
prediction, probabilities = analyzer.predict(review)
print(f"Sentiment: {prediction}")
print(f"Confidence: {probabilities[prediction]:.2%}")
```

## Implementation Details

### Class: `GiftReviewSentimentAnalyzer`

**Methods:**
- `preprocess(text)`: Cleans and normalizes text
- `load_sample_data()`: Returns training data with 45 reviews
- `train(reviews, labels)`: Trains the Naive Bayes model
- `predict(review)`: Predicts sentiment and returns probabilities
- `evaluate(reviews, labels)`: Evaluates model and displays metrics

### Technologies Used:
- **scikit-learn**: Machine learning library
  - `CountVectorizer`: Text feature extraction
  - `MultinomialNB`: Naive Bayes classifier
  - `train_test_split`: Data splitting
  - `accuracy_score`, `confusion_matrix`, `classification_report`: Evaluation metrics
- **NumPy**: Numerical computing
- **Regular Expressions**: Text preprocessing

## Why Naive Bayes for Sentiment Analysis?

1. **Fast and Efficient**: Quick training and prediction
2. **Works Well with Text**: Handles sparse word count features effectively
3. **Small Dataset Friendly**: Performs well even with limited training data
4. **Probabilistic**: Provides probability scores for each class
5. **Interpretable**: Easy to understand the model's decision-making

## Dataset

The program includes 45 sample reviews covering gift-related products:
- **15 Positive**: Expressing satisfaction and delight
- **15 Negative**: Containing complaints and issues
- **15 Neutral**: Expressing neutral or average feelings

Examples include reviews about custom mugs, gift boxes, wedding hampers, chocolates, photo frames, and delivery service.

## Future Enhancements

1. **Expand Dataset**: Add more training data for better accuracy
2. **TF-IDF Vectorization**: Replace CountVectorizer with TfidfVectorizer
3. **N-grams**: Include bigrams and trigrams for better context
4. **Real-time API**: Create a REST API endpoint for predictions
5. **Database Integration**: Connect to review database for live analysis
6. **Aspect-based Analysis**: Identify specific aspects (quality, delivery, service)

## Files

- **`gift_review_sentiment_analysis.py`**: Main Python program with full implementation
- **`GIFT_REVIEW_SENTIMENT_ANALYSIS.md`**: This documentation

## Requirements

```txt
scikit-learn>=1.0.0
numpy>=1.20.0
```

Install dependencies:
```bash
pip install scikit-learn numpy
```


