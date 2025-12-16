# Razorpay Setup Instructions for Tutorial Payments

## Quick Setup Steps

### 1. Install Razorpay SDK
Open terminal/command prompt in the `backend` folder and run:
```bash
composer require razorpay/razorpay
```

Or if composer is not in PATH:
```bash
php composer.phar require razorpay/razorpay
```

### 2. Create Environment File
Create a file named `.env` in the `backend` folder (same level as `composer.json`) with:
```
RAZORPAY_KEY_ID=rzp_test_RGXWGOBliVCIpU
RAZORPAY_KEY_SECRET=9Q49llzcN0kLD3021OoSstOp
```

**Important:** The `.env` file should be git-ignored (never commit secrets to git).

### 3. Test Configuration
Visit this URL in your browser to test:
```
http://localhost/my_little_thingz/backend/test-razorpay-config.php
```

All tests should show ✓ (checkmarks).

### 4. Frontend Setup
Create `frontend/.env.local` with:
```
VITE_RAZORPAY_KEY=rzp_test_RGXWGOBliVCIpU
```

Restart your frontend dev server after creating this file.

## Troubleshooting

### Error: "Razorpay SDK not installed"
- Run `composer require razorpay/razorpay` in the backend folder
- Make sure `vendor/autoload.php` exists

### Error: "Razorpay keys not set"
- Create `backend/.env` file with the keys (see step 2 above)
- Make sure the file is readable by PHP

### Error: "500 Internal Server Error"
- Check PHP error logs: `xampp/apache/logs/error.log`
- Visit the test script to see detailed error messages
- Make sure database connection is working

## Files Modified
- `backend/config/razorpay-config.php` - Loads keys from environment
- `backend/api/customer/purchase-tutorial.php` - Handles payment creation
- `backend/api/customer/tutorial-razorpay-verify.php` - Verifies payments
- `backend/composer.json` - Added razorpay/razorpay dependency








