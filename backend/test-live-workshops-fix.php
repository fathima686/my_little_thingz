<?php
header('Content-Type: text/html');

$userEmail = 'soudhame52@gmail.com';

echo "<h1>🧪 Live Workshops API Test</h1>";
echo "<p>Testing Live Workshops API fix for: <strong>$userEmail</strong></p>";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "X-Tutorial-Email: $userEmail\r\n"
    ]
]);

echo "<h2>Testing Live Workshops API</h2>";

try {
    $url = "http://localhost/my_little_thingz/backend/api/customer/live-workshops.php";
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "<p style='color: red;'>❌ Network error or API not found</p>";
        echo "<p>URL tested: $url</p>";
        echo "<p>Check if your local server is running and the path is correct.</p>";
    } else {
        $data = json_decode($response, true);
        
        if (!$data) {
            echo "<p style='color: red;'>❌ Invalid JSON response</p>";
            echo "<pre>Raw response: " . htmlspecialchars($response) . "</pre>";
        } else {
            if ($data['status'] === 'success') {
                echo "<p style='color: green;'>✅ Live Workshops API: Working!</p>";
                
                $workshops = $data['workshops'] ?? [];
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
                echo "<h3>✅ Success Details:</h3>";
                echo "<ul>";
                echo "<li><strong>Access Level:</strong> {$data['access_level']}</li>";
                echo "<li><strong>Total Workshops:</strong> " . count($workshops) . "</li>";
                echo "<li><strong>Message:</strong> {$data['message']}</li>";
                echo "<li><strong>User Email:</strong> {$data['user_email']}</li>";
                echo "</ul>";
                
                if (!empty($workshops)) {
                    echo "<h4>Available Workshops:</h4>";
                    echo "<ul>";
                    foreach ($workshops as $workshop) {
                        echo "<li><strong>{$workshop['title']}</strong> - {$workshop['formatted_date']} at {$workshop['formatted_time']}</li>";
                    }
                    echo "</ul>";
                }
                echo "</div>";
                
            } else {
                echo "<p style='color: red;'>❌ API Error: {$data['message']}</p>";
                echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
                echo "<h4>Error Details:</h4>";
                echo "<ul>";
                echo "<li><strong>Error Code:</strong> " . ($data['error_code'] ?? 'N/A') . "</li>";
                echo "<li><strong>Current Plan:</strong> " . ($data['current_plan'] ?? 'N/A') . "</li>";
                echo "<li><strong>Required Plans:</strong> " . implode(', ', $data['required_plans'] ?? []) . "</li>";
                if (isset($data['debug_info'])) {
                    echo "<li><strong>Debug Info:</strong> {$data['debug_info']}</li>";
                }
                echo "</ul>";
                echo "</div>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: {$e->getMessage()}</p>";
}

echo "<h2>🔧 Next Steps</h2>";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px;'>";
echo "<p>If the API is working:</p>";
echo "<ol>";
echo "<li>Run your full API test again: <a href='final-test-all-apis.php'>final-test-all-apis.php</a></li>";
echo "<li>All 5 APIs should now be working (100%)</li>";
echo "<li>Apply the frontend authentication fix in your browser console</li>";
echo "<li>Refresh your React app and test Pro features</li>";
echo "</ol>";
echo "</div>";
?>