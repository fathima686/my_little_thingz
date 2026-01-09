<?php
// Create a test image upload for custom request
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>Creating Test Image Upload</h2>";
    
    // First, ensure we have a custom request to work with
    $requests = $pdo->query("SELECT id, title FROM custom_requests ORDER BY id DESC LIMIT 1")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($requests)) {
        echo "No custom requests found. Creating one...<br>";
        
        $orderId = 'CR-' . date('Ymd') . '-TEST001';
        $stmt = $pdo->prepare("
            INSERT INTO custom_requests (
                order_id, customer_name, customer_email, title, description, 
                status, source, created_at
            ) VALUES (?, ?, ?, ?, ?, 'pending', 'form', NOW())
        ");
        
        $stmt->execute([
            $orderId,
            'Test Customer',
            'test@example.com',
            'Test Custom Request with Image',
            'This is a test request to verify image upload functionality'
        ]);
        
        $requestId = $pdo->lastInsertId();
        echo "Created test request with ID: $requestId<br>";
    } else {
        $requestId = $requests[0]['id'];
        echo "Using existing request ID: $requestId - {$requests[0]['title']}<br>";
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/uploads/custom-requests/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "Created upload directory: $uploadDir<br>";
    } else {
        echo "Upload directory exists: $uploadDir<br>";
    }
    
    // Create a simple test image (1x1 pixel PNG)
    $testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAI9jU77yQAAAABJRU5ErkJggg==');
    $testImageFilename = 'cr_' . $requestId . '_' . date('Ymd_His') . '_test.png';
    $testImagePath = $uploadDir . $testImageFilename;
    
    if (file_put_contents($testImagePath, $testImageData)) {
        echo "Created test image: $testImageFilename<br>";
        
        // Insert image record into database
        $imageUrl = 'uploads/custom-requests/' . $testImageFilename;
        $insertStmt = $pdo->prepare("
            INSERT INTO custom_request_images 
            (request_id, image_url, filename, original_filename, file_size, mime_type, uploaded_by) 
            VALUES (?, ?, ?, ?, ?, ?, 'customer')
        ");
        
        $insertStmt->execute([
            $requestId,
            $imageUrl,
            $testImageFilename,
            'test-reference-image.png',
            strlen($testImageData),
            'image/png'
        ]);
        
        $imageId = $pdo->lastInsertId();
        echo "Inserted image record with ID: $imageId<br>";
        
        // Test the API response
        echo "<br><h3>Testing API Response</h3>";
        $baseUrl = "http://localhost/my_little_thingz/backend/";
        
        $imageStmt = $pdo->prepare("
            SELECT image_url, filename, original_filename, uploaded_at 
            FROM custom_request_images 
            WHERE request_id = ? 
            ORDER BY uploaded_at ASC
        ");
        $imageStmt->execute([$requestId]);
        $dbImages = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Images found in database for request $requestId:<br>";
        foreach ($dbImages as $img) {
            $fullPath = __DIR__ . "/" . $img["image_url"];
            $exists = file_exists($fullPath) ? "EXISTS" : "MISSING";
            echo "&nbsp;&nbsp;URL: " . $baseUrl . $img["image_url"] . " ($exists)<br>";
            echo "&nbsp;&nbsp;Filename: {$img['filename']}<br>";
            echo "&nbsp;&nbsp;Original: {$img['original_filename']}<br>";
            echo "&nbsp;&nbsp;Uploaded: {$img['uploaded_at']}<br><br>";
        }
        
        echo "<br><strong>Success!</strong> Test image created and database record inserted.<br>";
        echo "You can now test the admin dashboard to see if it shows the actual image instead of placeholder.<br>";
        
    } else {
        echo "Failed to create test image file<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>