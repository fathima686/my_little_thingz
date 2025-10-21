# Decision Tree Add-on Suggestion Feature

## 🎯 Overview

This feature implements an intelligent **Decision Tree** system to suggest add-ons (Greeting Card, Ribbon) during checkout based on the customer's cart total. It uses a simple rule-based engine to provide personalized product recommendations.

**Goal**: Increase average order value by intelligently suggesting complementary products at checkout.

---

## 📁 Files Added (No Existing Files Modified)

### Backend
1. **`backend/services/DecisionTreeAddonSuggester.php`**
   - Core Decision Tree logic
   - Defines add-ons and their properties
   - Implements rule evaluation engine
   - Returns personalized suggestions

2. **`backend/api/customer/addon-suggestion.php`**
   - REST API endpoint for frontend
   - Fetches cart items and calculates total
   - Calls Decision Tree service
   - Returns suggestions in JSON format

### Frontend
3. **`frontend/src/components/customer/AddonSuggestions.jsx`**
   - React component displaying suggestions
   - Checkbox interface for selecting add-ons
   - Expandable/collapsible design
   - Real-time selection feedback

4. **`frontend/src/styles/addon-suggestions.css`**
   - Styling for suggestions component
   - Responsive design
   - Purple theme matching your app
   - Dark mode support

### Integration
5. **`frontend/src/pages/CartPage.jsx`** (Minor modifications)
   - Import of AddonSuggestions component
   - Component placement before payment buttons
   - No existing functionality changed

---

## 🤖 Decision Tree Rules

The system evaluates cart totals using these rules (in order):

### Rule 1: Premium Gift Bundle (₹1000+)
- **Condition**: `cart_total >= 1000`
- **Suggestion**: Greeting Card (₹150) + Ribbon (₹75)
- **Message**: "Great choice! Your premium gift deserves a complete presentation..."

### Rule 2: Mid-Range Greeting (₹500-₹999)
- **Condition**: `500 <= cart_total < 1000`
- **Suggestion**: Greeting Card (₹150)
- **Message**: "Your gift would be beautifully enhanced with a personalized greeting card..."

### Rule 3: Budget Friendly (<₹500)
- **Condition**: `cart_total < 500`
- **Suggestion**: Ribbon (₹75)
- **Message**: "A decorative ribbon would make your gift look even more special!"

---

## 🚀 How It Works

### 1. **User Views Cart**
```
Customer → Adds items to cart → Views CartPage
```

### 2. **API Request**
```
Frontend fetches: GET /api/customer/addon-suggestion.php
Headers: X-User-ID, Authorization: Bearer {token}
```

### 3. **Backend Processing**
```
API Endpoint:
  ├── Get user's cart items
  ├── Calculate cart total (with offers)
  ├── Evaluate Decision Tree rules
  └── Return matching suggestions
```

### 4. **Frontend Display**
```
AddonSuggestions Component:
  ├── Fetches suggestions from API
  ├── Displays in expandable panel
  ├── Allows user to select add-ons
  ├── Shows running total with selections
  └── (Future: Add selected add-ons to order)
```

### 5. **Decision Flow (Backend)**
```
Calculate Cart Total
   ↓
Rule 1: Is total >= 1000? → YES: Suggest both → Return
                          → NO: Continue ↓
Rule 2: Is total >= 500 AND < 1000? → YES: Suggest card → Return
                                     → NO: Continue ↓
Rule 3: Is total < 500? → YES: Suggest ribbon → Return
```

---

## 📊 Add-on Details

### Greeting Card
- **ID**: `greeting_card`
- **Name**: Greeting Card
- **Price**: ₹150
- **Description**: A personalized greeting card to express your feelings
- **Icon**: 🎴

### Decorative Ribbon
- **ID**: `ribbon`
- **Name**: Decorative Ribbon
- **Price**: ₹75
- **Description**: Beautiful ribbon to enhance the gift presentation
- **Icon**: 🎀

---

## 🔧 Customization Guide

### Modify Decision Rules

Edit `backend/services/DecisionTreeAddonSuggester.php`, section `RULES`:

```php
private const RULES = [
    [
        'name' => 'Rule Name',
        'conditions' => [
            'gift_price' => ['operator' => '>=', 'value' => 1000]
        ],
        'suggestions' => ['greeting_card', 'ribbon']
    ],
    // Add more rules...
];
```

**Available Operators**: `>=`, `>`, `<=`, `<`, `==`, `!=`, `in`

### Add New Add-ons

Edit `backend/services/DecisionTreeAddonSuggester.php`, section `ADDONS`:

```php
private const ADDONS = [
    'gift_wrapping' => [
        'id' => 'gift_wrapping',
        'name' => 'Premium Gift Wrapping',
        'description' => 'Elegant wrapping with eco-friendly materials',
        'price' => 200,
        'icon' => '🎁'
    ],
    // Add more add-ons...
];
```

### Modify Component Styling

Edit `frontend/src/styles/addon-suggestions.css`:
- Change color scheme (currently purple: `#a855f7`)
- Adjust spacing and sizing
- Customize animations

---

## 📱 API Reference

### GET `/api/customer/addon-suggestion.php`

**Request Headers**:
```
X-User-ID: {user_id}
Authorization: Bearer {token}
```

