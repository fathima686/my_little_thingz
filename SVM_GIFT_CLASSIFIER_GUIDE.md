# SVM Gift Trending Classifier

## Overview
A Support Vector Machine (SVM) classifier that predicts whether gifts are **Trending** or **Normal** based on sales metrics and user engagement.

## Features Used

1. **recent_sales_count** - Number of recent sales
2. **total_views** - Total page views
3. **average_rating** - Average customer rating
4. **number_of_reviews** - Total number of reviews

## Performance

✅ **Accuracy: 97.50%**

### Confusion Matrix:
```
              Normal    Trending
Normal          21         0
Trending        1         18
```

### Classification Report:
- **Normal**: Precision: 0.95, Recall: 1.00, F1: 0.98
- **Trending**: Precision: 1.00, Recall: 0.95, F1: 0.97

## How It Works

### Model Configuration
- **Algorithm**: SVM (Support Vector Machine)
- **Kernel**: RBF (Radial Basis Function)
- **Regularization**: C = 1.0
- **Feature Scaling**: StandardScaler applied

### Training Process
1. Create sample dataset (200 gifts: 97 Trending, 103 Normal)
2. Feature scaling with StandardScaler
3. Train/Test split (80/20)
4. Train SVM model with RBF kernel
5. Evaluate with accuracy, confusion matrix, and classification report

## Usage

### Run the Program
```bash
cd python_ml_service
python svm_gift_classifier.py
```

### Example Predictions

**Trending Gift Example:**
```
Gift: Popular Wedding Gift Box
  Recent Sales: 150
  Total Views: 2500
  Avg Rating: 4.8
  Num Reviews: 85
  Status: ✓ TRENDING ⬆️
```

**Normal Gift Example:**
```
Gift: Basic Photo Frame
  Recent Sales: 25
  Total Views: 300
  Avg Rating: 3.5
  Num Reviews: 12
  Status: ○ NORMAL
```

## Program Features

### 1. Dataset Creation
- Generates realistic sample data
- Balanced class distribution (50/50)
- Trending gifts: Higher sales, views, ratings
- Normal gifts: Lower engagement metrics

### 2. Feature Scaling
- Uses `StandardScaler` to normalize features
- Ensures all features are on the same scale
- Critical for SVM performance

### 3. Model Training
- **Kernel**: RBF (Radial Basis Function)
- **C parameter**: 1.0 (regularization)
- **Random state**: 42 for reproducibility

### 4. Evaluation
- Accuracy score
- Confusion matrix visualization
- Classification report (precision, recall, F1-score)

### 5. Manual Predictions
- Test specific gift examples
- Understand why gifts are trending
- Interactive mode for real-time predictions

## Code Structure

```python
# Initialize classifier
classifier = GiftTrendingClassifier(kernel='rbf', C=1.0)

# Create and prepare data
df = classifier.create_sample_dataset()
X = df[['recent_sales_count', 'total_views', 'average_rating', 'number_of_reviews']]
y = df['status']

# Split data
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2)

# Train model
classifier.train(X_train, y_train)

# Evaluate
classifier.evaluate(X_test, y_test)

# Predict
prediction = classifier.predict(features)
```

## Customization

### Change Kernel
```python
# Linear kernel
classifier = GiftTrendingClassifier(kernel='linear', C=1.0)

# Polynomial kernel
classifier = GiftTrendingClassifier(kernel='poly', C=1.0)
```

### Adjust Regularization
```python
# Stronger regularization (prevents overfitting)
classifier = GiftTrendingClassifier(C=0.1)

# Weaker regularization (allows more complex boundaries)
classifier = GiftTrendingClassifier(C=10.0)
```

## Class Characteristics

### Trending Gifts
- High recent sales (50-200)
- High total views (500-3000)
- Excellent ratings (4.0-5.0)
- Many reviews (20-100)

### Normal Gifts
- Low recent sales (0-50)
- Lower views (50-500)
- Average ratings (3.0-4.5)
- Fewer reviews (5-30)

## Integration with Your System

To integrate with your actual gift shop data:

```python
# Load your actual data
your_data = load_gifts_from_database()

# Extract features
X = your_data[['recent_sales_count', 'total_views', 'average_rating', 'number_of_reviews']]
y = your_data['status']  # Your target column

# Train model
classifier.train(X, y)

# Predict for new gifts
new_gift = [[100, 1500, 4.5, 50]]
prediction = classifier.predict(new_gift)
print(f"Status: {prediction[0]}")
```

## Files Created

- **`svm_gift_classifier.py`** - Main program with full implementation
- **`SVM_GIFT_CLASSIFIER_GUIDE.md`** - This documentation

## Requirements

```txt
scikit-learn>=1.0.0
numpy>=1.20.0
pandas>=1.3.0
```

## Advantages of SVM

1. **Effective for binary classification**
2. **Works well with high-dimensional data**
3. **RBF kernel captures non-linear relationships**
4. **Robust to outliers**
5. **Good generalization**

## Use Cases

✅ Identify trending gift products
✅ Monitor product popularity
✅ Make inventory decisions
✅ Optimize marketing campaigns
✅ Forecast sales potential

## Next Steps

1. **Collect Real Data**: Use your actual gift sales data
2. **Feature Engineering**: Add more relevant features
3. **Hyperparameter Tuning**: Optimize C and gamma parameters
4. **Cross-Validation**: Improve model validation
5. **Dashboard Integration**: Display trending gifts in admin panel

## Example: Predict a Specific Gift

```python
# Create gift data
features = np.array([[
    150,   # recent_sales_count
    2500,  # total_views
    4.8,   # average_rating
    85     # number_of_reviews
]])

# Predict
status = classifier.predict(features)
print(f"Gift status: {status[0]}")
```

## Troubleshooting

**Issue**: Low accuracy
**Solution**: 
- Add more training data
- Try different kernel functions
- Adjust C parameter

**Issue**: Overfitting
**Solution**: 
- Reduce C value
- Increase training data
- Use cross-validation

## Reference

- **File**: `python_ml_service/svm_gift_classifier.py`
- **Algorithm**: Support Vector Machine (SVM)
- **Library**: scikit-learn
- **Kernel**: RBF (Radial Basis Function)




















