# Purchase History-Based Recommendations Integration

## Overview

This enhanced BPNN system now includes sophisticated purchase history analysis that provides intelligent recommendations based on your customers' buying patterns. For example, if someone bought wedding cards, they'll be recommended wedding hampers and related items.

## Key Features

### 🎯 **Smart Category Progression**
- **Wedding Cards** → **Wedding Hampers, Bouquets**
- **Frames** → **Albums**
- **Chocolates** → **Gift Boxes**
- **Bouquets** → **Gift Boxes, Chocolates**

### 🎉 **Occasion Detection**
- Automatically detects occasions from purchase patterns
- **Wedding**: Wedding cards, gift boxes, bouquets
- **Birthday**: Gift boxes, chocolates, frames
- **Anniversary**: Bouquets, chocolates, gift boxes
- **Valentine's**: Bouquets, chocolates
- **Christmas**: Gift boxes, chocolates, frames

### 📊 **Advanced Analytics**
- Purchase frequency analysis
- Price preference patterns
- Seasonal buying trends
- Category affinity scoring

## Integration Examples

### 1. Basic Purchase History Recommendations

```jsx
import PurchaseHistoryRecommendations from './components/customer/PurchaseHistoryRecommendations';

// In your product page or dashboard
<PurchaseHistoryRecommendations
  userId={auth?.user_id}
  title="Recommended Based on Your Purchases"
  limit={6}
  showAnalysis={true}
  onCustomizationRequest={handleCustomization}
/>
```

### 2. Combined Recommendation System

```jsx
import BPNNRecommendations from './components/customer/BPNNRecommendations';
import PurchaseHistoryRecommendations from './components/customer/PurchaseHistoryRecommendations';

const ProductPage = ({ artwork, auth }) => {
  return (
    <div className="product-page">
      {/* Product details */}
      <div className="product-details">
        <h1>{artwork.title}</h1>
        <p>{artwork.description}</p>
        <p>Price: ₹{artwork.price}</p>
      </div>
      
      {/* AI-powered recommendations (neural network) */}
      <BPNNRecommendations
        userId={auth?.user_id}
        title="AI-Powered Recommendations"
        limit={4}
        showConfidence={true}
      />
      
      {/* Purchase history-based recommendations */}
      <PurchaseHistoryRecommendations
        userId={auth?.user_id}
        title="Based on Your Purchase History"
        limit={4}
        showAnalysis={true}
      />
      
      {/* Traditional KNN recommendations (existing) */}
      <Recommendations
        artworkId={artwork.id}
        title="Similar Products"
        limit={4}
      />
    </div>
  );
};
```

### 3. Dashboard Integration

```jsx
const CustomerDashboard = ({ auth }) => {
  return (
    <div className="dashboard">
      <h1>Welcome back, {auth.user.first_name}!</h1>
      
      {/* Purchase history analysis */}
      <PurchaseHistoryRecommendations
        userId={auth.user_id}
        title="Recommended for You"
        limit={8}
        showAnalysis={true}
        showAddToCart={true}
        showWishlist={true}
      />
      
      {/* AI recommendations */}
      <BPNNRecommendations
        userId={auth.user_id}
        title="AI Suggestions"
        limit={6}
        showConfidence={true}
      />
    </div>
  );
};
```

## API Usage

### Get Purchase History Recommendations

```javascript
// Basic recommendations
const response = await fetch(
  `/backend/api/customer/purchase_history_recommendations.php?user_id=${userId}&limit=8`
);
const data = await response.json();

// With analysis
const response = await fetch(
  `/backend/api/customer/purchase_history_recommendations.php?user_id=${userId}&limit=8&analysis=true`
);
const data = await response.json();

console.log('Recommendations:', data.recommendations);
console.log('Analysis:', data.analysis);
```

### Response Format

```json
{
  "status": "success",
  "recommendations": [
    {
      "artwork_id": 123,
      "title": "Wedding Hamper",
      "description": "Beautiful wedding gift set",
      "price": 2000,
      "effective_price": 1800,
      "image_url": "...",
      "category_id": 1,
      "category_name": "Gift box",
      "score": 0.85,
      "reason": "Based on your purchase of Wedding card",
      "has_offer": true,
      "offer_price": 1800
    }
  ],
  "analysis": {
    "total_purchases": 5,
    "categories_purchased": ["Wedding card", "Frames"],
    "occasions_detected": ["wedding", "birthday"],
    "price_range": "medium",
    "most_active_season": "spring"
  }
}
```

## Configuration

### Category Progression Rules

You can customize the progression rules in `PurchaseHistoryAnalyzer.php`:

```php
$progressionRules = [
    // If bought wedding cards, suggest wedding hampers
    6 => [1, 2], // Wedding cards -> Gift boxes, Bouquets
    
    // If bought frames, suggest albums
    3 => [8], // Frames -> Albums
    
    // If bought chocolates, suggest gift boxes
    5 => [1], // Chocolates -> Gift boxes
    
    // Add your own rules here
    4 => [3, 8], // Polaroids -> Frames, Albums
];
```

