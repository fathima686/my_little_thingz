<?php
// Test script to check custom request images
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>Custom Request Images Debug</h2>";
    
    // Check if tables exist
    echo "<h3>1. Table Structure Check</h3>";
    $tables = $pdo->query("SHOW TABLES LIKE 'custom_request%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . implode(", ", $tables) . "<br><br>";
    
    // Check custom_requests table
    echo "<h3>2. Custom Requests</h3>";
    $requests = $pdo->query("SELECT id, title, customer_name, created_at FROM custom_requests ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($requests as $req) {
        echo "ID: {$req['id']} - {$req['title']} by {$req['customer_name']} ({$req['created_at']})<br>";
    }
    echo "<br>";
    
    // Check custom_request_images table
    echo "<h3>3. Custom Request Images</h3>";
    $images = $pdo->query("SELECT * FROM custom_request_images ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($images)) {
        echo "No images found in database<br>";
    } else {
        foreach ($images as $img) {
            echo "Request ID: {$img['request_id']} - {$img['filename']} ({$img['image_url']}) - {$img['uploaded_at']}<br>";
            
            // Check if file exists
            $fullPath = __DIR__ . "/" . $img['image_url'];
            $exists = file_exists($fullPath) ? "EXISTS" : "MISSING";
            echo "&nbsp;&nbsp;File status: $exists<br>";
        }
    }
    echo "<br>";
    
    // Check upload directory
    echo "<h3>4. Upload Directory Check</h3>";
    $uploadDir = __DIR__ . "/uploads/custom-requests/";
    if (is_dir($uploadDir)) {
        $files = scandir($uploadDir);
        $imageFiles = array_filter($files, function($file) {
            return !in_array($file, ['.', '..']) && preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $file);
        });
        
        if (empty($imageFiles)) {
            echo "Upload directory exists but no image files found<br>";
        } else {
            echo "Image files in upload directory:<br>";
            foreach ($imageFiles as $file) {
                $filePath = $uploadDir . $file;
                $size = filesize($filePath);
                $modified = date("Y-m-d H:i:s", filemtime($filePath));
                echo "&nbsp;&nbsp;$file (Size: $size bytes, Modified: $modified)<br>";
            }
        }
    } else {
        echo "Upload directory does not exist: $uploadDir<br>";
    }
    echo "<br>";
    
    // Test API response for first request
    echo "<h3>5. API Response Test</h3>";
    if (!empty($requests)) {
        $firstRequestId = $requests[0]['id'];
        echo "Testing API response for request ID: $firstRequestId<br>";
        
        // Simulate what the API does
        $baseUrl = "http://localhost/my_little_thingz/backend/";
        $apiImages = [];
        
        // Get images from database
        $imageStmt = $pdo->prepare("
            SELECT image_url, filename, original_filename, uploaded_at 
            FROM custom_request_images 
            WHERE request_id = ? 
            ORDER BY uploaded_at ASC
        ");
        $imageStmt->execute([$firstRequestId]);
        $dbImages = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($dbImages as $img) {
            $fullPath = __DIR__ . "/" . $img["image_url"];
            if (file_exists($fullPath)) {
                $apiImages[] = [
                    "url" => $baseUrl . $img["image_url"],
                    "filename" => $img["filename"],
                    "original_name" => $img["original_filename"],
                    "uploaded_at" => $img["uploaded_at"]
                ];
            }
        }
        
        // Fallback: Check for files in upload directory
        if (empty($apiImages)) {
            $patterns = [
                $uploadDir . "cr_" . $firstRequestId . "_*",
                $uploadDir . "request_" . $firstRequestId . "_*"
            ];
            
            foreach ($patterns as $pattern) {
                $files = glob($pattern);
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $filename = basename($file);
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        if (in_array($ext, ["jpg", "jpeg", "png", "gif", "webp", "svg"])) {
                            $apiImages[] = [
                                "url" => $baseUrl . "uploads/custom-requests/" . $filename,
                                "filename" => $filename,
                                "original_name" => $filename,
                                "uploaded_at" => date("Y-m-d H:i:s", filemtime($file))
                            ];
                        }
                    }
                }
            }
        }
        
        if (empty($apiImages)) {
            echo "No images found for request $firstRequestId - would show placeholder<br>";
        } else {
            echo "Images found for request $firstRequestId:<br>";
            foreach ($apiImages as $img) {
                echo "&nbsp;&nbsp;URL: {$img['url']}<br>";
                echo "&nbsp;&nbsp;Filename: {$img['filename']}<br>";
                echo "&nbsp;&nbsp;Original: {$img['original_name']}<br>";
                echo "&nbsp;&nbsp;Uploaded: {$img['uploaded_at']}<br><br>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>