# BPNN Neural Network Recommendation System

## Overview

This implementation adds a sophisticated Backpropagation Neural Network (BPNN) recommendation system to "My Little Thingz" that predicts customer gift preferences based on their past purchases, views, ratings, and favorites. The system uses machine learning to provide personalized recommendations that enhance user engagement and improve conversion rates.

## Features

- **AI-Powered Recommendations**: Uses neural networks to predict customer preferences
- **Real-time Behavior Tracking**: Tracks user interactions (views, cart additions, purchases, ratings)
- **Feature Engineering**: Extracts meaningful features from user behavior and product data
- **Model Training & Retraining**: Automated training pipeline with performance monitoring
- **Caching System**: Efficient prediction caching for better performance
- **Admin Dashboard**: Complete management interface for model training and monitoring
- **Seamless Integration**: Works alongside existing KNN recommendation system

## Architecture

### Backend Components

1. **BPNNNeuralNetwork.php** - Core neural network implementation
2. **BPNNDataProcessor.php** - Data preprocessing and feature extraction
3. **BPNNTrainer.php** - Model training and management
4. **UserBehaviorTracker.php** - User interaction tracking
5. **API Endpoints** - RESTful APIs for recommendations and training

### Frontend Components

1. **BPNNRecommendations.jsx** - AI-powered recommendation component
2. **BPNNTrainingDashboard.jsx** - Admin training dashboard
3. **CSS Styles** - Complete styling for BPNN components

### Database Schema

- `user_behavior` - Tracks user interactions
- `bpnn_models` - Stores trained neural network models
- `bpnn_training_data` - Training dataset
- `bpnn_predictions` - Cached predictions
- `user_preference_profiles` - User preference profiles

## Installation & Setup

### 1. Database Setup

Run the migration script to create necessary tables:

```bash
cd backend
php run_bpnn_migration.php
```

### 2. Initial Model Training

Train the first model with existing data:

```bash
php train_bpnn_model.php
```

### 3. Frontend Integration

Import and use the BPNN components in your React application:

```jsx
import BPNNRecommendations from './components/customer/BPNNRecommendations';
import BPNNTrainingDashboard from './components/admin/BPNNTrainingDashboard';
import './styles/bpnn.css';
```

## Usage

### Customer Recommendations

```jsx
<BPNNRecommendations
  userId={auth.user_id}
  title="AI-Powered Recommendations"
  limit={8}
  showConfidence={true}
  onCustomizationRequest={handleCustomization}
/>
```

### Admin Training Dashboard

```jsx
<BPNNTrainingDashboard />
```

### Behavior Tracking

Track user interactions automatically:

```javascript
// Track a view
fetch('/api/customer/track_behavior.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    user_id: userId,
    artwork_id: artworkId,
    behavior_type: 'view'
  })
});

// Track a purchase
fetch('/api/customer/track_behavior.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    user_id: userId,
    artwork_id: artworkId,
    behavior_type: 'purchase'
  })
});
```

## API Endpoints

### Customer APIs

- `GET /api/customer/bpnn_recommendations.php` - Get AI recommendations
- `POST /api/customer/track_behavior.php` - Track user behavior

### Admin APIs

- `GET /api/admin/bpnn_training.php?action=status` - Get model status
- `POST /api/admin/bpnn_training.php?action=train` - Train new model
- `POST /api/admin/bpnn_training.php?action=retrain` - Retrain existing model
- `GET /api/admin/bpnn_training.php?action=test` - Test model performance

## Configuration

### Training Parameters

```php
$config = [
    'hidden_layers' => [8, 6],        // Neural network architecture
    'learning_rate' => 0.01,          // Learning rate
    'epochs' => 1000,                 // Training epochs
    'validation_split' => 0.2,        // Validation data percentage
    'training_data_limit' => 2000,    // Maximum training samples
    'activation_function' => 'sigmoid' // Activation function
];
```

### Feature Engineering

The system extracts 10 key features:

1. Category preference score
2. Price preference score
3. User activity level
4. Artwork popularity score
5. Normalized price
6. Category affinity
7. Seasonal preference
8. Similar item engagement
9. Offer preference
10. Trending preference

## Model Performance

### Metrics Tracked

- **Training Accuracy**: Model performance on training data
- **Validation Accuracy**: Model performance on validation data
- **Training Loss**: Mean squared error on training data
- **Validation Loss**: Mean squared error on validation data
- **Test Accuracy**: Real-world performance testing

### Performance Monitoring

The system automatically monitors model performance and can trigger retraining when accuracy drops below thresholds.

## Caching Strategy

- **Prediction Cache**: Caches predictions for 1 hour
- **User Profile Cache**: Updates user profiles on behavior changes
- **Model Cache**: Loads active model into memory for fast predictions

## Integration with Existing System

The BPNN system is designed to work alongside your existing KNN recommendation system:

- **KNN**: Product-to-product recommendations (when clicking on a product)
- **BPNN**: User preference predictions (personalized recommendations)

Both systems can be used together for comprehensive recommendation coverage.

## Maintenance

### Regular Tasks

1. **Monitor Performance**: Check model accuracy weekly
2. **Retrain Models**: Retrain when accuracy drops below 70%
3. **Clean Data**: Remove old behavior data (configurable)
4. **Update Features**: Enhance feature engineering based on new data

### Automated Retraining

The system can be configured for automatic retraining:

```php
// Retrain if validation accuracy drops below 70%
if ($currentAccuracy < 0.7) {
    $trainer->retrainModel($config);
}
```

## Troubleshooting

### Common Issues

1. **No Recommendations**: Check if model is trained and active
2. **Low Accuracy**: Increase training data or adjust parameters
3. **Slow Performance**: Enable prediction caching
4. **Memory Issues**: Reduce training data limit

### Debug Mode

Enable debug mode in API responses:

```php
// Add to API endpoints
if ($_GET['debug'] === '1') {
    $response['debug'] = $debugInfo;
}
```

## Performance Optimization

### Database Optimization

- Indexes on frequently queried columns
- Partitioning for large behavior tables
- Regular cleanup of old data

### Caching Optimization

- Redis integration for prediction cache
- CDN for static assets
- Database query optimization

## Security Considerations

- Input validation for all API endpoints
- SQL injection prevention
- Rate limiting for training endpoints
- Admin authentication required

## Future Enhancements

1. **Deep Learning**: Upgrade to deep neural networks
2. **Real-time Learning**: Online learning capabilities
3. **A/B Testing**: Compare recommendation algorithms
4. **Multi-objective Optimization**: Optimize for multiple metrics
5. **Federated Learning**: Privacy-preserving training

## Support

For issues or questions:

1. Check the logs in `backend/logs/`
2. Review the admin dashboard for model status
3. Test API endpoints manually
4. Verify database schema and data

## License

This BPNN implementation is part of the "My Little Thingz" project and follows the same licensing terms.

---

**Note**: This system requires PHP 7.4+ and MySQL 5.7+ for optimal performance. Ensure your server has sufficient memory for neural network training (recommended: 2GB+ RAM).






















