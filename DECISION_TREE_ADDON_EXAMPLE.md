# Decision Tree Add-on Recommendation System

## Overview

This system implements a sophisticated decision tree algorithm that suggests add-ons like greeting cards or ribbons based on gift price and other factors. The system follows the exact logic you requested:

- **if gift_price > 1000 → Include Greeting Card**
- **else → Optional Ribbon**

## Key Features

### 🎯 **Price-Based Decision Tree**
- **High Value (₹1000+)**: Premium greeting cards
- **Mid Range (₹500-1000)**: Decorative ribbons
- **Budget (₹500-)**: Basic packaging

### 🎉 **Category-Based Rules**
- **Wedding**: Premium greeting cards
- **Birthday**: Colorful ribbons
- **Anniversary**: Romantic greeting cards
- **Valentine's**: Heart ribbons
- **Christmas**: Festive greeting cards

### 📊 **Advanced Decision Logic**
- **Occasion Detection**: Formal vs casual occasions
- **Customer Preferences**: Premium vs budget-conscious
- **Seasonal Patterns**: Christmas, Valentine's, summer themes
- **Gift Type Analysis**: Hampers, bouquets, frames

## Integration Examples

### 1. Basic Add-on Recommendations

```jsx
import AddonRecommendations from './components/customer/AddonRecommendations';

// For a specific artwork
<AddonRecommendations
  artworkId={artwork.id}
  title="Recommended Add-ons"
  showDecisionPath={true}
  showConfidence={true}
  onAddonSelect={handleAddonSelect}
/>

// For price-based recommendations
<AddonRecommendations
  price={1500}
  category="Wedding card"
  occasion="wedding"
  title="Add-ons for Your Gift"
  onAddonSelect={handleAddonSelect}
/>
```

### 2. Product Page Integration

```jsx
const ProductPage = ({ artwork, auth }) => {
  const handleAddonSelect = (addon) => {
    // Add addon to cart or show customization options
    console.log('Selected addon:', addon);
    // You can integrate with your cart system here
  };

  return (
    <div className="product-page">
      {/* Product details */}
      <div className="product-details">
        <h1>{artwork.title}</h1>
        <p>Price: ₹{artwork.price}</p>
        <p>Category: {artwork.category_name}</p>
      </div>
      
      {/* Add-on recommendations */}
      <AddonRecommendations
        artworkId={artwork.id}
        userId={auth?.user_id}
        title="Complete Your Gift"
        showDecisionPath={true}
        showConfidence={true}
        onAddonSelect={handleAddonSelect}
      />
      
      {/* Other product recommendations */}
      <Recommendations artworkId={artwork.id} />
    </div>
  );
};
```

### 3. Cart Integration

```jsx
const CartPage = ({ cartItems, auth }) => {
  const handleAddonSelect = (addon, artworkId) => {
    // Add addon to the specific cart item
    addAddonToCartItem(artworkId, addon);
  };

  return (
    <div className="cart-page">
      {cartItems.map(item => (
        <div key={item.id} className="cart-item">
          <div className="item-details">
            <h3>{item.title}</h3>
            <p>₹{item.price}</p>
          </div>
          
          {/* Add-on recommendations for each item */}
          <AddonRecommendations
            artworkId={item.artwork_id}
            userId={auth?.user_id}
            title="Enhance This Gift"
            onAddonSelect={(addon) => handleAddonSelect(addon, item.artwork_id)}
          />
        </div>
      ))}
    </div>
  );
};
```

## API Usage

### Get Add-on Recommendations

```javascript
// For specific artwork
const response = await fetch(
  `/backend/api/customer/addon_recommendations.php?artwork_id=${artworkId}&user_id=${userId}`
);
const data = await response.json();

// For price-based recommendations
const response = await fetch(
  `/backend/api/customer/addon_recommendations.php?price=1500&category=Wedding card&occasion=wedding`
);
const data = await response.json();

console.log('Add-on recommendations:', data.addon_recommendations);
console.log('Decision path:', data.decision_path);
```

