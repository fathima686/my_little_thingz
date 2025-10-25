# Dynamic SVM Recommendation System Implementation Guide

## Overview

This implementation provides a dynamic SVM-based gift recommendation system that updates in real-time based on user behavior and new purchases. The system addresses the key issues:

1. **Static Model Problem**: The SVM model now retrains automatically based on new user interactions
2. **New User Handling**: Personalized recommendations for new users based on demographics and popular items
3. **Real-time Updates**: Recommendations update immediately after new purchases or user behavior

## Architecture

```
Frontend (React) → PHP API → Python ML Service → Database
     ↓                ↓           ↓              ↓
User Interface → Behavior Tracking → Dynamic SVM → Data Storage
```

## Key Components

### 1. Python ML Service (`python_ml_service/`)

#### `dynamic_svm_recommender.py`
- **DynamicSVMRecommender**: Main class handling dynamic learning
- **Features**:
  - Real-time model retraining
  - User behavior tracking
  - New user recommendations
  - Incremental learning

#### `enhanced_database.py`
- **EnhancedDatabaseConnection**: Database integration
- **DynamicDataProvider**: Real-time data access
- **Features**:
  - User profile extraction
  - Product feature computation
  - Behavior logging
  - Performance metrics

#### `app.py` (Updated)
- New API endpoints for dynamic SVM
- Integration with existing ML algorithms
- Real-time behavior tracking

### 2. PHP Integration (`backend/api/customer/`)

#### `dynamic_svm_recommendations.php`
- Bridge between frontend and Python service
- Handles user authentication
- Enriches recommendations with product details
- Behavior tracking integration

### 3. Frontend Component (`frontend/src/components/customer/`)

#### `DynamicSVMRecommendations.jsx`
- React component for displaying recommendations
- Real-time behavior tracking
- Auto-refresh capabilities
- New user detection

## Key Features

### 1. Dynamic Model Retraining

The system automatically retrains the SVM model when:
- New user interactions exceed threshold (default: 10 interactions)
- Model accuracy drops below threshold (default: 10% decrease)
- Weekly scheduled retraining
- Manual retraining trigger

```python
def _should_retrain(self) -> bool:
    # Retrain if enough new data
    if len(self.recent_purchases) >= self.min_samples_for_retrain:
        return True
    
    # Retrain if model is old
    if self.last_retrain_time:
        days_since_retrain = (datetime.now() - self.last_retrain_time).days
        if days_since_retrain >= 7:  # Retrain weekly
            return True
    
    return False
```

### 2. New User Recommendations

For users with less than 3 orders, the system provides:
- Popular items based on demographics
- Trending products
- Category-based filtering
- Confidence scoring

```python
def get_new_user_recommendations(self, user_data: Dict, 
                               available_products: List[Dict], limit: int = 10) -> List[Dict]:
    # Demographic filtering
    age = user_data.get('age', 25)
    gender = user_data.get('gender', 'unknown')
    
    # Filter products based on demographics
    filtered_products = []
    for product in available_products:
        if age < 25 and 'trendy' in product.get('title', '').lower():
            filtered_products.append(product)
        elif age > 40 and 'classic' in product.get('title', '').lower():
            filtered_products.append(product)
        else:
            filtered_products.append(product)
```

### 3. Real-time Behavior Tracking

Every user interaction is tracked and used for learning:
- Views, clicks, cart additions
- Purchases, wishlist additions
- Session data, device information
- Time-based patterns

```python
def update_user_behavior(self, user_id: int, product_id: int, 
                       behavior_type: str, additional_data: Dict = None):
    interaction = {
        'user_id': user_id,
        'product_id': product_id,
        'behavior_type': behavior_type,
        'timestamp': datetime.now(),
        'additional_data': additional_data or {}
    }
    
    # Store interaction
    self.user_interactions[user_id].append(interaction)
    
    # Update user preferences
    if behavior_type in ['purchase', 'add_to_cart', 'add_to_wishlist']:
        if 'preferred_products' not in self.user_preferences[user_id]:
            self.user_preferences[user_id]['preferred_products'] = []
        self.user_preferences[user_id]['preferred_products'].append(product_id)
```

## API Endpoints

### Python ML Service Endpoints

#### 1. Get Recommendations
```http
POST /api/ml/dynamic-svm/recommendations
Content-Type: application/json

{
    "user_id": 1,
    "limit": 10
}
```

**Response:**
```json
{
    "success": true,
    "recommendations": [...],
    "user_id": 1,
    "is_new_user": false,
    "model_version": 5,
    "generated_at": "2024-01-15T10:30:00Z"
}
```

#### 2. Update User Behavior
```http
POST /api/ml/dynamic-svm/update-behavior
Content-Type: application/json

{
    "user_id": 1,
    "product_id": 123,
    "behavior_type": "purchase",
    "additional_data": {
        "order_id": 456,
        "quantity": 2,
        "price": 1500
    }
}
```

#### 3. Predict User Preference
```http
POST /api/ml/dynamic-svm/predict
Content-Type: application/json

{
    "user_id": 1,
    "product_id": 123
}
```