### Occasion Detection Patterns

Customize occasion detection in the same file:

```php
$occasionPatterns = [
    'wedding' => [
        'keywords' => ['wedding', 'bride', 'groom', 'marriage'],
        'categories' => [6, 1, 2], // Wedding cards, Gift boxes, Bouquets
        'time_patterns' => ['spring', 'summer']
    ],
    'birthday' => [
        'keywords' => ['birthday', 'party', 'celebration'],
        'categories' => [1, 5, 3], // Gift boxes, Chocolates, Frames
        'time_patterns' => ['any']
    ],
    // Add more occasions
];
```

## Real-World Examples

### Example 1: Wedding Customer Journey

1. **Customer buys**: Wedding cards (₹50 each)
2. **System detects**: Wedding occasion
3. **Recommends**: 
   - Wedding hampers (₹2000)
   - Wedding bouquets (₹500)
   - Gift boxes (₹1000)
4. **Reason**: "Based on your purchase of Wedding card"

### Example 2: Birthday Customer Journey

1. **Customer buys**: Frames (₹150 each)
2. **System detects**: Birthday occasion
3. **Recommends**:
   - Albums (₹200)
   - Gift boxes (₹800)
   - Chocolates (₹300)
4. **Reason**: "Perfect birthday gift ideas"

### Example 3: Seasonal Patterns

1. **Customer buys**: Christmas-themed items in December
2. **System detects**: Christmas occasion
3. **Recommends**: Holiday gift collection
4. **Reason**: "Holiday gift collection"

## Performance Optimization

### Caching Strategy

```php
// Cache purchase analysis for 1 hour
$cacheKey = "purchase_analysis_{$userId}";
$analysis = $cache->get($cacheKey);

if (!$analysis) {
    $analysis = $purchaseAnalyzer->analyzePurchaseHistory($userId);
    $cache->set($cacheKey, $analysis, 3600); // 1 hour
}
```

### Database Optimization

```sql
-- Add indexes for better performance
CREATE INDEX idx_orders_user_status ON orders(user_id, status, payment_status);
CREATE INDEX idx_order_items_artwork ON order_items(artwork_id);
CREATE INDEX idx_artworks_category_status ON artworks(category_id, status);
```

## Monitoring and Analytics

### Track Recommendation Performance

```javascript
// Track recommendation clicks
const trackRecommendationClick = (artworkId, recommendationType) => {
  fetch('/backend/api/customer/track_behavior.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      user_id: auth.user_id,
      artwork_id: artworkId,
      behavior_type: 'view',
      additional_data: {
        recommendation_type: recommendationType, // 'purchase_history' or 'ai'
        source: 'recommendation_click'
      }
    })
  });
};
```

### Admin Analytics Dashboard

```jsx
const AdminAnalytics = () => {
  const [analytics, setAnalytics] = useState(null);
  
  useEffect(() => {
    fetchAnalytics();
  }, []);
  
  const fetchAnalytics = async () => {
    const response = await fetch('/backend/api/admin/recommendation_analytics.php');
    const data = await response.json();
    setAnalytics(data);
  };
  
  return (
    <div className="analytics-dashboard">
      <h2>Recommendation Performance</h2>
      
      <div className="metrics-grid">
        <div className="metric-card">
          <h3>Purchase History Recommendations</h3>
          <p>Click Rate: {analytics?.purchase_history_click_rate}%</p>
          <p>Conversion Rate: {analytics?.purchase_history_conversion_rate}%</p>
        </div>
        
        <div className="metric-card">
          <h3>AI Recommendations</h3>
          <p>Click Rate: {analytics?.ai_click_rate}%</p>
          <p>Conversion Rate: {analytics?.ai_conversion_rate}%</p>
        </div>
      </div>
    </div>
  );
};
```

## Troubleshooting

### Common Issues

1. **No recommendations appearing**
   - Check if user has purchase history
   - Verify database tables are created
   - Check API endpoint logs

2. **Low recommendation quality**
   - Ensure sufficient purchase data
   - Adjust category progression rules
   - Check occasion detection patterns

3. **Performance issues**
   - Enable caching
   - Add database indexes
   - Limit recommendation queries

### Debug Mode

```javascript
// Enable debug mode
const response = await fetch(
  `/backend/api/customer/purchase_history_recommendations.php?user_id=${userId}&debug=1`
);
const data = await response.json();
console.log('Debug info:', data.debug);
```

## Future Enhancements

1. **Machine Learning Integration**: Use the purchase history data to train better BPNN models
2. **Real-time Updates**: Update recommendations as new purchases are made
3. **A/B Testing**: Compare different recommendation strategies
4. **Personalization**: More sophisticated personalization based on user behavior
5. **Cross-selling**: Recommend complementary products from different categories

This enhanced system provides a comprehensive recommendation solution that learns from customer behavior and provides intelligent, contextual suggestions that increase engagement and sales.