### Response Format

```json
{
  "status": "success",
  "addon_recommendations": [
    {
      "addon_type": "premium_greeting_card",
      "name": "Premium Greeting Card",
      "description": "High-quality greeting card with elegant design",
      "price": 50,
      "type": "card",
      "confidence": 0.95,
      "reason": "Wedding gifts require elegant greeting cards",
      "category": "category_based",
      "priority": 0.855
    },
    {
      "addon_type": "elegant_ribbon",
      "name": "Elegant Ribbon",
      "description": "Sophisticated ribbon for elegant gifts",
      "price": 35,
      "type": "ribbon",
      "confidence": 0.85,
      "reason": "High-value gifts benefit from premium presentation",
      "category": "price_based",
      "priority": 0.85
    }
  ],
  "overall_confidence": 0.9,
  "rule_count": 2,
  "decision_path": [
    "Price ₹1500 > ₹1000 → Include Greeting Card",
    "Category: Wedding card → Category-specific add-on"
  ]
}
```

## Decision Tree Rules

### Price-Based Rules
```php
// Main decision tree logic
if (gift_price > 1000) {
    recommend('premium_greeting_card', 'High-value gifts benefit from personal greeting cards');
} else if (gift_price <= 1000) {
    recommend('optional_ribbon', 'Mid-range gifts can be enhanced with decorative ribbons');
} else if (gift_price < 500) {
    recommend('basic_packaging', 'Budget-friendly gifts with simple packaging');
}
```

### Category-Based Rules
```php
switch (category) {
    case 'wedding':
        recommend('premium_greeting_card', 'Wedding gifts require elegant greeting cards');
        break;
    case 'birthday':
        recommend('colorful_ribbon', 'Birthday gifts look great with colorful ribbons');
        break;
    case 'anniversary':
        recommend('romantic_greeting_card', 'Anniversary gifts need romantic greeting cards');
        break;
    case 'valentine':
        recommend('heart_ribbon', 'Valentine gifts are perfect with heart-shaped ribbons');
        break;
    case 'christmas':
        recommend('festive_greeting_card', 'Christmas gifts require festive greeting cards');
        break;
}
```

## Real-World Examples

### Example 1: High-Value Wedding Gift
- **Gift**: Wedding hamper (₹2000)
- **Decision Path**: 
  1. Price ₹2000 > ₹1000 → Include Greeting Card
  2. Category: Wedding → Premium greeting card
- **Recommendations**:
  - Premium Greeting Card (₹50) - "Wedding gifts require elegant greeting cards"
  - Elegant Ribbon (₹35) - "High-value gifts benefit from premium presentation"

### Example 2: Mid-Range Birthday Gift
- **Gift**: Gift box (₹800)
- **Decision Path**:
  1. Price ₹800 ≤ ₹1000 → Optional Ribbon
  2. Category: Birthday → Colorful ribbon
- **Recommendations**:
  - Colorful Ribbon (₹20) - "Birthday gifts look great with colorful ribbons"
  - Basic Greeting Card (₹25) - "Mid-range gifts can include simple cards"

### Example 3: Budget Gift
- **Gift**: Frame (₹300)
- **Decision Path**:
  1. Price ₹300 < ₹500 → Basic packaging
  2. Category: Frame → Simple greeting card
- **Recommendations**:
  - Basic Packaging (₹10) - "Budget-friendly gifts with simple packaging"
  - Simple Greeting Card (₹20) - "Frames work well with simple, personal greeting cards"

## Customization Options

### Adding New Rules

