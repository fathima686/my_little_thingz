<?php
/**
 * Clean up blank/empty images from artworks
 * This script identifies and removes artworks with blank or placeholder images
 */

// Database connection
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "🧹 Cleaning up blank/empty images from artworks...\n\n";

// Find artworks with blank or placeholder images
$blankImagePatterns = [
    'placeholder',
    'blank',
    'default',
    'no-image',
    'empty',
    'null',
    '',
    'NULL'
];

$totalFound = 0;
$totalRemoved = 0;

foreach ($blankImagePatterns as $pattern) {
    if ($pattern === '') {
        // Check for empty image_url
        $sql = "SELECT id, title, image_url FROM artworks WHERE image_url IS NULL OR image_url = ''";
    } else {
        // Check for specific patterns
        $sql = "SELECT id, title, image_url FROM artworks WHERE image_url LIKE '%$pattern%'";
    }
    
    $result = $mysqli->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "Found {$result->num_rows} artworks with '$pattern' images:\n";
        
        while ($row = $result->fetch_assoc()) {
            echo "  - ID: {$row['id']}, Title: {$row['title']}, Image: {$row['image_url']}\n";
            $totalFound++;
            
            // Ask for confirmation before deleting
            echo "    Do you want to delete this artwork? (y/n): ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            fclose($handle);
            
            if (trim(strtolower($line)) === 'y') {
                // Delete the artwork
                $deleteSql = "DELETE FROM artworks WHERE id = {$row['id']}";
                if ($mysqli->query($deleteSql)) {
                    echo "    ✅ Deleted artwork ID {$row['id']}\n";
                    $totalRemoved++;
                } else {
                    echo "    ❌ Failed to delete artwork ID {$row['id']}: " . $mysqli->error . "\n";
                }
            } else {
                echo "    ⏭️ Skipped artwork ID {$row['id']}\n";
            }
        }
        echo "\n";
    }
}

echo "📊 Summary:\n";
echo "Total artworks with blank images found: $totalFound\n";
echo "Total artworks removed: $totalRemoved\n";

// Also check for artworks with very short titles (likely test data)
echo "\n🔍 Checking for test artworks with very short titles...\n";

$sql = "SELECT id, title, image_url FROM artworks WHERE LENGTH(title) < 5 OR title LIKE '%test%' OR title LIKE '%sample%'";
$result = $mysqli->query($sql);

if ($result && $result->num_rows > 0) {
    echo "Found {$result->num_rows} potential test artworks:\n";
    
    while ($row = $result->fetch_assoc()) {
        echo "  - ID: {$row['id']}, Title: {$row['title']}, Image: {$row['image_url']}\n";
        
        echo "    Do you want to delete this test artwork? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim(strtolower($line)) === 'y') {
            $deleteSql = "DELETE FROM artworks WHERE id = {$row['id']}";
            if ($mysqli->query($deleteSql)) {
                echo "    ✅ Deleted test artwork ID {$row['id']}\n";
                $totalRemoved++;
            } else {
                echo "    ❌ Failed to delete test artwork ID {$row['id']}: " . $mysqli->error . "\n";
            }
        } else {
            echo "    ⏭️ Skipped test artwork ID {$row['id']}\n";
        }
    }
}

echo "\n🎉 Cleanup complete!\n";
echo "Total artworks removed: $totalRemoved\n";

$mysqli->close();
?>


