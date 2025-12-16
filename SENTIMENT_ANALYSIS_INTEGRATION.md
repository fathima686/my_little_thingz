# Sentiment Analysis Integration in Admin Dashboard

## Overview
Sentiment analysis is now integrated into your admin dashboard reviews page. Each customer review is automatically analyzed and displays its sentiment (Positive, Negative, or Neutral) with confidence scores.

## What You'll See in Admin Dashboard

### Review Display
Each review now shows:
- **Sentiment Badge**: Color-coded indicator showing the sentiment
  - ✓ POSITIVE (Green) - Good reviews
  - ✗ NEGATIVE (Red) - Reviews with complaints
  - ○ NEUTRAL (Yellow) - Neutral feedback
- **Confidence Score**: Percentage showing how confident the model is
- **Recommended Action**: Suggested action based on sentiment

### Example Display
```
Review #6 • wedding card
Rating: 5/5 • 10/27/2025

Customer Review:
"good product"
✓ POSITIVE (52.8%)
```

## Setup Instructions

### Step 1: Install Python Dependencies
```bash
cd python_ml_service
pip install flask flask-cors scikit-learn numpy
```

### Step 2: Start the Python ML Service
You need to run the Flask API service for sentiment analysis:

**Option A: Using the Batch Script (Windows)**
```bash
cd python_ml_service
start_sentiment_service.bat
```

**Option B: Manual Start**
```bash
cd python_ml_service
python app.py
```

The service will start on `http://localhost:5001`

### Step 3: Access the Admin Dashboard
1. Open your admin dashboard
2. Go to "Customer Reviews" section
3. Each review will now show its sentiment automatically

## How It Works

1. **Load Reviews**: When you load the reviews page, the system fetches all reviews
2. **Analyze Sentiment**: Each review with a comment is sent to the Python ML service
3. **Display Results**: Sentiment badges appear below each review
4. **Color Coding**:
   - Green = Positive sentiment (approve recommended)
   - Red = Negative sentiment (investigate)
   - Yellow = Neutral sentiment (review manually)

## API Endpoint

The sentiment analysis API is available at:
```
POST http://localhost:5001/api/ml/sentiment/analyze
```

**Request:**
```json
{
  "review_text": "good product"
}
```

**Response:**
```json
{
  "success": true,
  "sentiment": "positive",
  "confidence": 0.528,
  "confidence_percent": 52.8,
  "recommended_action": "approve",
  "probabilities": {
    "positive": 0.528,
    "neutral": 0.189,
    "negative": 0.283
  }
}
```

## Troubleshooting

### Issue: No sentiment badges showing
**Solution**: Make sure the Flask service is running on port 5001
```bash
cd python_ml_service
python app.py
```

### Issue: Connection refused
**Solution**: Check if the service is running
```bash
curl http://localhost:5001/api/ml/health
```
Should return: `{"status": "healthy"}`

### Issue: Port 5001 already in use
**Solution**: Change the port in `app.py` line 500:
```python
app.run(host='0.0.0.0', port=5002, debug=True)
```
Then update the API URL in `AdminReviews.jsx` line 37:
```javascript
const res = await fetch('http://localhost:5002/api/ml/sentiment/analyze', {
```

### Issue: Slow loading
**Solution**: The first time loads are slower as the model trains. Subsequent loads will be faster.

## Benefits

✅ **Automatic Classification**: Instantly see if reviews are positive or negative
✅ **Priority Management**: Negative reviews are highlighted for immediate attention
✅ **Confidence Scores**: Know how reliable each classification is
✅ **Time Saving**: No need to manually read every review
✅ **Consistent Analysis**: Same standard for all reviews

## File Locations

- **Frontend**: `frontend/src/pages/AdminReviews.jsx`
- **Backend API**: `python_ml_service/app.py`
- **Sentiment Model**: `python_ml_service/gift_review_sentiment_analysis.py`
- **Test Script**: `python_ml_service/test_sentiment_api.py`

## Testing

Test the sentiment analysis:
```bash
cd python_ml_service
python test_sentiment_api.py
```

Or manually:
```bash
python analyze_dashboard_reviews.py
```

## Next Steps

1. **Customize Training Data**: Add your actual reviews to improve accuracy
2. **Add More Features**: Filter reviews by sentiment
3. **Automated Actions**: Auto-approve positive reviews, auto-flag negative ones
4. **Analytics**: Track sentiment trends over time
5. **Email Alerts**: Get notified of negative reviews




















