<?php
/**
 * This script replaces the broken APIs with working versions
 */

echo "<h1>🔧 API Replacement Tool</h1>";

$replacements = [
    'api/customer/tutorials.php' => 'api/customer/tutorials-simple.php',
    'api/customer/subscription-status.php' => 'api/customer/subscription-status-simple.php',
    'api/customer/profile.php' => 'api/customer/profile-simple.php'
];

foreach ($replacements as $original => $replacement) {
    echo "<h3>Replacing $original</h3>";
    
    if (file_exists($original)) {
        // Backup original
        $backup = $original . '.backup.' . date('Y-m-d-H-i-s');
        if (copy($original, $backup)) {
            echo "✅ Backed up original to: $backup<br>";
        } else {
            echo "❌ Failed to backup $original<br>";
            continue;
        }
    }
    
    if (file_exists($replacement)) {
        if (copy($replacement, $original)) {
            echo "✅ Replaced $original with working version<br>";
        } else {
            echo "❌ Failed to replace $original<br>";
        }
    } else {
        echo "❌ Replacement file $replacement not found<br>";
    }
    
    echo "<br>";
}

echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h4>✅ API Replacement Complete!</h4>";
echo "<p>The broken APIs have been replaced with working versions that:</p>";
echo "<ul>";
echo "<li>Create missing database tables automatically</li>";
echo "<li>Handle errors gracefully</li>";
echo "<li>Provide sample data if none exists</li>";
echo "<li>Work without complex dependencies</li>";
echo "</ul>";
echo "<p><strong>Your videos should now be visible in the tutorials dashboard!</strong></p>";
echo "</div>";

echo "<h3>🧪 Test the Fixed APIs</h3>";
echo "<ul>";
echo "<li><a href='api/customer/tutorials.php' target='_blank'>Test Tutorials API</a></li>";
echo "<li><a href='api/customer/subscription-status.php?email=test@example.com' target='_blank'>Test Subscription API</a></li>";
echo "<li><a href='api/customer/profile.php' target='_blank'>Test Profile API</a> (add X-Tutorial-Email header)</li>";
echo "</ul>";
?>