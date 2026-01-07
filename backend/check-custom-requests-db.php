<?php
// Check Custom Requests Database
header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== CUSTOM REQUESTS DATABASE CHECK ===\n\n";
    
    // Check if table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'custom_requests'");
    if ($tableCheck->rowCount() === 0) {
        echo "❌ Table 'custom_requests' does not exist!\n";
        echo "Creating table...\n";
        
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
        
        echo "✅ Table created successfully!\n\n";
    } else {
        echo "✅ Table 'custom_requests' exists\n\n";
    }
    
    // Check table structure
    echo "=== TABLE STRUCTURE ===\n";
    $structure = $pdo->query("DESCRIBE custom_requests");
    while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-20s %-20s %-10s %-10s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key']
        );
    }
    echo "\n";
    
    // Count total records
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM custom_requests");
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "=== RECORD COUNT ===\n";
    echo "Total records: $total\n\n";
    
    if ($total > 0) {
        // Show all records
        echo "=== ALL RECORDS ===\n";
        $stmt = $pdo->query("SELECT * FROM custom_requests ORDER BY created_at DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "ID: {$row['id']}\n";
            echo "Order ID: {$row['order_id']}\n";
            echo "Customer: {$row['customer_name']} ({$row['customer_email']})\n";
            echo "Title: {$row['title']}\n";
            echo "Status: {$row['status']}\n";
            echo "Priority: {$row['priority']}\n";
            echo "Created: {$row['created_at']}\n";
            echo "---\n";
        }
        
        // Count by status
        echo "\n=== COUNT BY STATUS ===\n";
        $statusStmt = $pdo->query("
            SELECT status, COUNT(*) as count 
            FROM custom_requests 
            GROUP BY status 
            ORDER BY count DESC
        ");
        while ($row = $statusStmt->fetch(PDO::FETCH_ASSOC)) {
            echo "{$row['status']}: {$row['count']}\n";
        }
    } else {
        echo "No records found in custom_requests table.\n";
        echo "Adding sample data...\n\n";
        
        // Add sample data
        $sampleData = [
            [
                'CR-' . date('Ymd') . '-001',
                1,
                'Alice Johnson',
                'alice@example.com',
                'Custom Wedding Gift',
                'Wedding',
                'Need a personalized wedding gift for my best friend',
                'Heart-shaped, gold color, engraved names',
                '2026-03-15',
                'high',
                'pending'
            ],
            [
                'CR-' . date('Ymd') . '-002',
                2,
                'Bob Smith',
                'bob@example.com',
                'Birthday Surprise',
                'Birthday',
                'Custom birthday gift for my daughter',
                'Pink theme, unicorn design, age 8',
                '2026-02-20',
                'medium',
                'submitted'
            ]
        ];
        
        $insertStmt = $pdo->prepare("
            INSERT INTO custom_requests (
                order_id, customer_id, customer_name, customer_email, 
                title, occasion, description, requirements, deadline, priority, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sampleData as $data) {
            $insertStmt->execute($data);
            echo "✅ Added sample request: {$data[4]}\n";
        }
        
        echo "\nSample data added successfully!\n";
        echo "Total records now: " . $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>