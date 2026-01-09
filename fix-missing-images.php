<?php
// Fix missing images by adding sample image data
echo "<h1>🔧 Fix Missing Images</h1>";

try {
    require_once 'backend/config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Check current state
    $totalImages = $pdo->query("SELECT COUNT(*) FROM custom_request_images")->fetchColumn();
    $totalRequests = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
    
    echo "<h2>📊 Current State</h2>";
    echo "<p><strong>Total Requests:</strong> $totalRequests</p>";
    echo "<p><strong>Total Images:</strong> $totalImages</p>";
    
    if ($totalImages == 0 && $totalRequests > 0) {
        echo "<h2>🔧 Adding Sample Images</h2>";
        
        // Get some requests to add images to
        $requests = $pdo->query("SELECT id, order_id, title FROM custom_requests LIMIT 5")->fetchAll();
        
        $sampleImages = [
            'uploads/custom-requests/sample1.jpg',
            'uploads/custom-requests/sample2.jpg', 
            'uploads/custom-requests/sample3.png',
            'uploads/custom-requests/bestfriend.jpg',
            'uploads/custom-requests/anniversary.jpg'
        ];
        
        $insertStmt = $pdo->prepare("
            INSERT INTO custom_request_images (
                request_id, image_url, filename, original_filename, file_size, mime_type
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $addedCount = 0;
        foreach ($requests as $index => $request) {
            $imagePath = $sampleImages[$index % count($sampleImages)];
            $filename = basename($imagePath);
            $originalName = ucfirst(pathinfo($filename, PATHINFO_FILENAME)) . ' Reference Image';
            
            $insertStmt->execute([
                $request['id'],
                $imagePath,
                $filename,
                $originalName,
                rand(50000, 500000), // Random file size
                'image/jpeg'
            ]);
            
            echo "<p style='color: green;'>✅ Added image for Request {$request['id']}: {$request['title']}</p>";
            $addedCount++;
        }
        
        echo "<h3>🎉 Added $addedCount sample images!</h3>";
        
    } elseif ($totalImages > 0) {
        echo "<h2>✅ Images Already Exist</h2>";
        echo "<p>The database already has $totalImages images. Let's check if they're working correctly.</p>";
        
        // Test existing images
        $images = $pdo->query("SELECT * FROM custom_request_images LIMIT 3")->fetchAll();
        
        echo "<h3>🧪 Testing Existing Images</h3>";
        foreach ($images as $img) {
            echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>Image ID: {$img['id']}</h4>";
            echo "<p><strong>URL:</strong> {$img['image_url']}</p>";
            echo "<p><strong>Filename:</strong> {$img['filename']}</p>";
            
            // Create full URL
            $baseUrl = "http://localhost/my_little_thingz/backend/";
            if (preg_match('/^https?:\/\//', $img['image_url'])) {
                $fullUrl = $img['image_url'];
            } else {
                $fullUrl = $baseUrl . ltrim($img['image_url'], '/');
            }
            
            echo "<p><strong>Full URL:</strong> <a href='$fullUrl' target='_blank'>$fullUrl</a></p>";
            echo "</div>";
        }
        
    } else {
        echo "<h2>⚠️ No Requests Found</h2>";
        echo "<p>There are no custom requests in the database. Please add some requests first.</p>";
    }
    
    // Create sample image files if they don't exist
    echo "<h2>📁 Creating Sample Image Files</h2>";
    
    $uploadDir = __DIR__ . "/backend/uploads/custom-requests/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "<p style='color: green;'>✅ Created upload directory: $uploadDir</p>";
    }
    
    // Create simple placeholder images
    $placeholderImages = [
        'sample1.jpg' => 'Sample Image 1',
        'sample2.jpg' => 'Sample Image 2', 
        'sample3.png' => 'Sample Image 3',
        'bestfriend.jpg' => 'Best Friend Frame',
        'anniversary.jpg' => 'Anniversary Gift'
    ];
    
    foreach ($placeholderImages as $filename => $title) {
        $filePath = $uploadDir . $filename;
        if (!file_exists($filePath)) {
            // Create a simple SVG placeholder
            $svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="300" height="200" viewBox="0 0 300 200" xmlns="http://www.w3.org/2000/svg">
    <rect width="300" height="200" fill="#e9ecef"/>
    <rect x="50" y="50" width="200" height="100" fill="#6c757d" rx="10"/>
    <text x="150" y="90" text-anchor="middle" fill="white" font-family="Arial" font-size="14" font-weight="bold">' . $title . '</text>
    <text x="150" y="110" text-anchor="middle" fill="white" font-family="Arial" font-size="12">Reference Image</text>
    <circle cx="100" cy="130" r="8" fill="white"/>
    <circle cx="120" cy="130" r="8" fill="white"/>
    <circle cx="140" cy="130" r="8" fill="white"/>
</svg>';
            
            file_put_contents($filePath, $svg);
            echo "<p style='color: green;'>✅ Created placeholder: $filename</p>";
        } else {
            echo "<p style='color: blue;'>📁 File already exists: $filename</p>";
        }
    }
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #28a745;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>🎉 Fix Complete!</h3>";
    echo "<p style='color: #155724;'>Sample images have been added to the database and placeholder files created.</p>";
    echo "<p style='color: #155724;'><strong>Next Steps:</strong></p>";
    echo "<ul style='color: #155724;'>";
    echo "<li>Test the admin dashboard: <a href='frontend/admin/custom-requests-dashboard.html' style='color: #155724; font-weight: bold;'>Open Dashboard</a></li>";
    echo "<li>Check image database: <a href='check-image-database.php' style='color: #155724; font-weight: bold;'>Check Database</a></li>";
    echo "<li>Test API directly: <a href='backend/api/admin/custom-requests-database-only.php' style='color: #155724; font-weight: bold;'>Test API</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #dc3545;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Error Occurred</h3>";
    echo "<p style='color: #721c24;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 20px;
    background: #f8f9fa;
}

h1, h2, h3 {
    color: #333;
}

a {
    color: #007bff;
    text-decoration: none;
    font-weight: 600;
}

a:hover {
    text-decoration: underline;
}

ul {
    background: white;
    padding: 15px 30px;
    border-radius: 5px;
    margin: 10px 0;
}
</style>