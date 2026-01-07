<?php
header('Content-Type: text/html');

$userEmail = 'soudhame52@gmail.com';
$apiBase = 'http://localhost/my_little_thingz/backend/api';

echo "<h1>Quick Subscription Test</h1>";

// Test 1: Set user to Pro
echo "<h2>1. Setting user to Pro...</h2>";
$setProUrl = 'http://localhost/my_little_thingz/backend/set-user-to-pro.php';
$setProResponse = file_get_contents($setProUrl);
$setProData = json_decode($setProResponse, true);
echo "<pre>" . htmlspecialchars(json_encode($setProData, JSON_PRETTY_PRINT)) . "</pre>";

// Test 2: Check subscription status
echo "<h2>2. Checking subscription status...</h2>";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "X-Tutorial-Email: $userEmail\r\n"
    ]
]);
$statusResponse = file_get_contents("$apiBase/customer/subscription-status.php", false, $context);
$statusData = json_decode($statusResponse, true);
echo "<pre>" . htmlspecialchars(json_encode($statusData, JSON_PRETTY_PRINT)) . "</pre>";

// Test 3: Check profile
echo "<h2>3. Checking profile...</h2>";
$profileResponse = file_get_contents("$apiBase/customer/profile.php", false, $context);
$profileData = json_decode($profileResponse, true);
echo "<pre>" . htmlspecialchars(json_encode($profileData, JSON_PRETTY_PRINT)) . "</pre>";

// Test 4: Check live workshops access
echo "<h2>4. Checking live workshops access...</h2>";
$workshopsResponse = file_get_contents("$apiBase/customer/live-workshops.php", false, $context);
$workshopsData = json_decode($workshopsResponse, true);
echo "<pre>" . htmlspecialchars(json_encode($workshopsData, JSON_PRETTY_PRINT)) . "</pre>";

// Summary
echo "<h2>Summary</h2>";
$isPro = ($statusData['plan_code'] ?? '') === 'pro';
$canAccessLiveWorkshops = $statusData['feature_access']['access_levels']['can_access_live_workshops'] ?? false;

echo "<p><strong>User Plan:</strong> " . ($statusData['plan_code'] ?? 'Unknown') . "</p>";
echo "<p><strong>Can Access Live Workshops:</strong> " . ($canAccessLiveWorkshops ? 'YES' : 'NO') . "</p>";
echo "<p><strong>Profile Shows Pro:</strong> " . (($profileData['subscription']['plan_code'] ?? '') === 'pro' ? 'YES' : 'NO') . "</p>";
echo "<p><strong>Live Workshops API:</strong> " . (($workshopsData['status'] ?? '') === 'success' ? 'SUCCESS' : 'FAILED') . "</p>";

if ($isPro && $canAccessLiveWorkshops) {
    echo "<h3 style='color: green;'>✅ ALL TESTS PASSED - Pro subscription is working correctly!</h3>";
} else {
    echo "<h3 style='color: red;'>❌ TESTS FAILED - Pro subscription is not working correctly</h3>";
}
?>