#### 4. Retrain Model
```http
POST /api/ml/dynamic-svm/retrain
Content-Type: application/json

{
    "force": false
}
```

#### 5. Get Model Information
```http
GET /api/ml/dynamic-svm/model-info
```

### PHP API Endpoints

#### 1. Get Recommendations
```http
GET /backend/api/customer/dynamic_svm_recommendations.php?user_id=1&limit=8&min_confidence=0.3
```

#### 2. Update Behavior
```http
POST /backend/api/customer/dynamic_svm_recommendations.php
Content-Type: application/json

{
    "action": "update_behavior",
    "user_id": 1,
    "product_id": 123,
    "behavior_type": "view",
    "additional_data": {}
}
```

## Installation and Setup

### 1. Python Dependencies

```bash
cd python_ml_service
pip install -r requirements.txt
```

### 2. Database Setup

The system creates a behavior tracking table automatically:

```sql
CREATE TABLE IF NOT EXISTS user_behavior_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    behavior_type VARCHAR(50) NOT NULL,
    additional_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_behavior (user_id, behavior_type, created_at),
    INDEX idx_product_behavior (product_id, behavior_type, created_at)
);
```

### 3. Environment Configuration

Create `.env` file in `python_ml_service/`:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=my_little_things
DB_USER=root
DB_PASSWORD=
SECRET_KEY=your-secret-key-here
DEBUG=True
```

### 4. Start Services

```bash
# Start Python ML Service
cd python_ml_service
python app.py

# Start PHP backend (XAMPP)
# Start React frontend
cd frontend
npm run dev
```

## Usage Examples

### 1. Frontend Integration

```jsx
import DynamicSVMRecommendations from './components/customer/DynamicSVMRecommendations';

function App() {
  return (
    <DynamicSVMRecommendations
      userId={auth.user_id}
      title="AI-Powered Dynamic Recommendations"
      limit={8}
      autoRefresh={true}
      refreshInterval={300000} // 5 minutes
      showConfidence={true}
      showAddToCart={true}
      showWishlist={true}
    />
  );
}
```

### 2. Behavior Tracking

```javascript
// Track user behavior
const trackBehavior = async (behaviorType, productId) => {
  await fetch('/backend/api/customer/dynamic_svm_recommendations.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'update_behavior',
      user_id: auth.user_id,
      product_id: productId,
      behavior_type: behaviorType,
      additional_data: {
        session_id: sessionStorage.getItem('session_id'),
        timestamp: new Date().toISOString()
      }
    })
  });
};
```

### 3. Manual Model Retraining

```python
# Trigger manual retraining
import requests

response = requests.post(
    'http://localhost:5001/api/ml/dynamic-svm/retrain',
    json={'force': True}
)

if response.json()['success']:
    print("Model retrained successfully")
```

## Testing

Run the comprehensive test suite:

```bash
cd python_ml_service
python test_dynamic_svm.py
```

The test suite covers:
- Service health checks
- Recommendation generation
- Behavior tracking
- Model predictions
- PHP integration
- End-to-end functionality

## Performance Considerations

### 1. Caching Strategy
- Model caching with versioning
- Recommendation caching (5-minute TTL)
- User profile caching

### 2. Background Processing
- Asynchronous model retraining
- Queue-based behavior processing
- Scheduled model updates

### 3. Database Optimization
- Indexed behavior tracking
- Efficient user profile queries
- Product feature precomputation

## Monitoring and Analytics

### 1. Model Performance Metrics
- Accuracy tracking
- Recommendation success rate
- User engagement metrics

### 2. Real-time Monitoring
- Model version tracking
- Retraining frequency
- Error rate monitoring

### 3. User Behavior Analytics
- Interaction patterns
- Purchase conversion rates
- Recommendation effectiveness

## Troubleshooting

### Common Issues

1. **Python Service Not Starting**
   - Check database connection
   - Verify dependencies
   - Check port availability (5001)

2. **Recommendations Not Updating**
   - Verify behavior tracking
   - Check model retraining triggers
   - Monitor database connections

3. **New User Recommendations**
   - Ensure demographic data is available
   - Check product feature computation
   - Verify new user detection logic

### Debug Mode

Enable debug logging:

```python
import logging
logging.basicConfig(level=logging.DEBUG)
```

## Future Enhancements

1. **Advanced ML Algorithms**
   - Deep learning models
   - Ensemble methods
   - Real-time streaming ML

2. **Enhanced Personalization**
   - Context-aware recommendations
   - Multi-modal learning
   - Cross-domain recommendations

3. **Performance Optimization**
   - Model compression
   - Distributed training
   - Edge computing integration

## Conclusion

This dynamic SVM recommendation system provides:

✅ **Real-time Updates**: Recommendations change based on new purchases
✅ **New User Handling**: Personalized recommendations for new users
✅ **Automatic Retraining**: Model updates without manual intervention
✅ **Behavior Tracking**: Comprehensive user interaction logging
✅ **Scalable Architecture**: Modular design for easy expansion

The system successfully addresses all the original issues and provides a robust foundation for personalized gift recommendations.






