<?php
/**
 * Razorpay API Keys Configuration
 * 
 * IMPORTANT: Replace these with your actual Razorpay API keys
 * Get them from: https://dashboard.razorpay.com/app/keys
 */

return [
    // Test Mode Keys (for development)
    'test' => [
        'key_id' => 'rzp_test_YOUR_KEY_ID_HERE',
        'key_secret' => 'YOUR_SECRET_KEY_HERE'
    ],
    
    // Live Mode Keys (for production)
    'live' => [
        'key_id' => 'rzp_live_YOUR_LIVE_KEY_ID_HERE',
        'key_secret' => 'YOUR_LIVE_SECRET_KEY_HERE'
    ],
    
    // Current mode: 'test' or 'live'
    'mode' => 'test',
    
    // Currency
    'currency' => 'INR'
];
?>