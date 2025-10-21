# 🏗️ Decision Tree Add-on Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     My Little Thingz App                        │
│                                                                 │
│  Frontend (React/Vite)          Backend (PHP)      Database    │
│  ════════════════════════════════════════════════════════════  │
│                                                                 │
│  CartPage.jsx                                                   │
│  ├─ Import AddonSuggestions                                     │
│  ├─ Pass: cartTotal, auth, cartItems                            │
│  └─ Render in checkout section                                  │
│      │                                                           │
│      ↓                                                           │
│  ┌──────────────────────────────────┐                           │
│  │  AddonSuggestions.jsx            │                           │
│  │  ┌────────────────────────────┐  │                           │
│  │  │ Fetches suggestions on load│  │                           │
│  │  │ GET /addon-suggestion.php  │  │                           │
│  │  │ + auth headers             │  │                           │
│  │  └────────────────────────────┘  │                           │
│  │           │                       │                           │
│  └───────────┼───────────────────────┘                           │
│              │ X-User-ID, Bearer token                           │
│              ↓                                                    │
│  ┌─────────────────────────────────────────────────────┐         │
│  │ addon-suggestion.php (API Endpoint)                │         │
│  │ ┌───────────────────────────────────────────────┐ │         │
│  │ │ 1. Validate user & auth                       │ │         │
│  │ │ 2. Query: SELECT * FROM cart WHERE user_id   │ │         │
│  │ │ 3. JOIN with artworks table for pricing      │ │         │
│  │ │ 4. Calculate cart total (with offer logic)   │ │         │
│  │ │ 5. Pass to Decision Tree service             │ │         │
│  │ │ 6. Return JSON response with suggestions     │ │         │
│  │ └───────────────────────────────────────────────┘ │         │
│  └──────────────────┬──────────────────────────────────┘         │
│                     │                                            │
│                     ↓                                            │
│  ┌──────────────────────────────────────────────────┐           │
│  │ DecisionTreeAddonSuggester.php (Service)       │           │
│  │ ┌──────────────────────────────────────────────┐│           │
│  │ │ RULES (Decision Tree)                       ││           │
│  │ │ ├─ Rule 1: >=1000 → 2 addons               ││           │
│  │ │ ├─ Rule 2: 500-999 → 1 addon               ││           │
│  │ │ └─ Rule 3: <500 → 1 addon                  ││           │
│  │ │                                             ││           │
│  │ │ ADDONS (Catalog)                           ││           │
│  │ │ ├─ greeting_card (150)                     ││           │
│  │ │ └─ ribbon (75)                             ││           │
│  │ │                                             ││           │
│  │ │ Methods:                                    ││           │
│  │ │ ├─ suggestAddons($total) → returns array  ││           │
│  │ │ ├─ evaluateRule($rule) → boolean           ││           │
│  │ │ └─ compareValues($value, $condition)       ││           │
│  │ └──────────────────────────────────────────────┘│           │
│  └──────────────────────────────────────────────────┘           │
│       ↑                                                          │
│       │ Return: suggested_addons, applied_rule, reasoning       │
│       │                                                          │
│  ┌────┴──────────────────────────────────────────────────────┐  │
│  │ JSON Response to AddonSuggestions.jsx                    │  │
│  │ {                                                        │  │
│  │   "status": "success",                                  │  │
│  │   "cart_total": 1500,                                   │  │
│  │   "suggested_addons": [{...}, {...}],                  │  │
│  │   "applied_rule": "Premium Gift Bundle",               │  │
│  │   "reasoning": "Great choice!..."                       │  │
│  │ }                                                        │  │
│  └────┬──────────────────────────────────────────────────────┘  │
│       │                                                          │
│       ↓                                                          │
│  AddonSuggestions Component Renders:                            │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ 🎁 Enhance Your Gift                             ▼     │   │
│  │ Premium Gift Bundle                              expand │   │
│  │ ┌─────────────────────────────────────────────────────┐ │   │
│  │ │ Great choice! Your premium gift deserves...      │ │   │
│  │ │                                                   │ │   │
│  │ │ ☑ 🎴 Greeting Card              +₹150      │ │   │
│  │ │ ☑ 🎀 Decorative Ribbon          +₹75       │ │   │
│  │ │                                                   │ │   │
│  │ │ ✓ 2 items selected → +₹225                  │ │   │
│  │ └─────────────────────────────────────────────────────┘ │   │
│  └──────────────────────────────────────────────────────────┘   │
│                    ↓                                             │
│  User continues to Payment (Razorpay)                           │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Decision Tree Logic Flow

