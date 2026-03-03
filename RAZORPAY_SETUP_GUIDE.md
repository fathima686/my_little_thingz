# Razorpay Payment Integration Setup Guide

## Step 1: Create Razorpay Account

1. Go to [Razorpay Dashboard](https://dashboard.razorpay.com/)
2. Sign up for a new account or log in to existing account
3. Complete the KYC verification process

## Step 2: Generate API Keys

1. In the Razorpay Dashboard, go to **Settings** → **API Keys**
2. Switch to **Test Mode** (for development)
3. Click **Generate Key**
4. Copy the **Key ID** and **Key Secret**

## Step 3: Update Configuration

### Option A: Environment Variables (Recommended)
Create a `.env` file in your backend folder:
```
RAZORPAY_KEY_ID=rzp_test_your_key_id_here
RAZORPAY_KEY_SECRET=your_secret_key_here
```

### Option B: Direct Configuration
Update `backend/config/razorpay.php`:
```php
$keyId = 'rzp_test_your_key_id_here';
$keySecret = 'your_secret_key_here';
```

## Step 4: Test Payment

1. Add items to cart
2. Fill shipping address
3. Click "Pay Securely"
4. Use test card details:
   - **Card Number**: 4111 1111 1111 1111
   - **Expiry**: Any future date
   - **CVV**: Any 3 digits
   - **Name**: Any name

## Test Card Numbers

| Card Type | Card Number | Description |
|-----------|-------------|-------------|
| Visa | 4111 1111 1111 1111 | Success |
| Visa | 4000 0000 0000 0002 | Declined |
| Mastercard | 5555 5555 5555 4444 | Success |
| American Express | 3782 8224 6310 005 | Success |

## Webhook Setup (Optional)

1. In Razorpay Dashboard, go to **Settings** → **Webhooks**
2. Add webhook URL: `https://yourdomain.com/backend/api/webhooks/razorpay.php`
3. Select events: `payment.captured`, `payment.failed`

## Going Live

1. Complete business verification in Razorpay Dashboard
2. Switch to **Live Mode**
3. Generate Live API keys
4. Update configuration with live keys
5. Test with small amount first

## Troubleshooting

- **Authentication Failed**: Check if API keys are correct
- **Invalid Key**: Ensure you're using Test keys in Test mode
- **Network Error**: Check if your server can reach Razorpay APIs
- **CORS Error**: Ensure proper headers are set in backend APIs

## Support

- [Razorpay Documentation](https://razorpay.com/docs/)
- [Integration Guide](https://razorpay.com/docs/payments/server-integration/)
- [Test Cards](https://razorpay.com/docs/payments/payments/test-card-upi-details/)