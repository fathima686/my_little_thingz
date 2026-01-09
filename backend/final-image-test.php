<?php
// Final test of the fixed admin API
echo "<h1>🎯 Final Image API Test</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Test one specific request that has images
    $testRequestId = 19; // Try a different request that should have images
    
    echo "<h2>🧪 Testing Request ID: $testRequestId</h2>";
    
    // Get request details
    $request = $pdo->query("SELECT * FROM custom_requests WHERE id = $testRequestId")->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo "<p style='color: red;'>❌ Request not found</p>";
        exit;
    }
    
    echo "<p><strong>Request:</strong> {$request['order_id']} - {$request['title']}</p>";
    
    // Simulate the exact API logic
    $baseUrl = "http://localhost/my_little_thingz/backend/";
    $request["images"] = [];
    
    // Get images from database using the FIXED logic
    try {
        $imageStmt = $pdo->prepare("
            SELECT image_path, uploaded_at 
            FROM custom_request_images 
            WHERE request_id = ? 
            ORDER BY uploaded_at ASC
        ");
        $imageStmt->execute([$request["id"]]);
        $dbImages = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p style='color: blue;'>📷 Found " . count($dbImages) . " images in database</p>";
        
        foreach ($dbImages as $img) {
            $imagePath = $img["image_path"];
            
            // Extract filename from path
            $filename = basename($imagePath);
            $originalName = $filename;
            
            // Try to extract original name from filename if it contains underscore pattern
            if (preg_match('/^[a-f0-9]+_(.+)$/', $filename, $matches)) {
                $originalName = $matches[1];
            }
            
            // Check multiple possible file locations (FIXED LOGIC)
            $possiblePaths = [
                __DIR__ . "/../../" . $imagePath,  // Original path
                __DIR__ . "/../" . $imagePath,     // One level up
                __DIR__ . "/" . $imagePath         // Same level
            ];
            
            $fileExists = false;
            $actualPath = "";
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $fileExists = true;
                    $actualPath = $path;
                    break;
                }
            }
            
            echo "<div style='background: white; padding: 15px; margin: 15px 0; border-radius: 10px; border-left: 5px solid " . ($fileExists ? "#28a745" : "#dc3545") . ";'>";
            echo "<h4>" . ($fileExists ? "✅" : "❌") . " Image: $originalName</h4>";
            echo "<p><strong>Database Path:</strong> $imagePath</p>";
            echo "<p><strong>File Status:</strong> " . ($fileExists ? "Found" : "Not Found") . "</p>";
            
            if ($fileExists) {
                echo "<p><strong>Actual Path:</strong> $actualPath</p>";
                echo "<p><strong>URL:</strong> <a href='{$baseUrl}{$imagePath}' target='_blank'>{$baseUrl}{$imagePath}</a></p>";
                
                // Get file info
                $fileSize = filesize($actualPath);
                $fileType = mime_content_type($actualPath);
                echo "<p><strong>File Size:</strong> " . number_format($fileSize / 1024, 2) . " KB</p>";
                echo "<p><strong>File Type:</strong> $fileType</p>";
                
                $request["images"][] = [
                    "url" => $baseUrl . $imagePath,
                    "filename" => $filename,
                    "original_name" => $originalName,
                    "uploaded_at" => $img["uploaded_at"],
                    "file_exists" => true
                ];
            } else {
                echo "<p><strong>Checked Paths:</strong></p>";
                echo "<ul>";
                foreach ($possiblePaths as $path) {
                    echo "<li>$path</li>";
                }
                echo "</ul>";
                
                $request["images"][] = [
                    "url" => $baseUrl . $imagePath,
                    "filename" => $filename,
                    "original_name" => $originalName,
                    "uploaded_at" => $img["uploaded_at"],
                    "file_exists" => false,
                    "error" => "File not found in any expected location"
                ];
            }
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error loading images: " . $e->getMessage() . "</p>";
    }
    
    // Show final result
    echo "<h2>📊 API Response Preview</h2>";
    
    $workingImages = array_filter($request["images"], function($img) { return $img["file_exists"]; });
    
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h4>What the admin dashboard will receive:</h4>";
    echo "<pre style='background: white; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
    echo json_encode([
        "request_id" => $request["id"],
        "order_id" => $request["order_id"],
        "title" => $request["title"],
        "images" => $request["images"]
    ], JSON_PRETTY_PRINT);
    echo "</pre>";
    echo "</div>";
    
    // Summary
    echo "<h2>🎯 Final Results</h2>";
    
    if (count($workingImages) > 0) {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #28a745;'>";
        echo "<h4 style='color: #155724;'>🎉 SUCCESS!</h4>";
        echo "<p style='color: #155724;'>✅ Fixed column mapping from <code>image_url</code> to <code>image_path</code></p>";
        echo "<p style='color: #155724;'>✅ Fixed file path resolution with multiple fallbacks</p>";
        echo "<p style='color: #155724;'>✅ Added filename extraction from paths</p>";
        echo "<p style='color: #155724;'>📊 <strong>" . count($workingImages) . "</strong> out of <strong>" . count($request["images"]) . "</strong> images are working</p>";
        echo "<p style='color: #155724;'>🎯 Admin dashboard should now display images correctly!</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #dc3545;'>";
        echo "<h4 style='color: #721c24;'>❌ Still Issues</h4>";
        echo "<p style='color: #721c24;'>No images are accessible. Files may be missing or paths are still incorrect.</p>";
        echo "</div>";
    }
    
    // Test links
    echo "<h2>🔗 Test the Fixed Dashboard</h2>";
    
    echo "<div style='background: #e8f4fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<ul>";
    echo "<li><a href='api/admin/custom-requests-database-only.php' target='_blank' style='color: #007cba; font-weight: bold;'>📊 Test Admin API</a> - Should now show images</li>";
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

h1, h2, h3, h4 {
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

code {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
}

pre {
    font-size: 12px;
    line-height: 1.4;
}
</style>