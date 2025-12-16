<?php
// Razorpay configuration. Prefer loading from environment variables when available.
// Ensure you set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in environment or hardcode for local tests.

$keyId = getenv('RAZORPAY_KEY_ID');
$keySecret = getenv('RAZORPAY_KEY_SECRET');

if (!$keyId || !$keySecret) {
    throw new RuntimeException('Razorpay keys are not configured. Set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in the server environment.');
}

$razorpay = [
    'key_id' => $keyId,
    'key_secret' => $keySecret,
    'currency' => 'INR',
];

return $razorpay;