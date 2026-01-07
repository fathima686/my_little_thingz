<?php
// Fix CORS headers in all admin API files to include X-Admin-Email
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fixing Admin API CORS Headers</h1>";
echo "<p>Adding X-Admin-Email to Access-Control-Allow-Headers in all admin API files...</p>";

$adminApiDir = __DIR__ . '/api/admin/';
$files = glob($adminApiDir . '*.php');

$updatedFiles = [];
$errors = [];

foreach ($files as $file) {
    $filename = basename($file);
    echo "<h3>Processing: $filename</h3>";
    
    try {
        $content = file_get_contents($file);
        
        // Check if file has CORS headers
        if (strpos($content, 'Access-Control-Allow-Headers') !== false) {
            // Pattern to match the Access-Control-Allow-Headers line
            $pattern = '/header\s*\(\s*["\']Access-Control-Allow-Headers:\s*([^"\']+)["\']\s*\)\s*;/i';
            
            if (preg_match($pattern, $content, $matches)) {
                $currentHeaders = $matches[1];
                
                // Check if X-Admin-Email is already included
                if (strpos($currentHeaders, 'X-Admin-Email') === false) {
                    // Add X-Admin-Email to the headers
                    $newHeaders = $currentHeaders . ', X-Admin-Email';
                    $newLine = 'header("Access-Control-Allow-Headers: ' . $newHeaders . '");';
                    
                    // Replace the old line with the new one
                    $newContent = preg_replace($pattern, $newLine, $content);
                    
                    // Write back to file
                    if (file_put_contents($file, $newContent)) {
                        $updatedFiles[] = $filename;
                        echo "<span style='color: green;'>✓ Updated: $currentHeaders → $newHeaders</span><br>";
                    } else {
                        $errors[] = "$filename: Failed to write file";
                        echo "<span style='color: red;'>✗ Failed to write file</span><br>";
                    }
                } else {
                    echo "<span style='color: blue;'>→ Already includes X-Admin-Email</span><br>";
                }
            } else {
                echo "<span style='color: orange;'>⚠ Has CORS headers but pattern not matched</span><br>";
            }
        } else {
            echo "<span style='color: gray;'>- No CORS headers found</span><br>";
        }
        
    } catch (Exception $e) {
        $errors[] = "$filename: " . $e->getMessage();
        echo "<span style='color: red;'>✗ Error: " . $e->getMessage() . "</span><br>";
    }
}

echo "<h2>Summary</h2>";
echo "<p><strong>Files updated:</strong> " . count($updatedFiles) . "</p>";
if (!empty($updatedFiles)) {
    echo "<ul>";
    foreach ($updatedFiles as $file) {
        echo "<li style='color: green;'>$file</li>";
    }
    echo "</ul>";
}

if (!empty($errors)) {
    echo "<p><strong>Errors:</strong></p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li style='color: red;'>$error</li>";
    }
    echo "</ul>";
}

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Refresh your React application</li>";
echo "<li>The CORS errors should be resolved</li>";
echo "<li>Check the browser console to confirm</li>";
echo "</ol>";

// Also check if custom-requests.php needs updating
echo "<h3>Checking custom-requests.php specifically</h3>";
$customRequestsFile = $adminApiDir . 'custom-requests.php';
if (file_exists($customRequestsFile)) {
    $content = file_get_contents($customRequestsFile);
    if (strpos($content, 'X-Admin-Email') !== false) {
        echo "<span style='color: green;'>✓ custom-requests.php already includes X-Admin-Email</span><br>";
    } else {
        echo "<span style='color: orange;'>⚠ custom-requests.php may need manual update</span><br>";
    }
} else {
    echo "<span style='color: red;'>✗ custom-requests.php not found</span><br>";
}
?>