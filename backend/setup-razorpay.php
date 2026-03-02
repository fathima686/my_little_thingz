<?php
/**
 * Razorpay Setup Helper
 * This script helps you configure Razorpay API keys
 */

echo "=== RAZORPAY SETUP HELPER ===\n\n";

echo "To set up Razorpay payment:\n\n";

echo "1. Go to Razorpay Dashboard: https://dashboard.razorpay.com/\n";
echo "2. Sign up or log in to your account\n";
echo "3. Go to Settings → API Keys\n";
echo "4. Switch to Test Mode\n";
echo "5. Click 'Generate Key'\n";
echo "6. Copy your Key ID and Key Secret\n\n";

echo "Then update your configuration:\n\n";

echo "Option 1 - Environment Variables (Recommended):\n";
echo "Create a .env file in the backend folder with:\n";
echo "RAZORPAY_KEY_ID=rzp_test_your_key_id_here\n";
echo "RAZORPAY_KEY_SECRET=your_secret_key_here\n\n";

echo "Option 2 - Direct Configuration:\n";
echo "Edit backend/config/razorpay.php and replace:\n";
echo "\$keyId = 'rzp_test_your_actual_key_id';\n";
echo "\$keySecret = 'your_actual_secret_key';\n\n";

echo "Test Cards for Testing:\n";
echo "Card Number: 4111 1111 1111 1111\n";
echo "Expiry: Any future date (e.g., 12/25)\n";
echo "CVV: Any 3 digits (e.g., 123)\n";
echo "Name: Any name\n\n";

// Check current configuration
echo "Current Configuration Status:\n";
$config = require __DIR__ . '/config/razorpay.php';

if ($config['key_id'] === 'rzp_test_REPLACE_WITH_YOUR_KEY_ID') {
    echo "❌ Key ID: Not configured (placeholder value)\n";
} else {
    echo "✅ Key ID: " . substr($config['key_id'], 0, 15) . "...\n";
}

if ($config['key_secret'] === 'REPLACE_WITH_YOUR_SECRET_KEY') {
    echo "❌ Key Secret: Not configured (placeholder value)\n";
} else {
    echo "✅ Key Secret: " . substr($config['key_secret'], 0, 10) . "...\n";
}

echo "\nCurrency: " . $config['currency'] . "\n";

if ($config['key_id'] === 'rzp_test_REPLACE_WITH_YOUR_KEY_ID' || $config['key_secret'] === 'REPLACE_WITH_YOUR_SECRET_KEY') {
    echo "\n⚠️  SETUP REQUIRED: Please configure your Razorpay API keys\n";
    echo "Follow the steps above to get your keys from Razorpay Dashboard\n";
} else {
    echo "\n✅ Configuration looks good! You can test payments now.\n";
}

echo "\n=== END SETUP HELPER ===\n";
?>