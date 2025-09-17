# Razorpay Payment Setup Guide

## Current Issue
You're getting "Authentication failed" error because Razorpay API credentials are not configured.

## Quick Fix (For Testing)

1. **Get Razorpay Test Credentials:**
   - Go to [Razorpay Dashboard](https://dashboard.razorpay.com/)
   - Sign up/Login
   - Go to Settings > API Keys
   - Generate Test Keys
   - Copy Key ID and Key Secret

2. **Update Configuration:**
   - Open `backend/config/razorpay.php`
   - Replace `YOUR_RAZORPAY_KEY_ID_HERE` with your actual Key ID
   - Replace `YOUR_RAZORPAY_KEY_SECRET_HERE` with your actual Key Secret

## Example:
```php
$razorpay = [
    'key_id' => getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_1234567890abcdef',
    'key_secret' => getenv('RAZORPAY_KEY_SECRET') ?: 'your_actual_secret_key_here',
    'currency' => 'INR',
];
```

## For Production (Recommended)
1. Create a `.env` file in your project root
2. Add your credentials:
   ```
   RAZORPAY_KEY_ID=rzp_live_your_live_key_id
   RAZORPAY_KEY_SECRET=your_live_secret_key
   ```

## Test Mode vs Live Mode
- **Test Mode**: Use `rzp_test_` keys for development
- **Live Mode**: Use `rzp_live_` keys for production

## After Setup
Once you add your credentials, the payment flow should work without authentication errors.
