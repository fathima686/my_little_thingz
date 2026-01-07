<?php
// Setup database table for custom request images
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Setting up Custom Request Images Database</h2>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Create custom_requests table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS custom_requests (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(50) UNIQUE NOT NULL,
        customer_id INT UNSIGNED NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        title VARCHAR(255) NOT NULL,
        occasion VARCHAR(100),
        description TEXT,
        requirements TEXT,
        deadline DATE,
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        status ENUM('submitted', 'pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'submitted',
        design_url VARCHAR(500),
        admin_notes TEXT,
        customer_feedback TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    echo "<p style='color: green;'>✅ custom_requests table created/verified</p>";
    
    // Create custom_request_images table
    $pdo->exec("CREATE TABLE IF NOT EXISTS custom_request_images (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        request_id INT UNSIGNED NOT NULL,
        image_url VARCHAR(500) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        original_filename VARCHAR(255),
        file_size INT UNSIGNED,
        mime_type VARCHAR(100),
        upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        uploaded_by ENUM('customer', 'admin') DEFAULT 'customer',
        FOREIGN KEY (request_id) REFERENCES custom_requests(id) ON DELETE CASCADE,
        INDEX idx_request_id (request_id)
    )");
    
    echo "<p style='color: green;'>✅ custom_request_images table created</p>";
    
    // Check if we have any existing requests
    $count = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
    
    if ($count == 0) {
        echo "<p style='color: orange;'>⚠️ No custom requests found, creating sample data...</p>";
        
        // Create sample requests
        $sampleRequests = [
            [
                'CR-' . date('Ymd') . '-001',
                1,
                'John Doe',
                'john@example.com',
                'Custom Wedding Invitation',
                'Wedding',
                'Need elegant wedding invitations with gold foil accents',
                'Size: 5x7 inches, Color: Ivory and gold, Quantity: 100 pieces',
                date('Y-m-d', strtotime('+14 days')),
                'high',
                'pending'
            ],
            [
                'CR-' . date('Ymd') . '-002',
                2,
                'Sarah Smith',
                'sarah@example.com',
                'Birthday Party Decorations',
                'Birthday',
                'Custom decorations for 5-year-old birthday party',
                'Theme: Unicorns, Colors: Pink and purple, Include balloons and banners',
                date('Y-m-d', strtotime('+7 days')),
                'medium',
                'in_progress'
            ],
            [
                'CR-' . date('Ymd') . '-003',
                3,
                'Mike Johnson',
                'mike@example.com',
                'Corporate Logo Design',
                'Business',
                'Need a modern logo for tech startup',
                'Style: Minimalist, Colors: Blue and white, Vector format required',
                date('Y-m-d', strtotime('+21 days')),
                'low',
                'completed'
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO custom_requests (
                order_id, customer_id, customer_name, customer_email, 
                title, occasion, description, requirements, deadline, priority, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sampleRequests as $request) {
            $stmt->execute($request);
            echo "<span style='color: green;'>+ Created request: {$request[4]}</span><br>";
        }
    } else {
        echo "<p style='color: green;'>✅ Found $count existing custom requests</p>";
    }
    
    // Check for uploaded images and add them to the database
    $uploadDir = __DIR__ . '/uploads/custom-requests/';
    if (is_dir($uploadDir)) {
        $files = glob($uploadDir . '*');
        $imageCount = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                
                // Try to extract request ID from filename (format: cr_ID_timestamp_hash.ext)
                if (preg_match('/^cr_(\d+)_/', $filename, $matches)) {
                    $requestId = $matches[1];
                    
                    // Check if this image is already in database
                    $checkStmt = $pdo->prepare("SELECT id FROM custom_request_images WHERE filename = ?");
                    $checkStmt->execute([$filename]);
                    
                    if (!$checkStmt->fetch()) {
                        // Add to database
                        $imageUrl = 'uploads/custom-requests/' . $filename;
                        $fileSize = filesize($file);
                        $mimeType = mime_content_type($file);
                        
                        $insertStmt = $pdo->prepare("
                            INSERT INTO custom_request_images 
                            (request_id, image_url, filename, file_size, mime_type, uploaded_by) 
                            VALUES (?, ?, ?, ?, ?, 'admin')
                        ");
                        
                        try {
                            $insertStmt->execute([$requestId, $imageUrl, $filename, $fileSize, $mimeType]);
                            $imageCount++;
                            echo "<span style='color: green;'>+ Added image: $filename (Request #$requestId)</span><br>";
                        } catch (PDOException $e) {
                            echo "<span style='color: orange;'>⚠️ Could not add $filename: Request #$requestId not found</span><br>";
                        }
                    }
                }
            }
        }
        
        echo "<p style='color: green;'>✅ Processed $imageCount images</p>";
    }
    
    // Show current status
    echo "<h3>Current Database Status:</h3>";
    
    $requestCount = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
    $imageCount = $pdo->query("SELECT COUNT(*) FROM custom_request_images")->fetchColumn();
    
    echo "<p><strong>Custom Requests:</strong> $requestCount</p>";
    echo "<p><strong>Images:</strong> $imageCount</p>";
    
    // Show sample data
    echo "<h4>Sample Requests with Images:</h4>";
    $stmt = $pdo->query("
        SELECT cr.id, cr.title, cr.customer_name, 
               COUNT(cri.id) as image_count
        FROM custom_requests cr
        LEFT JOIN custom_request_images cri ON cr.id = cri.request_id
        GROUP BY cr.id
        ORDER BY cr.id
        LIMIT 5
    ");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Title</th><th>Customer</th><th>Images</th>";
    echo "</tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['title']}</td>";
        echo "<td>{$row['customer_name']}</td>";
        echo "<td>{$row['image_count']} images</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p>The database is now ready to store and display real uploaded images.</p>";
    echo "<p><strong>Next step:</strong> Update AdminDashboard to use the real images API.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>