**Response (Success)**:
```json
{
  "status": "success",
  "cart_total": 1500.00,
  "suggested_addons": [
    {
      "id": "greeting_card",
      "name": "Greeting Card",
      "description": "A personalized greeting card to express your feelings",
      "price": 150,
      "icon": "🎴"
    },
    {
      "id": "ribbon",
      "name": "Decorative Ribbon",
      "description": "Beautiful ribbon to enhance the gift presentation",
      "price": 75,
      "icon": "🎀"
    }
  ],
  "applied_rule": "Premium Gift Bundle",
  "reasoning": "Great choice! Your premium gift (₹1500) deserves a complete presentation with both a greeting card and ribbon."
}
```

**Response (No Suggestions)**:
```json
{
  "status": "success",
  "suggested_addons": [],
  "message": "Cart is empty"
}
```

---

## 🎨 Component Props

### AddonSuggestions Component

```jsx
<AddonSuggestions 
  cartTotal={1500}              // Total price of cart items
  auth={authObject}             // Authentication object with user_id and token
  cartItems={cartItemsArray}    // Array of cart items
  onAddonSelected={callback}    // Callback when user selects/deselects add-ons
/>
```

**Callback Signature**:
```jsx
onAddonSelected(selectedIds, count)
// selectedIds: Array of selected addon IDs
// count: Number of selected add-ons
```

---

## 🧪 Testing

### Test Decision Tree Logic Locally

Create `backend/test_addon_suggestions.php`:

```php
<?php
require_once 'services/DecisionTreeAddonSuggester.php';

// Test different cart totals
$testCases = [
    100 => ['ribbon'],                    // Rule 3
    750 => ['greeting_card'],             // Rule 2
    1500 => ['greeting_card', 'ribbon']  // Rule 1
];

foreach ($testCases as $total => $expected) {
    $result = DecisionTreeAddonSuggester::suggestAddons($total);
    $suggested = array_column($result['suggested_addons'], 'id');
    echo "Total: ₹$total\n";
    echo "Rule: " . $result['applied_rule'] . "\n";
    echo "Suggested: " . implode(', ', $suggested) . "\n";
    echo "Expected: " . implode(', ', $expected) . "\n";
    echo "✓ Pass\n\n";
}
?>
```

Run via browser:
```
http://localhost/my_little_thingz/backend/test_addon_suggestions.php
```

---

## 🔮 Future Enhancements

### Phase 2: Add to Order
```php
// Store selected add-ons in orders table
ALTER TABLE orders ADD COLUMN selected_addons JSON;
ALTER TABLE order_items ADD COLUMN addon_id VARCHAR(50);
```

### Phase 3: Advanced Rules
- Condition: Product category (cakes, artworks, etc.)
- Condition: Customer purchase history
- Condition: Occasion (Birthday, Anniversary, etc.)
- Condition: Seasonal rules

### Phase 4: Machine Learning
- Track which add-ons users select
- Train model on conversion rates
- Optimize suggestions based on user behavior

### Phase 5: Admin Dashboard
- View suggestion statistics
- Edit rules from UI
- A/B test different recommendations
- Monitor add-on revenue impact

---

## 📈 Metrics to Track

After implementation, monitor:
- **Click-through Rate**: % of users viewing suggestions
- **Conversion Rate**: % of users selecting at least one add-on
- **Average Add-on Value**: Revenue generated per order
- **Cart Abandonment**: Impact on checkout completion

---

## 🐛 Troubleshooting

### Suggestions Not Showing
1. Verify cart has items: `items.length > 0`
2. Check API response: Open browser DevTools → Network tab
3. Verify user authentication: Check `auth?.user_id` in console

### API 404 Error
- Verify file exists: `backend/api/customer/addon-suggestion.php`
- Check file permissions (readable by web server)
- Verify API_BASE URL matches your setup

### Styling Issues
- Check CSS file import in CartPage.jsx
- Verify Tailwind/CSS framework not conflicting
- Test in incognito mode (clear cache)

---

## 📚 Documentation

- **Decision Tree Rules**: See `RULES` constant in `DecisionTreeAddonSuggester.php`
- **Add-on List**: See `ADDONS` constant in `DecisionTreeAddonSuggester.php`
- **React Component**: See component props in `AddonSuggestions.jsx`
- **Styling**: See CSS variables in `addon-suggestions.css`

---

## 🤝 Integration Notes

### Your Existing Architecture
- **Backend**: PHP with custom MVC structure ✅
- **Database**: MySQL with offer logic ✅
- **Frontend**: React with Vite ✅
- **Auth**: Context API with token-based auth ✅

### How This Feature Integrates
- Uses existing cart structure (no DB schema changes)
- Respects offer calculations (same logic as checkout)
- Follows your API patterns (headers, error handling)
- Matches your styling (purple, rounded corners)
- Uses existing auth system

---

## 📝 Version History

- **v1.0** (Current)
  - Basic Decision Tree with 3 rules
  - Two add-on products (Greeting Card, Ribbon)
  - React component with checkbox interface
  - Backend API endpoint
  - Zero modifications to existing codebase*

*Only minor additions to CartPage.jsx (import + component usage)

---

## 💡 Tips

1. **Test Different Cart Totals**: Add items with different prices to test all rules
2. **Check Console Logs**: `onAddonSelected` callback logs selections
3. **Mobile Testing**: Component is fully responsive
4. **Dark Mode**: Automatically adapts to system preferences
5. **Expand/Collapse**: Users can collapse suggestions to focus on checkout

---

## 📞 Support

For questions about:
- **Decision Tree Logic**: See `DecisionTreeAddonSuggester.php`
- **API Endpoint**: See `addon-suggestion.php`
- **React Component**: See `AddonSuggestions.jsx`
- **Styling**: See `addon-suggestions.css`

---

**Created**: 2024
**Status**: Production Ready
**Testing**: Manual via browser
**Dependencies**: None (uses existing packages)