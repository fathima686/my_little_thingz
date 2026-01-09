<?php
// Test images for all requests that have them
echo "<h1>🧪 Testing Images for All Requests</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Get all requests that have images
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
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>📊 Testing " . count($requestsWithImages) . " Requests with Images</h2>";
    
    $baseUrl = "http://localhost/my_little_thingz/backend/";
    $totalImages = 0;
    $workingImages = 0;
    
    foreach ($requestsWithImages as $request) {
        echo "<div style='background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 10px;'>";
        echo "<h3>🔍 Request: {$request['order_id']}</h3>";
        echo "<p><strong>Title:</strong> {$request['title']}</p>";
        echo "<p><strong>Customer:</strong> {$request['customer_name']}</p>";
        echo "<p><strong>Expected Images:</strong> {$request['image_count']}</p>";
        
        // Get images using the FIXED API logic
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
                $totalImages++;
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
                
                if ($fileExists) {
                    $workingImages++;
                    $status = "✅";
                    $statusColor = "#28a745";
                    $fileSize = filesize($actualPath);
                    $fileType = mime_content_type($actualPath);
                    $sizeInfo = " (" . number_format($fileSize / 1024, 1) . " KB, $fileType)";
                } else {
                    $status = "❌";
                    $statusColor = "#dc3545";
                    $sizeInfo = " (File not found)";
                }
                
                echo "<div style='background: white; padding: 10px; margin: 10px 0; border-radius: 5px; border-left: 3px solid $statusColor;'>";
                echo "<p><strong>$status $originalName</strong>$sizeInfo</p>";
                echo "<p><strong>URL:</strong> <a href='{$baseUrl}{$imagePath}' target='_blank'>{$baseUrl}{$imagePath}</a></p>";
                echo "<p><strong>Uploaded:</strong> {$img['uploaded_at']}</p>";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error loading images: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    // Summary
    echo "<h2>📊 Complete Summary</h2>";
    
    echo "<div style='background: #e8f4fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h4 style='color: #0c5460;'>🎯 Image Status Across All Requests:</h4>";
    echo "<p style='color: #0c5460;'>📊 <strong>" . count($requestsWithImages) . "</strong> requests have images</p>";
    echo "<p style='color: #0c5460;'>📷 <strong>$totalImages</strong> total images found</p>";
    echo "<p style='color: #0c5460;'>✅ <strong>$workingImages</strong> images are accessible</p>";
    echo "<p style='color: #0c5460;'>❌ <strong>" . ($totalImages - $workingImages) . "</strong> images have issues</p>";
    
    $successRate = $totalImages > 0 ? round(($workingImages / $totalImages) * 100, 1) : 0;
    echo "<p style='color: #0c5460;'>📈 <strong>Success Rate: {$successRate}%</strong></p>";
    echo "</div>";
    
    if ($successRate >= 80) {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #28a745;'>";
        echo "<h4 style='color: #155724;'>🎉 EXCELLENT! Images Working Well</h4>";
        echo "<p style='color: #155724;'>✅ Most images are working correctly</p>";
        echo "<p style='color: #155724;'>✅ Admin dashboard should display images properly</p>";
        echo "<p style='color: #155724;'>✅ Image mapping fix was successful</p>";
        echo "</div>";
    } elseif ($successRate >= 50) {
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #ffc107;'>";
        echo "<h4 style='color: #856404;'>⚠️ PARTIAL SUCCESS</h4>";
        echo "<p style='color: #856404;'>Some images are working, but some files may be missing</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #dc3545;'>";
        echo "<h4 style='color: #721c24;'>❌ ISSUES REMAIN</h4>";
        echo "<p style='color: #721c24;'>Many images are still not accessible</p>";
        echo "</div>";
    }
    
    // Test links
    echo "<h2>🔗 Test the Admin Dashboard</h2>";
    
    echo "<div style='background: #e8f4fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<p><strong>Now test the admin dashboard to see all images:</strong></p>";
    echo "<ul>";
    echo "<li><a href='api/admin/custom-requests-database-only.php' target='_blank' style='color: #007cba; font-weight: bold;'>📊 Test Admin API</a> - Should return images for all requests</li>";
    echo "<li><a href='../frontend/admin/custom-requests-dashboard.html' target='_blank' style='color: #007cba; font-weight: bold;'>👨‍💼 Open Admin Dashboard</a> - Images should display for all requests</li>";
    echo "</ul>";
    echo "</div>";
    
    // Show which requests have the most images
    echo "<h2>📷 Requests with Most Images</h2>";
    
    $topRequests = array_slice(array_reverse(array_sort($requestsWithImages, function($a, $b) {
        return $a['image_count'] <=> $b['image_count'];
    })), 0, 5);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Order ID</th><th>Title</th><th>Customer</th><th>Image Count</th></tr>";
    
    foreach ($topRequests as $req) {
        echo "<tr style='background: #e8f5e8;'>";
        echo "<td><strong>{$req['order_id']}</strong></td>";
        echo "<td>" . substr($req['title'], 0, 40) . "...</td>";
        echo "<td>{$req['customer_name']}</td>";
        echo "<td><strong>{$req['image_count']}</strong> images</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #dc3545;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Error Occurred</h3>";
    echo "<p style='color: #721c24;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

// Helper function for sorting
function array_sort($array, $callback) {
    usort($array, $callback);
    return $array;
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
    padding: 10px;
    text-align: left;
    border: 1px solid #ddd;
}

th {
    background: #f8f9fa;
    font-weight: 600;
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