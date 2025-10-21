# Auto Add-on Display Integration

## Overview

This component automatically displays add-on products (cards and ribbons) based on the gift price directly on your product detail page. For your chocolates product (₹30), it will show ribbon recommendations since the price is under ₹1000.

## Integration Examples

### 1. Product Detail Page Integration

```jsx
import AutoAddonDisplay from './components/customer/AutoAddonDisplay';
import './styles/auto-addon-display.css';

const ProductDetailPage = ({ artwork, auth }) => {
  const handleAddonSelect = (addon) => {
    // Add addon to cart
    console.log('Adding addon to cart:', addon);
    // Integrate with your existing cart system
  };

  return (
    <div className="product-detail-page">
      {/* Your existing product details */}
      <div className="product-info">
        <h1>{artwork.title}</h1>
        <p className="product-price">₹{artwork.price}</p>
        <p className="product-description">{artwork.description}</p>
        
        <div className="product-actions">
          <button className="wishlist-btn">
            <LuHeart className="btn-icon" />
            Add to Wishlist
          </button>
          <button className="cart-btn">
            <LuShoppingCart className="btn-icon" />
            Add to Cart
          </button>
        </div>
      </div>

      {/* Auto Add-on Display - Shows automatically based on price */}
      <AutoAddonDisplay
        artworkId={artwork.id}
        price={artwork.price}
        category={artwork.category_name}
        userId={auth?.user_id}
        onAddonSelect={handleAddonSelect}
        showDetails={true}
        showPricing={true}
        showActions={true}
      />

      {/* Your existing similar products section */}
      <div className="similar-products">
        <h3>Similar to this</h3>
        {/* Your existing similar products carousel */}
      </div>
    </div>
  );
};
```

### 2. Modal/Popup Integration

```jsx
const ProductModal = ({ artwork, isOpen, onClose }) => {
  const handleAddonSelect = (addon) => {
    // Add addon to cart
    addToCart(artwork.id, addon);
  };

  if (!isOpen) return null;

  return (
    <div className="product-modal">
      <div className="modal-content">
        <div className="modal-header">
          <h2>{artwork.title}</h2>
          <button onClick={onClose} className="close-btn">×</button>
        </div>
        
        <div className="modal-body">
          <div className="product-details">
            <img src={artwork.image_url} alt={artwork.title} />
            <div className="product-info">
              <p className="price">₹{artwork.price}</p>
              <p className="description">{artwork.description}</p>
            </div>
          </div>

          {/* Auto Add-on Display */}
          <AutoAddonDisplay
            artworkId={artwork.id}
            price={artwork.price}
            category={artwork.category_name}
            onAddonSelect={handleAddonSelect}
            showDetails={true}
            showPricing={true}
            showActions={true}
          />
        </div>
      </div>
    </div>
  );
};
```

### 3. Cart Page Integration

```jsx
const CartPage = ({ cartItems, auth }) => {
  const handleAddonSelect = (addon, artworkId) => {
    // Add addon to specific cart item
    addAddonToCartItem(artworkId, addon);
  };

  return (
    <div className="cart-page">
      <h1>Shopping Cart</h1>
      
      {cartItems.map(item => (
        <div key={item.id} className="cart-item">
          <div className="item-details">
            <img src={item.image_url} alt={item.title} />
            <div className="item-info">
              <h3>{item.title}</h3>
              <p>₹{item.price}</p>
            </div>
          </div>
          
          {/* Auto Add-on Display for each item */}
          <AutoAddonDisplay
            artworkId={item.artwork_id}
            price={item.price}
            category={item.category_name}
            userId={auth?.user_id}
            onAddonSelect={(addon) => handleAddonSelect(addon, item.artwork_id)}
            showDetails={false}
            showPricing={true}
            showActions={true}
          />
        </div>
      ))}
    </div>
  );
};
```

## Real-World Examples

### Example 1: Chocolates (₹30)
**Decision Logic:**
1. Price ₹30 ≤ ₹1000 → Optional Ribbon
2. Category: Chocolates → Colorful ribbon

**Displayed Add-ons:**
- **Colorful Ribbon** (₹20) - "Birthday gifts look great with colorful ribbons"
- **Basic Greeting Card** (₹25) - "Mid-range gifts can include simple cards"

