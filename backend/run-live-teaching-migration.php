<?php
// Simple script to run the live teaching migration
echo "<h2>Running Live Teaching Migration</h2>";
echo "<pre>";

// Capture output
ob_start();
include 'migrate_live_teaching.php';
$output = ob_get_clean();

echo htmlspecialchars($output);
echo "</pre>";

echo "<p><strong>Migration completed!</strong></p>";
echo "<p><a href='test-live-sessions-debug.php'>Test Live Sessions API</a></p>";
?>