```
Input: cart_total (number)
│
├─ Evaluate Rule 1: Is total >= 1000?
│  │
│  ├─ YES → Return [greeting_card, ribbon]
│  │         Stop here ✓
│  │
│  └─ NO → Continue to Rule 2
│
├─ Evaluate Rule 2: Is total >= 500 AND < 1000?
│  │
│  ├─ YES → Return [greeting_card]
│  │         Stop here ✓
│  │
│  └─ NO → Continue to Rule 3
│
└─ Evaluate Rule 3: Is total < 500?
   │
   ├─ YES → Return [ribbon]
   │         Stop here ✓
   │
   └─ NO → Return [] (no suggestions)
```

---

## Data Flow Diagram

```
Browser                          Server
═══════════════════════════════════════════════════════

User at Checkout
│
├─ Component Mount
│  └─ useEffect()
│
└─ Fetch API Call
   │
   ├─ URL: /api/customer/addon-suggestion.php
   ├─ Method: GET
   ├─ Headers:
   │  ├─ X-User-ID: {user_id}
   │  └─ Authorization: Bearer {token}
   │
   └─────────────────────────────────────→ PHP Endpoint
                                          │
                                          ├─ Validate headers
                                          ├─ Get user_id from header
                                          │
                                          ├─ Query Database
                                          │  └─ SELECT c.*, a.* FROM cart c
                                          │     JOIN artworks a ON c.artwork_id = a.id
                                          │     WHERE c.user_id = ?
                                          │
                                          ├─ Calculate Total
                                          │  ├─ Loop through items
                                          │  ├─ Apply offer prices/percentages
                                          │  └─ Sum: ₹X
                                          │
                                          ├─ Call Decision Tree
                                          │  └─ DecisionTreeAddonSuggester
                                          │     ::suggestAddons(₹X)
                                          │
                                          └─ Return JSON
   ←───────────────────────────────────────
   │
   ├─ Receive Response
   │
   ├─ setState(suggestions)
   │
   └─ Render Component
      │
      ├─ Display suggestions
      ├─ Show checkboxes
      └─ Allow selection
```

---

## Component Hierarchy

```
CartPage
├─ Import: AddonSuggestions component
├─ State: cartTotal, auth, items
│
└─ <aside className="cart-summary">
   ├─ Subtotal/Shipping info
   │
   ├─ <AddonSuggestions>          ← NEW COMPONENT
   │  ├─ Props: cartTotal, auth, cartItems, onAddonSelected
   │  │
   │  ├─ useEffect: Fetch suggestions
   │  │  └─ GET /addon-suggestion.php
   │  │
   │  ├─ Render: Header (expandable)
   │  │
   │  ├─ Render: Content
   │  │  ├─ Reasoning message
   │  │  ├─ Add-on list with checkboxes
   │  │  └─ Selection summary
   │  │
   │  └─ onAddonSelected callback (optional)
   │
   ├─ Customization section
   │
   └─ Payment buttons
      └─ "Pay Securely" → Razorpay
```

---

## Database Queries

### Query 1: Get Cart Items (addon-suggestion.php)
```sql
SELECT 
  c.id as cart_id, 
  c.artwork_id, 
  c.quantity, 
  a.price, 
  a.offer_price, 
  a.offer_percent, 
  a.offer_starts_at, 
  a.offer_ends_at
FROM cart c 
JOIN artworks a ON c.artwork_id = a.id 
WHERE c.user_id = ? AND a.status = 'active'
```

**No modifications to database schema!**

---

## API Response Examples

