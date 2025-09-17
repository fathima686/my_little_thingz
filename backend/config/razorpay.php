<?php
// Razorpay configuration. Prefer loading from environment variables when available.
// Ensure you set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in environment or hardcode for local tests.

$razorpay = [
    'key_id' => getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_RGXWGOBliVCIpU',
    'key_secret' => getenv('RAZORPAY_KEY_SECRET') ?: '9Q49llzcN0kLD3021OoSstOp',
    'currency' => 'INR',
];

return $razorpay;