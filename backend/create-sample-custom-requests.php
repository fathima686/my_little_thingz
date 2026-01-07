<?php
// Create sample custom requests for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Creating Sample Custom Requests</h2>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Create table if it doesn't exist
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
        status ENUM('submitted', 'drafted_by_admin', 'changes_requested', 'approved_by_customer', 'locked_for_production') DEFAULT 'submitted',
        design_url VARCHAR(500),
        admin_notes TEXT,
        customer_feedback TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    echo "<p style='color: green;'>✓ Table created/verified</p>";
    
    // Check if we already have sample data
    $count = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
    
    if ($count > 0) {
        echo "<p style='color: blue;'>→ Found $count existing requests</p>";
    } else {
        echo "<p>Creating sample requests...</p>";
        
        $sampleRequests = [
            [
                'order_id' => 'CR-' . date('Ymd') . '-001',
                'customer_id' => 1,
                'customer_name' => 'John Doe',
                'customer_email' => 'john@example.com',
                'title' => 'Custom Wedding Invitation',
                'occasion' => 'Wedding',
                'description' => 'Need elegant wedding invitations with gold foil accents',
                'requirements' => 'Size: 5x7 inches, Color: Ivory and gold, Quantity: 100 pieces',
                'deadline' => date('Y-m-d', strtotime('+14 days')),
                'priority' => 'high',
                'status' => 'submitted'
            ],
            [
                'order_id' => 'CR-' . date('Ymd') . '-002',
                'customer_id' => 2,
                'customer_name' => 'Sarah Smith',
                'customer_email' => 'sarah@example.com',
                'title' => 'Birthday Party Decorations',
                'occasion' => 'Birthday',
                'description' => 'Custom decorations for 5-year-old birthday party',
                'requirements' => 'Theme: Unicorns, Colors: Pink and purple, Include balloons and banners',
                'deadline' => date('Y-m-d', strtotime('+7 days')),
                'priority' => 'medium',
                'status' => 'submitted'
            ],
            [
                'order_id' => 'CR-' . date('Ymd') . '-003',
                'customer_id' => 3,
                'customer_name' => 'Mike Johnson',
                'customer_email' => 'mike@example.com',
                'title' => 'Corporate Logo Design',
                'occasion' => 'Business',
                'description' => 'Need a modern logo for tech startup',
                'requirements' => 'Style: Minimalist, Colors: Blue and white, Vector format required',
                'deadline' => date('Y-m-d', strtotime('+21 days')),
                'priority' => 'low',
                'status' => 'drafted_by_admin'
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO custom_requests (
                order_id, customer_id, customer_name, customer_email, 
                title, occasion, description, requirements, deadline, priority, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sampleRequests as $request) {
            $stmt->execute([
                $request['order_id'],
                $request['customer_id'],
                $request['customer_name'],
                $request['customer_email'],
                $request['title'],
                $request['occasion'],
                $request['description'],
                $request['requirements'],
                $request['deadline'],
                $request['priority'],
                $request['status']
            ]);
            
            echo "<span style='color: green;'>+ Created: {$request['title']}</span><br>";
        }
    }
    
    // Show current requests
    echo "<h3>Current Custom Requests:</h3>";
    $requests = $pdo->query("SELECT * FROM custom_requests ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($requests)) {
        echo "<p>No requests found.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Order ID</th><th>Customer</th><th>Title</th><th>Status</th><th>Priority</th><th>Deadline</th>";
        echo "</tr>";
        
        foreach ($requests as $req) {
            echo "<tr>";
            echo "<td>{$req['id']}</td>";
            echo "<td>{$req['order_id']}</td>";
            echo "<td>{$req['customer_name']}</td>";
            echo "<td>{$req['title']}</td>";
            echo "<td style='text-transform: capitalize;'>{$req['status']}</td>";
            echo "<td style='text-transform: capitalize;'>{$req['priority']}</td>";
            echo "<td>{$req['deadline']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p>You can now test the custom requests in the admin dashboard.</p>";
    echo "<p><a href='test-admin-auth-fix.html'>Test the API</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>