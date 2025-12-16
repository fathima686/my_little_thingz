<?php
/**
 * Simple script to update Razorpay credentials
 * Usage: Run this file in browser or via command line with parameters
 * 
 * Via browser: update-razorpay-credentials.php?key_id=YOUR_KEY&key_secret=YOUR_SECRET
 * Via CLI: php update-razorpay-credentials.php YOUR_KEY YOUR_SECRET
 */

$keyId = null;
$keySecret = null;

// Get from command line arguments
if (php_sapi_name() === 'cli') {
    if (isset($argv[1]) && isset($argv[2])) {
        $keyId = $argv[1];
        $keySecret = $argv[2];
    }
} else {
    // Get from GET parameters (for browser testing)
    $keyId = $_GET['key_id'] ?? null;
    $keySecret = $_GET['key_secret'] ?? null;
}

if (!$keyId || !$keySecret) {
    die("Usage: Provide RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET\n");
}

// Update backend .env file
$envPath = __DIR__ . '/.env';
$envContent = "RAZORPAY_KEY_ID=$keyId\nRAZORPAY_KEY_SECRET=$keySecret\n";

if (file_put_contents($envPath, $envContent) === false) {
    die("Failed to write to .env file\n");
}

echo "✓ Updated backend/.env file\n";

// Update frontend .env.local file
$frontendEnvPath = __DIR__ . '/../frontend/.env.local';
$frontendEnvContent = "VITE_RAZORPAY_KEY=$keyId\n";

if (file_put_contents($frontendEnvPath, $frontendEnvContent) === false) {
    die("Failed to write to frontend/.env.local file\n");
}

echo "✓ Updated frontend/.env.local file\n";
echo "\nCredentials updated successfully!\n";
echo "Key ID: $keyId\n";
echo "Key Secret: " . substr($keySecret, 0, 10) . "...\n";
echo "\n⚠️  IMPORTANT: Restart your frontend dev server (npm run dev) for changes to take effect!\n";