```php
// Add custom rules in DecisionTreeAddonRecommender.php
'custom_rules' => [
    [
        'condition' => 'gift_price > 5000',
        'action' => 'luxury_greeting_card',
        'confidence' => 0.98,
        'reason' => 'Luxury gifts require premium greeting cards'
    ],
    [
        'condition' => 'category == "corporate"',
        'action' => 'professional_greeting_card',
        'confidence' => 0.9,
        'reason' => 'Corporate gifts need professional presentation'
    ]
]
```

### Custom Add-on Types

```php
// Add new add-on types
'luxury_greeting_card' => [
    'name' => 'Luxury Greeting Card',
    'description' => 'Premium greeting card with gold accents',
    'price' => 100,
    'type' => 'card',
    'image' => 'luxury_card.jpg'
]
```

## Performance Optimization

### Caching Strategy

```php
// Cache decision tree results
$cacheKey = "addon_recommendations_{$artworkId}_{$userId}";
$recommendations = $cache->get($cacheKey);

if (!$recommendations) {
    $recommendations = $addonRecommender->getGiftAddonRecommendations($artworkId, $userId);
    $cache->set($cacheKey, $recommendations, 1800); // 30 minutes
}
```

### Database Optimization

```sql
-- Add indexes for better performance
CREATE INDEX idx_artworks_category_status ON artworks(category_id, status);
CREATE INDEX idx_orders_user_status ON orders(user_id, status);
```

## Monitoring and Analytics

### Track Add-on Performance

```javascript
// Track add-on selections
const trackAddonSelection = (addon, artworkId) => {
  fetch('/backend/api/customer/track_behavior.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      user_id: auth.user_id,
      artwork_id: artworkId,
      behavior_type: 'addon_selection',
      additional_data: {
        addon_type: addon.type,
        addon_price: addon.price,
        confidence: addon.confidence
      }
    })
  });
};
```

### Admin Analytics

```jsx
const AddonAnalytics = () => {
  const [analytics, setAnalytics] = useState(null);
  
  useEffect(() => {
    fetchAddonAnalytics();
  }, []);
  
  const fetchAddonAnalytics = async () => {
    const response = await fetch('/backend/api/admin/addon_analytics.php');
    const data = await response.json();
    setAnalytics(data);
  };
  
  return (
    <div className="addon-analytics">
      <h2>Add-on Recommendation Performance</h2>
      
      <div className="metrics-grid">
        <div className="metric-card">
          <h3>Most Popular Add-ons</h3>
          <ul>
            {analytics?.popular_addons?.map(addon => (
              <li key={addon.type}>
                {addon.name}: {addon.selection_rate}% selection rate
              </li>
            ))}
          </ul>
        </div>
        
        <div className="metric-card">
          <h3>Decision Tree Accuracy</h3>
          <p>Overall Confidence: {analytics?.overall_confidence}%</p>
          <p>Rule Effectiveness: {analytics?.rule_effectiveness}%</p>
        </div>
      </div>
    </div>
  );
};
```

## Troubleshooting

### Common Issues

1. **No recommendations appearing**
   - Check if artwork exists and is active
   - Verify price and category parameters
   - Check decision tree rules

2. **Low confidence scores**
   - Ensure sufficient data for decision making
   - Check rule conditions and logic
   - Verify customer preferences

3. **Performance issues**
   - Enable caching for decision tree results
   - Add database indexes
   - Optimize rule evaluation

### Debug Mode

```javascript
// Enable debug mode
const response = await fetch(
  `/backend/api/customer/addon_recommendations.php?artwork_id=${artworkId}&debug=1`
);
const data = await response.json();
console.log('Debug info:', data.debug);
```

## Future Enhancements

1. **Machine Learning Integration**: Use ML to improve decision tree accuracy
2. **A/B Testing**: Test different decision tree configurations
3. **Real-time Updates**: Update recommendations based on inventory
4. **Personalization**: More sophisticated customer preference learning
5. **Bundle Recommendations**: Suggest add-on combinations

This decision tree system provides intelligent, rule-based add-on recommendations that enhance the gift-giving experience while following your exact specifications! 🎁






