### Example 1: Premium Cart (₹1500)
```json
{
  "status": "success",
  "cart_total": 1500,
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

### Example 2: Mid-Range Cart (₹750)
```json
{
  "status": "success",
  "cart_total": 750,
  "suggested_addons": [
    {
      "id": "greeting_card",
      "name": "Greeting Card",
      "description": "A personalized greeting card to express your feelings",
      "price": 150,
      "icon": "🎴"
    }
  ],
  "applied_rule": "Mid-Range Greeting",
  "reasoning": "Your gift (₹750) would be beautifully enhanced with a personalized greeting card."
}
```

### Example 3: Budget Cart (₹300)
```json
{
  "status": "success",
  "cart_total": 300,
  "suggested_addons": [
    {
      "id": "ribbon",
      "name": "Decorative Ribbon",
      "description": "Beautiful ribbon to enhance the gift presentation",
      "price": 75,
      "icon": "🎀"
    }
  ],
  "applied_rule": "Budget Friendly",
  "reasoning": "A decorative ribbon would make your gift (₹300) look even more special!"
}
```

---

## File Structure

```
My Little Thingz/
├── backend/
│   ├── api/
│   │   └── customer/
│   │       ├── addon-suggestion.php        ← NEW
│   │       ├── cart.php
│   │       ├── checkout.php
│   │       └── ...
│   │
│   ├── services/
│   │   ├── DecisionTreeAddonSuggester.php  ← NEW
│   │   ├── ShiprocketAutomation.php
│   │   └── ...
│   │
│   ├── config/
│   │   └── database.php
│   │
│   └── ...
│
├── frontend/
│   └── src/
│       ├── components/
│       │   └── customer/
│       │       ├── AddonSuggestions.jsx    ← NEW
│       │       ├── CustomizationModal.jsx
│       │       └── ...
│       │
│       ├── pages/
│       │   ├── CartPage.jsx                ← MODIFIED (2 lines added)
│       │   └── ...
│       │
│       ├── styles/
│       │   ├── addon-suggestions.css       ← NEW
│       │   ├── customization-modal.css
│       │   └── ...
│       │
│       └── ...
│
├── DECISION_TREE_ADDON_FEATURE.md          ← NEW (Full docs)
├── ADDON_IMPLEMENTATION_SUMMARY.md         ← NEW (Overview)
├── ADDON_QUICK_START.md                    ← NEW (Testing guide)
├── ADDON_ARCHITECTURE.md                   ← NEW (This file)
│
└── ... (existing files)
```

---

## Technology Stack

| Layer | Technology | Files |
|-------|-----------|-------|
| **Frontend UI** | React (Vite) | `AddonSuggestions.jsx` |
| **Frontend Styling** | CSS3 | `addon-suggestions.css` |
| **Frontend State** | React Hooks | `useEffect`, `useState` |
| **API Communication** | Fetch API | `addon-suggestion.php` |
| **Backend Logic** | PHP (OOP) | `DecisionTreeAddonSuggester.php` |
| **Business Rules** | Decision Tree | RULES constant |
| **Database** | MySQL | cart, artworks tables |
| **Authentication** | Token-based | Headers validation |

---

## Security Considerations

```
Request Validation Flow
│
├─ Check: X-User-ID header exists
├─ Check: Authorization header has Bearer token
├─ Check: User ID matches session
├─ Check: User owns the cart items
│
└─ Execute: Return only user's suggestions
```

---

## Performance Characteristics

```
Operation                              Time
═════════════════════════════════════════════════
Frontend Load Component                ~10ms
API Call + Network                     ~100-200ms
Database Query (cart items)            ~20-50ms
Decision Tree Evaluation               ~1-2ms
Response Parse + Render                ~10-30ms
───────────────────────────────────────────────
Total Time to Display                  ~150-300ms
```

---

## Scalability Notes

- **No DB schema changes** → Easy to deploy
- **Stateless API** → Can scale horizontally
- **Lightweight JSON** → Fast network transfer
- **Client-side caching** → No repeated calls during session
- **Add-ons hardcoded** → No DB lookup needed for catalog

---

## Integration Points

```
Existing System              ← New Feature Integrates
══════════════════════════════════════════════════════

Cart System (cart table)     ← Uses cart total calculation
Artworks System (pricing)    ← Uses offer prices
Auth System (tokens)         ← Uses auth headers
UI Framework (React)         ← Embedded in CartPage
Checkout Flow                ← Placed in summary section
Payment System (Razorpay)    ← Completely independent
```

---

## Extension Points

Future phases can easily extend:

```
Phase 2: Store selections
└─ Add: addon_selections to order_items table
└─ Add: addon_price to order table

Phase 3: Analytics
└─ Track: addon click-through rates
└─ Track: conversion rates per rule
└─ Track: revenue per rule

Phase 4: Advanced rules
└─ Add: category-based conditions
└─ Add: user behavior conditions
└─ Add: seasonal rules

Phase 5: ML Integration
└─ Input: Historical user data
└─ Process: Train model
└─ Output: Predictive suggestions
```

---

## Summary

✅ **Clean Architecture** - Separation of concerns
✅ **Zero Schema Changes** - Uses existing tables
✅ **Scalable** - Can add rules/add-ons easily
✅ **Secure** - Validates user & token
✅ **Fast** - Lightweight queries & calculations
✅ **Maintainable** - Well-documented code
✅ **Extensible** - Easy to add future features

---

**Diagram created**: 2024
**Version**: 1.0
**Status**: Production Ready