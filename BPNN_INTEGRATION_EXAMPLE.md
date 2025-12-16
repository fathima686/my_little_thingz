# BPNN Integration Example

## Quick Start Integration

Here's how to integrate the BPNN recommendation system into your existing "My Little Thingz" project:

### 1. Database Setup

```bash
# Run the migration script
cd backend
php run_bpnn_migration.php

# Train the initial model
php train_bpnn_model.php
```

### 2. Frontend Integration

Add the BPNN recommendations to your existing pages:

#### In your main product gallery page:

```jsx
// Add to your existing ArtworkGallery.jsx
import BPNNRecommendations from './components/customer/BPNNRecommendations';
import './styles/bpnn.css';

// Add this after your existing KNN recommendations
<BPNNRecommendations
  userId={auth?.user_id}
  title="Recommended for You"
  limit={6}
  showConfidence={true}
  onCustomizationRequest={handleCustomizationRequest}
/>
```

#### In your product detail page:

```jsx
// Add to your product detail component
<BPNNRecommendations
  userId={auth?.user_id}
  title="You Might Also Like"
  limit={4}
  showConfidence={false}
  showAddToCart={true}
  showWishlist={true}
/>
```

### 3. Behavior Tracking Integration

Add behavior tracking to your existing components:

#### Track views when products are displayed:

```jsx
// In your product card component
useEffect(() => {
  if (auth?.user_id && artwork.id) {
    // Track view behavior
    fetch('/backend/api/customer/track_behavior.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        user_id: auth.user_id,
        artwork_id: artwork.id,
        behavior_type: 'view'
      })
    }).catch(console.error);
  }
}, [auth?.user_id, artwork.id]);
```

#### Track cart additions:

```jsx
// In your existing add to cart function
const handleAddToCart = async (artwork) => {
  // Your existing cart logic...
  
  // Track behavior
  if (auth?.user_id) {
    fetch('/backend/api/customer/track_behavior.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        user_id: auth.user_id,
        artwork_id: artwork.id,
        behavior_type: 'add_to_cart'
      })
    }).catch(console.error);
  }
};
```

#### Track purchases:

```jsx
// In your checkout success handler
const handlePurchaseSuccess = (orderData) => {
  // Your existing success logic...
  
  // Track purchase behavior for each item
  if (auth?.user_id && orderData.items) {
    orderData.items.forEach(item => {
      fetch('/backend/api/customer/track_behavior.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          user_id: auth.user_id,
          artwork_id: item.artwork_id,
          behavior_type: 'purchase'
        })
      }).catch(console.error);
    });
  }
};
```

### 4. Admin Dashboard Integration

Add the training dashboard to your admin panel:

```jsx
// In your admin dashboard
import BPNNTrainingDashboard from './components/admin/BPNNTrainingDashboard';

// Add a new tab or page
<Tab label="AI Training">
  <BPNNTrainingDashboard />
</Tab>
```

### 5. API Integration

Use the BPNN recommendations API in your existing recommendation system:

```javascript
// Create a hybrid recommendation function
const getRecommendations = async (userId, artworkId = null) => {
  const recommendations = [];
  
  // Get BPNN recommendations (user-based)
  if (userId) {
    try {
      const bpnnResponse = await fetch(
        `/backend/api/customer/bpnn_recommendations.php?user_id=${userId}&limit=4`
      );
      const bpnnData = await bpnnResponse.json();
      if (bpnnData.status === 'success') {
        recommendations.push(...bpnnData.recommendations);
      }
    } catch (error) {
      console.error('BPNN recommendations failed:', error);
    }
  }
  
  // Get KNN recommendations (product-based)
  if (artworkId) {
    try {
      const knnResponse = await fetch(
        `/backend/api/customer/recommendations.php?artwork_id=${artworkId}&limit=4`
      );
      const knnData = await knnResponse.json();
      if (knnData.status === 'success') {
        recommendations.push(...knnData.recommendations);
      }
    } catch (error) {
      console.error('KNN recommendations failed:', error);
    }
  }
  
  // Remove duplicates and return
  const uniqueRecommendations = recommendations.filter((item, index, self) => 
    index === self.findIndex(t => t.artwork_id === item.artwork_id)
  );
  
  return uniqueRecommendations.slice(0, 8);
};
```

### 6. Styling Integration

The BPNN components use their own CSS classes that won't conflict with your existing styles. The main classes are:

- `.bpnn-dashboard` - Admin dashboard
- `.recommendations-container` - Recommendation component
- `.recommendation-item` - Individual recommendation cards

### 7. Performance Optimization

Enable caching for better performance:

```php
// In your BPNN recommendations API
$useCache = $_GET['use_cache'] ?? true;
$minConfidence = $_GET['min_confidence'] ?? 0.3;
```

### 8. Monitoring and Maintenance

Set up regular model retraining:

```php
// Create a cron job for weekly retraining
// 0 2 * * 0 php /path/to/backend/retrain_model.php
```

### 9. Testing the Integration

Test the complete system:

1. **User Registration**: Register a new user
2. **Browse Products**: View several products to generate behavior data
3. **Add to Cart**: Add items to cart and wishlist
4. **Make Purchase**: Complete a purchase
5. **Check Recommendations**: Verify AI recommendations appear
6. **Admin Dashboard**: Check model performance in admin panel

### 10. Troubleshooting

If recommendations don't appear:

1. Check if the model is trained: `GET /backend/api/admin/bpnn_training.php?action=status`
2. Verify behavior tracking: Check `user_behavior` table
3. Test the API directly: `GET /backend/api/customer/bpnn_recommendations.php?user_id=1`
4. Check error logs in `backend/logs/`

## Example Complete Integration

Here's a complete example of integrating BPNN into your existing product page:

```jsx
import React, { useState, useEffect } from 'react';
import BPNNRecommendations from './components/customer/BPNNRecommendations';
import './styles/bpnn.css';

const ProductPage = ({ artwork, auth }) => {
  const [recommendations, setRecommendations] = useState([]);
  
  // Track view behavior
  useEffect(() => {
    if (auth?.user_id && artwork?.id) {
      fetch('/backend/api/customer/track_behavior.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          user_id: auth.user_id,
          artwork_id: artwork.id,
          behavior_type: 'view'
        })
      }).catch(console.error);
    }
  }, [auth?.user_id, artwork?.id]);
  
  return (
    <div className="product-page">
      {/* Your existing product details */}
      <div className="product-details">
        <h1>{artwork.title}</h1>
        <p>{artwork.description}</p>
        <p>Price: ₹{artwork.price}</p>
        {/* Your existing product actions */}
      </div>
      
      {/* BPNN AI Recommendations */}
      <BPNNRecommendations
        userId={auth?.user_id}
        title="Recommended for You"
        limit={6}
        showConfidence={true}
        onCustomizationRequest={(artwork) => {
          // Handle customization request
          console.log('Customize:', artwork);
        }}
      />
    </div>
  );
};

export default ProductPage;
```

This integration provides a seamless experience where users get both traditional product recommendations and AI-powered personalized suggestions based on their behavior patterns.
















