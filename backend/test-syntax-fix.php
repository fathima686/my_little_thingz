<?php
// Test if the syntax error is fixed
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing FeatureAccessControl Syntax Fix</h2>";

try {
    require_once 'models/FeatureAccessControl.php';
    echo "<span style='color: green;'>✓ FeatureAccessControl.php syntax is now valid</span><br>";
    
    // Test if we can instantiate it
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $featureControl = new FeatureAccessControl($db);
    echo "<span style='color: green;'>✓ FeatureAccessControl class can be instantiated</span><br>";
    
    // Test a simple method
    $plan = $featureControl->getUserPlan(1);
    echo "<span style='color: green;'>✓ getUserPlan method works, returned: " . ($plan ?? 'null') . "</span><br>";
    
} catch (ParseError $e) {
    echo "<span style='color: red;'>✗ Parse Error: " . $e->getMessage() . "</span><br>";
} catch (Exception $e) {
    echo "<span style='color: orange;'>⚠ Runtime Error: " . $e->getMessage() . "</span><br>";
}

echo "<br><p><a href='api/customer/live-sessions.php' target='_blank'>Test Live Sessions API</a></p>";
?>