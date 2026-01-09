<?php
// Check what's actually in the custom_request_images table
echo "<h1>🔍 Custom Request Images Database Check</h1>";

try {
    require_once 'backend/config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Check if custom_request_images table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'custom_request_images'")->fetchAll();
    if (count($tables) == 0) {
        echo "<p style='color: red;'>❌ custom_request_images table does not exist!</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ custom_request_images table exists</p>";
    
    // Show table structure
    echo "<h2>📋 Table Structure</h2>";
    $columns = $pdo->query("DESCRIBE custom_request_images")->fetchAll();
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count total images
    $totalImages = $pdo->query("SELECT COUNT(*) FROM custom_request_images")->fetchColumn();
    echo "<h2>📊 Image Data Summary</h2>";
    echo "<p><strong>Total Images:</strong> $totalImages</p>";
    
    if ($totalImages > 0) {
        // Show sample data
        echo "<h3>🔍 Sample Image Records</h3>";
        $sampleImages = $pdo->query("SELECT * FROM custom_request_images LIMIT 10")->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Request ID</th><th>Image URL</th><th>Filename</th><th>Original Filename</th><th>Uploaded At</th>";
        echo "</tr>";
        
        foreach ($sampleImages as $img) {
            echo "<tr>";
            echo "<td>{$img['id']}</td>";
            echo "<td>{$img['request_id']}</td>";
            echo "<td style='max-width: 200px; word-break: break-all;'>{$img['image_url']}</td>";
            echo "<td>{$img['filename']}</td>";
            echo "<td>{$img['original_filename']}</td>";
            echo "<td>{$img['uploaded_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check which requests have images
        echo "<h3>📷 Requests with Images</h3>";
        $requestsWithImages = $pdo->query("
            SELECT 
                cr.id,
                cr.order_id,
                cr.title,
                cr.customer_name,
                COUNT(cri.id) as image_count
            FROM custom_requests cr
            INNER JOIN custom_request_images cri ON cr.id = cri.request_id
            GROUP BY cr.id
            ORDER BY cr.id DESC
        ")->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Request ID</th><th>Order ID</th><th>Title</th><th>Customer</th><th>Image Count</th>";
        echo "</tr>";
        
        foreach ($requestsWithImages as $req) {
            echo "<tr>";
            echo "<td>{$req['id']}</td>";
            echo "<td>{$req['order_id']}</td>";
            echo "<td>" . substr($req['title'], 0, 30) . "...</td>";
            echo "<td>{$req['customer_name']}</td>";
            echo "<td><strong>{$req['image_count']}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test actual image URLs
        echo "<h3>🧪 Image URL Tests</h3>";
        $testImages = $pdo->query("SELECT * FROM custom_request_images LIMIT 5")->fetchAll();
        
        foreach ($testImages as $img) {
            echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>Image ID: {$img['id']} (Request: {$img['request_id']})</h4>";
            echo "<p><strong>Stored URL:</strong> {$img['image_url']}</p>";
            echo "<p><strong>Filename:</strong> {$img['filename']}</p>";
            echo "<p><strong>Original:</strong> {$img['original_filename']}</p>";
            
            // Test if it's a valid URL or file path
            $imageUrl = $img['image_url'];
            if (preg_match('/^https?:\/\//', $imageUrl)) {
                echo "<p style='color: blue;'>📡 This is a full URL</p>";
                echo "<p><strong>Test URL:</strong> <a href='$imageUrl' target='_blank'>$imageUrl</a></p>";
            } else {
                echo "<p style='color: orange;'>📁 This is a relative path</p>";
                $baseUrl = "http://localhost/my_little_thingz/backend/";
                $fullUrl = $baseUrl . ltrim($imageUrl, '/');
                echo "<p><strong>Full URL:</strong> <a href='$fullUrl' target='_blank'>$fullUrl</a></p>";
                
                // Check if file exists
                $possiblePaths = [
                    __DIR__ . "/backend/" . ltrim($imageUrl, '/'),
                    __DIR__ . "/" . ltrim($imageUrl, '/'),
                    __DIR__ . "/backend/uploads/" . basename($imageUrl)
                ];
                
                $fileFound = false;
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        echo "<p style='color: green;'>✅ File found at: $path</p>";
                        $fileFound = true;
                        break;
                    }
                }
                
                if (!$fileFound) {
                    echo "<p style='color: red;'>❌ File not found in any expected location</p>";
                    echo "<p><strong>Searched paths:</strong></p>";
                    echo "<ul>";
                    foreach ($possiblePaths as $path) {
                        echo "<li>$path</li>";
                    }
                    echo "</ul>";
                }
            }
            
            echo "</div>";
        }
        
    } else {
        echo "<p style='color: orange;'>⚠️ No images found in database</p>";
        
        // Check if there are any custom requests
        $totalRequests = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
        echo "<p><strong>Total Custom Requests:</strong> $totalRequests</p>";
        
        if ($totalRequests > 0) {
            echo "<p style='color: blue;'>💡 There are custom requests but no images. Images may need to be uploaded or the table may need to be populated.</p>";
        }
    }
    
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

table {
    margin: 15px 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

th, td {
    padding: 8px 12px;
    text-align: left;
    border: 1px solid #ddd;
}

th {
    background: #f8f9fa;
    font-weight: 600;
}

a {
    color: #007bff;
    text-decoration: none;
    font-weight: 600;
}

a:hover {
    text-decoration: underline;
}
</style>