### Example 2: Wedding Hamper (₹2000)
**Decision Logic:**
1. Price ₹2000 > ₹1000 → Include Greeting Card
2. Category: Wedding → Premium greeting card

**Displayed Add-ons:**
- **Premium Greeting Card** (₹50) - "Wedding gifts require elegant greeting cards"
- **Elegant Ribbon** (₹35) - "High-value gifts benefit from premium presentation"

### Example 3: Birthday Gift Box (₹800)
**Decision Logic:**
1. Price ₹800 ≤ ₹1000 → Optional Ribbon
2. Category: Birthday → Colorful ribbon

**Displayed Add-ons:**
- **Colorful Ribbon** (₹20) - "Birthday gifts look great with colorful ribbons"
- **Fun Greeting Card** (₹30) - "Birthday celebrations need fun cards"

## Component Features

### Automatic Display
- **No Manual Trigger**: Shows automatically based on price
- **Smart Logic**: Uses decision tree rules
- **Context Aware**: Considers category and occasion

### Interactive Selection
- **Click to Select**: Users can select multiple add-ons
- **Visual Feedback**: Selected items are highlighted
- **Total Calculation**: Shows total price of selected add-ons

### Detailed Information
- **Add-on Details**: Name, description, price, type
- **Decision Logic**: Shows why each add-on was recommended
- **Confidence Scores**: Visual indicators of recommendation quality

### Action Buttons
- **Add All to Cart**: Add all selected add-ons at once
- **Save for Later**: Add to wishlist
- **Individual Selection**: Select specific add-ons

## Styling Integration

### CSS Import
```jsx
import './styles/auto-addon-display.css';
```

### Custom Styling
```css
/* Override default styles */
.auto-addon-container {
  margin: 2rem 0;
  border-radius: 20px;
}

.addon-title {
  color: #your-brand-color;
}

.addon-card.selected {
  border-color: #your-brand-color;
}
```

## API Integration

### Backend Requirements
The component uses the existing `addon_recommendations.php` API endpoint:

```javascript
// Automatic API call
GET /backend/api/customer/addon_recommendations.php?artwork_id=123&user_id=456
```

### Response Handling
```javascript
// The component automatically handles:
// - Loading states
// - Error handling
// - Data formatting
// - User interactions
```

## Performance Optimization

### Lazy Loading
```jsx
// Only load when needed
const AutoAddonDisplay = React.lazy(() => import('./components/customer/AutoAddonDisplay'));

// Use with Suspense
<Suspense fallback={<div>Loading add-ons...</div>}>
  <AutoAddonDisplay artworkId={artwork.id} />
</Suspense>
```

### Caching
```javascript
// The API endpoint includes caching
// Recommendations are cached for 30 minutes
// Reduces server load and improves performance
```

## Mobile Responsiveness

### Responsive Design
- **Grid Layout**: Automatically adjusts to screen size
- **Touch Friendly**: Large touch targets for mobile
- **Readable Text**: Optimized font sizes for mobile
- **Swipe Gestures**: Smooth scrolling on mobile

### Mobile-Specific Features
- **Stacked Layout**: Cards stack vertically on mobile
- **Full-Width Buttons**: Action buttons span full width
- **Simplified Logic**: Decision logic is simplified for mobile

## Troubleshooting

### Common Issues

1. **Add-ons not showing**
   - Check if artwork exists and is active
   - Verify price parameter is passed
   - Check API endpoint is working

2. **Styling issues**
   - Ensure CSS file is imported
   - Check for conflicting styles
   - Verify responsive breakpoints

3. **Selection not working**
   - Check onAddonSelect callback
   - Verify state management
   - Check console for errors

### Debug Mode
```jsx
<AutoAddonDisplay
  artworkId={artwork.id}
  price={artwork.price}
  debug={true} // Enable debug logging
/>
```

## Future Enhancements

1. **Personalization**: Learn from user preferences
2. **A/B Testing**: Test different recommendation strategies
3. **Real-time Updates**: Update based on inventory
4. **Bundle Offers**: Suggest add-on combinations
5. **Social Proof**: Show popularity of add-ons

This auto add-on display provides a seamless way to increase average order value while enhancing the customer experience with intelligent, context-aware recommendations! 🎁

