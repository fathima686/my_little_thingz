<?php
// Test the fixed image API
echo "<h1>🧪 Testing Fixed Image API</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Simulate the API call for a few requests
    echo "<h2>📊 Testing Image Loading for Recent Requests</h2>";
    
    $requests = $pdo->query("
        SELECT id, order_id, title, customer_name 
        FROM custom_requests 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $baseUrl = "http://localhost/my_little_thingz/backend/";
    
    foreach ($requests as $request) {
        echo "<div style='background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 10px;'>";
        echo "<h3>Request: {$request['order_id']} - {$request['title']}</h3>";
        echo "<p><strong>Customer:</strong> {$request['customer_name']}</p>";
        
        // Get images using the fixed logic
        $request["images"] = [];
        
        try {
            $imageStmt = $pdo->prepare("
                SELECT image_path, uploaded_at 
                FROM custom_request_images 
                WHERE request_id = ? 
                ORDER BY uploaded_at ASC
            ");
            $imageStmt->execute([$request["id"]]);
            $dbImages = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($dbImages)) {
                echo "<p style='color: orange;'>⚠️ No images found for this request</p>";
            } else {
                echo "<p style='color: blue;'>📷 Found " . count($dbImages) . " images:</p>";
                
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
                    } else {
                        echo "<p style='color: red;'>❌ File not found - image won't display</p>";
                    }
                    echo "</div>";
                    
                    $request["images"][] = [
                        "url" => $baseUrl . $imagePath,
                        "filename" => $filename,
                        "original_name" => $originalName,
                        "uploaded_at" => $img["uploaded_at"],
                        "file_exists" => $fileExists
                    ];
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error loading images: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    // Test the actual API endpoint
    echo "<h2>🔗 API Endpoint Test</h2>";
    
    echo "<div style='background: #e8f4fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h4>Test the fixed API:</h4>";
    echo "<ul>";
    echo "<li><a href='api/admin/custom-requests-database-only.php' target='_blank' style='color: #007cba; font-weight: bold;'>📊 Test Admin API</a> - Should now show images correctly</li>";
    echo "<li><a href='../frontend/admin/custom-requests-dashboard.html' target='_blank' style='color: #007cba; font-weight: bold;'>👨‍💼 Open Admin Dashboard</a> - Images should display</li>";
    echo "</ul>";
    echo "</div>";
    
    // Summary
    echo "<h2>📋 Summary</h2>";
    
    $totalRequests = count($requests);
    $requestsWithImages = 0;
    $totalImages = 0;
    $workingImages = 0;
    
    foreach ($requests as $request) {
        if (!empty($request["images"])) {
            $requestsWithImages++;
            $totalImages += count($request["images"]);
            foreach ($request["images"] as $img) {
                if ($img["file_exists"]) {
                    $workingImages++;
                }
            }
        }
    }
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #28a745;'>";
    echo "<h4 style='color: #155724;'>🎯 Image Fix Results:</h4>";
    echo "<p style='color: #155724;'>✅ Fixed column mapping: <code>image_path</code> instead of <code>image_url</code></p>";
    echo "<p style='color: #155724;'>✅ Added filename extraction from image paths</p>";
    echo "<p style='color: #155724;'>✅ Added file existence checking</p>";
    echo "<p style='color: #155724;'>📊 <strong>$requestsWithImages</strong> out of <strong>$totalRequests</strong> requests have images</p>";
    echo "<p style='color: #155724;'>📷 <strong>$workingImages</strong> out of <strong>$totalImages</strong> images are accessible</p>";
    echo "</div>";
    
    if ($workingImages < $totalImages) {
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #ffc107;'>";
        echo "<h4 style='color: #856404;'>⚠️ Some Images Not Found</h4>";
        echo "<p style='color: #856404;'>Some image files are missing from the server. This could be due to:</p>";
        echo "<ul style='color: #856404;'>";
        echo "<li>Files were moved or deleted</li>";
        echo "<li>Incorrect file paths in database</li>";
        echo "<li>Upload directory permissions</li>";
        echo "</ul>";
        echo "</div>";
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
</style>