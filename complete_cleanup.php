<?php
/**
 * Complete cleanup - Remove all blank items and sweet-related content
 */

// Database connection
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "🧹 Complete cleanup - Removing all blank items and sweet-related content...\n\n";

// Remove ALL artworks with sweet-related terms
$sweetPatterns = [
    'sweet',
    'chocolate',
    'candy',
    'treat',
    'dessert',
    'nuts hamper',
    'gift basket'
];

$totalRemoved = 0;

foreach ($sweetPatterns as $pattern) {
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

// Remove any artworks with blank or empty images
echo "🔍 Checking for artworks with blank images...\n";

$blankImageSql = "SELECT id, title, image_url FROM artworks WHERE 
    image_url IS NULL OR 
    image_url = '' OR 
    image_url LIKE '%placeholder%' OR 
    image_url LIKE '%blank%' OR 
    image_url LIKE '%default%' OR
    image_url LIKE '%no-image%'";

$result = $mysqli->query($blankImageSql);

if ($result && $result->num_rows > 0) {
    echo "Found {$result->num_rows} artworks with blank images:\n";
    
    while ($row = $result->fetch_assoc()) {
        echo "  - ID: {$row['id']}, Title: {$row['title']}, Image: {$row['image_url']}\n";
        
        $deleteSql = "DELETE FROM artworks WHERE id = {$row['id']}";
        if ($mysqli->query($deleteSql)) {
            echo "    ✅ Deleted artwork ID {$row['id']}\n";
            $totalRemoved++;
        } else {
            echo "    ❌ Failed to delete artwork ID {$row['id']}: " . $mysqli->error . "\n";
        }
    }
} else {
    echo "No artworks with blank images found.\n";
}

// Remove any test or sample artworks
echo "\n🔍 Checking for test/sample artworks...\n";

$testSql = "SELECT id, title FROM artworks WHERE 
    LOWER(title) LIKE '%test%' OR 
    LOWER(title) LIKE '%sample%' OR 
    LOWER(title) LIKE '%demo%' OR 
    LOWER(title) LIKE '%example%' OR
    LENGTH(title) < 5";

$result = $mysqli->query($testSql);

if ($result && $result->num_rows > 0) {
    echo "Found {$result->num_rows} test artworks:\n";
    
    while ($row = $result->fetch_assoc()) {
        echo "  - ID: {$row['id']}, Title: {$row['title']}\n";
        
        $deleteSql = "DELETE FROM artworks WHERE id = {$row['id']}";
        if ($mysqli->query($deleteSql)) {
            echo "    ✅ Deleted artwork ID {$row['id']}\n";
            $totalRemoved++;
        } else {
            echo "    ❌ Failed to delete artwork ID {$row['id']}: " . $mysqli->error . "\n";
        }
    }
} else {
    echo "No test artworks found.\n";
}

// Show remaining artworks
echo "\n📊 Remaining artworks in database:\n";
$remainingSql = "SELECT id, title, image_url FROM artworks ORDER BY id";
$result = $mysqli->query($remainingSql);

if ($result && $result->num_rows > 0) {
    echo "Total remaining: {$result->num_rows} artworks\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - ID: {$row['id']}, Title: {$row['title']}, Image: " . ($row['image_url'] ?: 'NULL') . "\n";
    }
} else {
    echo "No artworks remaining in database.\n";
}

echo "\n📊 Summary:\n";
echo "Total artworks removed: $totalRemoved\n";

echo "\n🎉 Complete cleanup finished!\n";
echo "All blank items and sweet-related content have been removed.\n";

$mysqli->close();
?>


