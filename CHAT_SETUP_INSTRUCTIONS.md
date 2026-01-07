# Product Customization Chat System Setup Instructions

## Overview

This system allows users to chat with admins about **product customization** directly from cart items or product pages. Perfect for discussing sizes, colors, materials, personalization options, and special requests.

## 1. Database Setup

Run the SQL schema to create the required tables:

```sql
-- Execute this in your MySQL database
source backend/database/order_chat_schema.sql;
```

This creates the `product_chat_messages` table for storing customization conversations.

## 2. File Structure

Ensure your project has this structure:

```
your-project/
├── backend/
│   ├── api/
│   │   └── product_chat/
│   │       ├── send_message.php
│   │       └── get_messages.php
│   ├── config/
│   │   └── database.php (your existing file)
│   └── database/
│       └── order_chat_schema.sql
├── frontend/
│   ├── css/
│   │   └── order-chat.css
│   └── js/
│       └── order-chat.js
└── cart.php (your existing cart page)
```

## 3. Integration Steps

### Step 1: Add CSS to your cart/product pages
```html
<link rel="stylesheet" href="frontend/css/order-chat.css">
```

### Step 2: Add the chat container div
```html
<div id="product-chat"></div>
```

### Step 3: Add JavaScript and initialize
```html
<script src="frontend/js/order-chat.js"></script>
<script>
    // Initialize chat for a specific product
    function showCustomizationChat(productId, cartItemId) {
        new ProductChat(productId, <?php echo $user_id; ?>, cartItemId, 'product-chat');
    }
</script>
```

### Step 4: Add customize buttons to your cart items
```html
<button onclick="showCustomizationChat(<?php echo $product_id; ?>, <?php echo $cart_item_id; ?>)">
    💬 Customize This Item
</button>
```

## 4. Usage Examples

### Cart Page Integration
```php
// In your cart page
foreach ($cart_items as $item) {
    echo '<button onclick="showCustomizationChat(' . $item['product_id'] . ', ' . $item['id'] . ')">
            💬 Customize This Item
          </button>';
}
```

### Product Page Integration
```php
// In your product details page
echo '<button onclick="showCustomizationChat(' . $product_id . ')">
        💬 Ask About Customization
      </button>';
```

## 5. Features

### Quick Action Buttons
The chat includes preset buttons for common customization questions:
- **Ask about Size** - Size options and measurements
- **Ask about Color** - Available colors and finishes
- **Ask about Material** - Material options and properties
- **Custom Request** - Special customization needs

### Message Types
- **Text messages** - Regular conversation
- **Customization requests** - Structured customization data
- **Image support** - Ready for future image sharing

### Real-time Updates
- 3-second polling for new messages
- Auto-scroll to latest messages
- Read/unread status tracking
- Typing indicators (ready for future enhancement)

## 6. Session Requirements

The system expects these session variables:
- `$_SESSION['admin_id']` - for admin users
- `$_SESSION['user_id']` - for regular users

## 7. Customization

### Theme Colors
Modify CSS variables in `frontend/css/order-chat.css`:

```css
:root {
    --chat-bg-primary: #your-color;
    --chat-user-bg: #your-color;
    --chat-button-bg: #your-color;
}
```

### Quick Action Templates
Modify templates in `frontend/js/order-chat.js`:

```javascript
const templates = {
    size: "Your custom size question template...",
    color: "Your custom color question template...",
    // ... add more templates
};
```

## 8. Admin Interface

Admins can respond to customization chats by:
1. Accessing the same chat interface
2. Viewing all product conversations
3. Managing customization requests

## 9. Use Cases

### Perfect for:
- **Size consultations** - "What size would work best for my space?"
- **Color matching** - "Can you match this specific color?"
- **Material questions** - "Is this suitable for outdoor use?"
- **Personalization** - "Can you engrave a specific message?"
- **Custom designs** - "Can you create something unique?"
- **Bulk orders** - "I need 50 of these with different names"

### Example Conversations:
- User: "Hi! I need this mug in blue, but I don't see that color option."
- Admin: "We can definitely do blue! We have navy blue and sky blue available. Which would you prefer?"

## 10. Testing

1. Add a product to your cart
2. Click "Customize This Item"
3. Send a test message about customization
4. Check that messages persist on page refresh
5. Test with both user and admin accounts

## 11. Future Enhancements

The system is designed to be easily extensible:
- Image upload for reference photos
- Voice messages for complex requests
- Integration with inventory system
- Automated pricing for customizations
- Order modification directly from chat
- Integration with design tools