# Subscription Implementation Guide

## Overview
Complete subscription system has been implemented for the tutorial platform, allowing users to subscribe to Premium (₹499/month) or Pro (₹999/month) plans for unlimited access to all tutorials.

## Database Setup

### Run Migration
Execute the migration script to create the necessary tables:

```bash
cd backend
php migrate_subscriptions.php
```

This will create:
- `subscription_plans` - Stores subscription plan details (Free, Premium, Pro)
- `subscriptions` - Tracks user subscriptions
- `subscription_invoices` - Records subscription payments

### Tables Created

#### subscription_plans
- `id` - Primary key
- `plan_code` - Unique code (free, premium, pro)
- `name` - Plan name
- `description` - Plan description
- `price` - Monthly price in INR
- `billing_period` - monthly or yearly
- `razorpay_plan_id` - Razorpay plan ID (auto-created)
- `features` - JSON array of features
- `is_active` - Whether plan is active

#### subscriptions
- `id` - Primary key
- `user_id` - Foreign key to users
- `plan_id` - Foreign key to subscription_plans
- `razorpay_subscription_id` - Razorpay subscription ID
- `status` - Subscription status (created, authenticated, active, cancelled, etc.)
- `current_start` - Current billing period start
- `current_end` - Current billing period end
- `quantity` - Number of subscriptions
- `total_count` - Total billing cycles (12 for monthly)
- `paid_count` - Number of payments made
- `remaining_count` - Remaining billing cycles

#### subscription_invoices
- `id` - Primary key
- `subscription_id` - Foreign key to subscriptions
- `razorpay_invoice_id` - Razorpay invoice ID
- `razorpay_payment_id` - Razorpay payment ID
- `amount` - Invoice amount
- `currency` - Currency (INR)
- `status` - Invoice status (issued, paid, cancelled, etc.)
- `invoice_date` - Invoice creation date
- `paid_at` - Payment date

## API Endpoints

### 1. Create Subscription
**Endpoint:** `POST /backend/api/customer/create-subscription.php`

**Headers:**
- `X-Tutorial-Email`: User email

**Request Body:**
```json
{
  "plan_code": "premium" // or "pro"
}
```

**Response:**
```json
{
  "status": "success",
  "subscription_id": 1,
  "razorpay_subscription_id": "sub_xxxxx",
  "razorpay_plan_id": "plan_xxxxx",
  "plan_code": "premium",
  "amount": 49900,
  "currency": "INR",
  "subscription_status": "created"
}
```

### 2. Get Subscription Status
**Endpoint:** `GET /backend/api/customer/subscription-status.php`

**Headers:**
- `X-Tutorial-Email`: User email

**Response:**
```json
{
  "status": "success",
  "has_subscription": true,
  "subscription_id": 1,
  "plan_code": "premium",
  "plan_name": "Premium",
  "subscription_status": "active",
  "current_start": "2025-01-01 00:00:00",
  "current_end": "2025-02-01 00:00:00",
  "price": 499.00,
  "features": ["Unlimited tutorial access", "HD video quality", ...],
  "is_active": true
}
```

### 3. Verify Subscription Payment
**Endpoint:** `POST /backend/api/customer/subscription-verify.php`

**Headers:**
- `X-Tutorial-Email`: User email

**Request Body:**
```json
{
  "razorpay_subscription_id": "sub_xxxxx",
  "razorpay_payment_id": "pay_xxxxx",
  "razorpay_signature": "signature_xxxxx"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Subscription verified and activated",
  "subscription_status": "active"
}
```

### 4. Webhook Handler
**Endpoint:** `POST /backend/api/webhooks/razorpay-subscription-webhook.php`

This endpoint handles Razorpay webhook events:
- `subscription.activated` - Subscription activated
- `subscription.charged` - Payment received
- `subscription.cancelled` - Subscription cancelled
- `subscription.completed` - Subscription completed
- `subscription.paused` - Subscription paused
- `subscription.resumed` - Subscription resumed
- `subscription.updated` - Subscription updated

**Setup:**
1. Configure webhook URL in Razorpay Dashboard
2. Set `RAZORPAY_WEBHOOK_SECRET` in your `.env` file

## Frontend Integration

### Subscription Flow

1. **User clicks "Upgrade" on a plan**
   - Calls `create-subscription.php`
   - Receives Razorpay subscription ID

2. **Razorpay Checkout**
   - Opens Razorpay subscription checkout
   - User completes payment

3. **Payment Verification**
   - Frontend calls `subscription-verify.php`
   - Backend verifies signature and activates subscription

4. **Access Control**
   - Users with active Premium/Pro subscriptions get access to all tutorials
   - Checked in `check-tutorial-access.php`

### Key Components

- `TutorialsDashboard.jsx` - Main dashboard with subscription plans
- `SubscriptionPlansSection` - Displays subscription plans
- `fetchSubscriptionStatus()` - Fetches current subscription status
- `openRazorpaySubscriptionCheckout()` - Opens Razorpay subscription checkout

## Tutorial Access Logic

The system checks access in this order:

1. **Free Tutorials** - Always accessible
2. **Active Subscription** - Premium/Pro users get access to all tutorials
3. **Individual Purchase** - Users who purchased specific tutorials

Updated in:
- `backend/api/customer/check-tutorial-access.php`
- `backend/api/customer/purchase-tutorial.php`

## Subscription Plans

### Free Plan (₹0/month)
- Limited free tutorials
- Basic video quality
- Community support

### Premium Plan (₹499/month)
- Unlimited tutorial access
- HD video quality
- New content weekly
- Priority support
- Download videos

### Pro Plan (₹999/month)
- Everything in Premium
- 1-on-1 mentorship
- Live workshops
- Certificate of completion
- Early access to new content

## Razorpay Configuration

### Required Environment Variables
Add to `backend/.env`:
```
RAZORPAY_KEY_ID=your_key_id
RAZORPAY_KEY_SECRET=your_key_secret
RAZORPAY_WEBHOOK_SECRET=your_webhook_secret
```

### Frontend Environment Variable
Add to `frontend/.env`:
```
VITE_RAZORPAY_KEY=your_key_id
```

## Testing

1. **Create Subscription:**
   ```bash
   curl -X POST http://localhost/my_little_thingz/backend/api/customer/create-subscription.php \
     -H "Content-Type: application/json" \
     -H "X-Tutorial-Email: user@example.com" \
     -d '{"plan_code": "premium"}'
   ```

2. **Check Status:**
   ```bash
   curl http://localhost/my_little_thingz/backend/api/customer/subscription-status.php \
     -H "X-Tutorial-Email: user@example.com"
   ```

## Notes

- Free plan subscriptions are activated immediately without Razorpay
- Premium/Pro plans require Razorpay payment
- Subscriptions are automatically renewed (12 months)
- Webhook handles renewal payments automatically
- Users with active subscriptions get access to all tutorials automatically

## Troubleshooting

1. **Migration fails:** Ensure database connection is configured correctly
2. **Razorpay errors:** Check API keys in `.env` file
3. **Webhook not working:** Verify webhook URL and secret in Razorpay dashboard
4. **Subscription not activating:** Check webhook logs and subscription status in Razorpay dashboard


