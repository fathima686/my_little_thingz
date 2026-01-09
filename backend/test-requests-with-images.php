<?php
// Test requests that actually have images
echo "<h1>🧪 Testing Requests with Images</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Get requests that have images
    echo "<h2>📊 Requests with Images</h2>";
    
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
        ORDER BY cr.created_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($requestsWithImages)) {
        echo "<p style='color: orange;'>⚠️ No requests with images found</p>";
    } else {
        echo "<p style='color: blue;'>📊 Found " . count($requestsWithImages) . " requests with images</p>";
        
        $baseUrl = "http://localhost/my_little_thingz/backend/";
        
        foreach ($requestsWithImages as $request) {
            echo "<div style='background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 10px;'>";
            echo "<h3>Request: {$request['order_id']} - {$request['title']}</h3>";
            echo "<p><strong>Customer:</strong> {$request['customer_name']}</p>";
            echo "<p><strong>Image Count:</strong> {$request['image_count']}</p>";
            
            // Get images using the fixed logic
            try {
                $imageStmt = $pdo->prepare("
                    SELECT image_path, uploaded_at 
                    FROM custom_request_images 
                    WHERE request_id = ? 
                    ORDER BY uploaded_at ASC
                ");
                $imageStmt->execute([$request["id"]]);
                $dbImages = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<p style='color: blue;'>📷 Loading " . count($dbImages) . " images:</p>";
                
                foreach ($dbImages as $img) {
                    $imagePath = $img["image_path"];
                    $fullPath = __DIR__ . "/../../" . $imagePath;
                    
                    // Extract filename from path
                    $filename = basename($imagePath);
                    $originalName = $filename;
                    
                    // Try to extract original name from filename if it contains underscore pattern
                    if (preg_match('/^[a-f0-9]+_(.+)$/', $filename, $matches)) {
                        $originalName = $matches[1];
                    }
                    
                    $fileExists = file_exists($fullPath);
                    $status = $fileExists ? "✅" : "❌";
                    
                    echo "<div style='background: white; padding: 10px; margin: 10px 0; border-radius: 5px; border-left: 3px solid " . ($fileExists ? "#28a745" : "#dc3545") . ";'>";
                    echo "<p><strong>$status Image:</strong> $originalName</p>";
                    echo "<p><strong>Path:</strong> $imagePath</p>";
                    echo "<p><strong>Full Path:</strong> $fullPath</p>";
                    echo "<p><strong>URL:</strong> <a href='{$baseUrl}{$imagePath}' target='_blank'>{$baseUrl}{$imagePath}</a></p>";
                    echo "<p><strong>Uploaded:</strong> {$img['uploaded_at']}</p>";
                    
                    if ($fileExists) {
                        echo "<p style='color: green;'>✅ File exists and should display in dashboard</p>";
                        
                        // Try to get file info
                        $fileSize = filesize($fullPath);
                        $fileType = mime_content_type($fullPath);
                        echo "<p><strong>File Size:</strong> " . number_format($fileSize / 1024, 2) . " KB</p>";
                        echo "<p><strong>File Type:</strong> $fileType</p>";
                        
                    } else {
                        echo "<p style='color: red;'>❌ File not found - image won't display</p>";
                        
                        // Check if file exists in different location
                        $altPath1 = __DIR__ . "/../" . $imagePath;
                        $altPath2 = __DIR__ . "/" . $imagePath;
                        
                        if (file_exists($altPath1)) {
                            echo "<p style='color: orange;'>⚠️ File found at: $altPath1</p>";
                        } elseif (file_exists($altPath2)) {
                            echo "<p style='color: orange;'>⚠️ File found at: $altPath2</p>";
                        } else {
                            echo "<p style='color: red;'>❌ File not found in any expected location</p>";
                        }
                    }
                    echo "</div>";
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error loading images: " . $e->getMessage() . "</p>";
            }
            
            echo "</div>";
        }
        
        // Summary
        echo "<h2>📋 Summary</h2>";
        
        $totalImages = array_sum(array_column($requestsWithImages, 'image_count'));
        
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #28a745;'>";
        echo "<h4 style='color: #155724;'>🎯 Image Status:</h4>";
        echo "<p style='color: #155724;'>📊 <strong>" . count($requestsWithImages) . "</strong> requests have images</p>";
        echo "<p style='color: #155724;'>📷 <strong>$totalImages</strong> total images found in database</p>";
        echo "<p style='color: #155724;'>✅ Admin API should now display these images correctly</p>";
        echo "</div>";
    }
    
    // Test links
    echo "<h2>🔗 Test the Fix</h2>";
    
    echo "<div style='background: #e8f4fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<ul>";
    echo "<li><a href='api/admin/custom-requests-database-only.php' target='_blank' style='color: #007cba; font-weight: bold;'>📊 Test Admin API</a> - Should show images for requests with image_count > 0</li>";
    echo "<li><a href='../frontend/admin/custom-requests-dashboard.html' target='_blank' style='color: #007cba; font-weight: bold;'>👨‍💼 Open Admin Dashboard</a> - Images should display</li>";
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

ul {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

li {
    margin: 8px 0;
}

a {
    color: #007cba;
    text-decoration: none;
    font-weight: 600;
}

a:hover {
    text-decoration: underline;
}
</style>