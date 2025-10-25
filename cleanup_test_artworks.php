<?php
/**
 * Remove test artworks and clean up database
 */

// Database connection
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "🧹 Cleaning up test artworks and improving search...\n\n";

// Remove any test artworks
$testPatterns = [
    'test',
    'sample', 
    'demo',
    'example',
    'sweet chocolate', // Remove sweet-related test items
    'candy',
    'treat',
    'dessert'
];

$totalRemoved = 0;

foreach ($testPatterns as $pattern) {
    $sql = "SELECT id, title FROM artworks WHERE LOWER(title) LIKE '%$pattern%'";
    $result = $mysqli->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "Found {$result->num_rows} artworks with '$pattern' in title:\n";
        
        while ($row = $result->fetch_assoc()) {
            echo "  - ID: {$row['id']}, Title: {$row['title']}\n";
            
            // Delete the artwork
            $deleteSql = "DELETE FROM artworks WHERE id = {$row['id']}";
            if ($mysqli->query($deleteSql)) {
                echo "    ✅ Deleted artwork ID {$row['id']}\n";
                $totalRemoved++;
            } else {
                echo "    ❌ Failed to delete artwork ID {$row['id']}: " . $mysqli->error . "\n";
            }
        }
        echo "\n";
    }
}

// Update search suggestions to remove sweet-related terms
echo "🔧 Updating search suggestions...\n";

// Update any existing search keywords to remove sweet-related terms
$updateSql = "UPDATE search_keywords SET 
    suggestions = REPLACE(suggestions, 'sweet,', ''),
    suggestions = REPLACE(suggestions, ',sweet', ''),
    suggestions = REPLACE(suggestions, 'candy,', ''),
    suggestions = REPLACE(suggestions, ',candy', ''),
    suggestions = REPLACE(suggestions, 'treat,', ''),
    suggestions = REPLACE(suggestions, ',treat', ''),
    suggestions = REPLACE(suggestions, 'dessert,', ''),
    suggestions = REPLACE(suggestions, ',dessert', '')
    WHERE suggestions LIKE '%sweet%' OR suggestions LIKE '%candy%' OR suggestions LIKE '%treat%' OR suggestions LIKE '%dessert%'";

if ($mysqli->query($updateSql)) {
    echo "✅ Updated search suggestions\n";
} else {
    echo "⚠️ Could not update search suggestions: " . $mysqli->error . "\n";
}

echo "\n📊 Summary:\n";
echo "Total test artworks removed: $totalRemoved\n";
echo "Search suggestions updated to remove sweet-related terms\n";

echo "\n🎉 Cleanup complete!\n";
echo "Your artwork gallery is now clean and optimized!\n";

$mysqli->close();
?>


