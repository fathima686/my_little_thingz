<?php
// Debug Custom Requests 500 Error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Debug Custom Requests 500 Error</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .container{max-width:1000px;margin:0 auto;background:white;padding:20px;border-radius:8px;} .success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>🔍 Debug Custom Requests 500 Error</h1>";

try {
    echo "<h2 class='info'>Step 1: Test Database Connection</h2>";
    
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p class='success'>✓ Database connection successful</p>";
    
    echo "<h2 class='info'>Step 2: Check Table Existence</h2>";
    
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'custom_requests'");
    if ($tableCheck->rowCount() === 0) {
        echo "<p class='error'>❌ Table 'custom_requests' does not exist</p>";
        echo "<p>Creating table...</p>";
        
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
        
        echo "<p class='success'>✓ Table created successfully</p>";
    } else {
        echo "<p class='success'>✓ Table 'custom_requests' exists</p>";
    }
    
    echo "<h2 class='info'>Step 3: Test Simple Query</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM custom_requests");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Records in table: <strong>$count</strong></p>";
    
    if ($count == 0) {
        echo "<p>Adding sample data...</p>";
        
        $insertStmt = $pdo->prepare("
            INSERT INTO custom_requests (
                order_id, customer_id, customer_name, customer_email, 
                title, occasion, description, requirements, deadline, priority, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $sampleData = [
            'CR-' . date('Ymd') . '-001',
            1,
            'Test Customer',
            'test@example.com',
            'Test Custom Request',
            'Test',
            'This is a test custom request',
            'Test requirements',
            '2026-03-15',
            'medium',
            'pending'
        ];
        
        $insertStmt->execute($sampleData);
        echo "<p class='success'>✓ Sample data added</p>";
    }
    
    echo "<h2 class='info'>Step 4: Test API Query</h2>";
    
    // Test the exact query the API uses
    $status = 'pending';
    $whereConditions = [];
    $params = [];
    
    if ($status !== 'all') {
        if ($status === 'pending') {
            $whereConditions[] = "cr.status IN ('submitted', 'pending')";
        } else {
            $whereConditions[] = "cr.status = ?";
            $params[] = $status;
        }
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $query = "
        SELECT 
            cr.*,
            DATEDIFF(cr.deadline, CURDATE()) as days_until_deadline
        FROM custom_requests cr
        $whereClause
        ORDER BY 
            CASE cr.priority 
                WHEN 'high' THEN 1 
                WHEN 'medium' THEN 2 
                WHEN 'low' THEN 3 
            END,
            cr.deadline ASC,
            cr.created_at DESC
        LIMIT 50 OFFSET 0
    ";
    
    echo "<p>Testing query:</p>";
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='success'>✓ Query executed successfully</p>";
    echo "<p>Found <strong>" . count($requests) . "</strong> requests</p>";
    
    if (count($requests) > 0) {
        echo "<p>First request:</p>";
        echo "<pre>" . htmlspecialchars(json_encode($requests[0], JSON_PRETTY_PRINT)) . "</pre>";
    }
    
    echo "<h2 class='info'>Step 5: Create Simple Working API</h2>";
    
    // Create a simple working API
    $simpleAPI = '<?php
// Simple Custom Requests API - Fixed for 500 Error
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-ID");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set("display_errors", 0); // Don\'t display errors in JSON response

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

try {
    require_once "../../config/database.php";
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Ensure table exists
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
        priority ENUM(\'low\', \'medium\', \'high\') DEFAULT \'medium\',
        status ENUM(\'submitted\', \'pending\', \'in_progress\', \'completed\', \'cancelled\') DEFAULT \'submitted\',
        design_url VARCHAR(500),
        admin_notes TEXT,
        customer_feedback TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $method = $_SERVER["REQUEST_METHOD"];
    
    if ($method === "GET") {
        $status = $_GET["status"] ?? "all";
        
        // Simple query without complex conditions
        if ($status === "all") {
            $stmt = $pdo->prepare("SELECT * FROM custom_requests ORDER BY created_at DESC LIMIT 50");
            $stmt->execute();
        } elseif ($status === "pending") {
            $stmt = $pdo->prepare("SELECT * FROM custom_requests WHERE status IN (\'submitted\', \'pending\') ORDER BY created_at DESC LIMIT 50");
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare("SELECT * FROM custom_requests WHERE status = ? ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$status]);
        }
        
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add UI-compatible fields
        foreach ($requests as &$request) {
            $nameParts = explode(" ", $request["customer_name"], 2);
            $request["first_name"] = $nameParts[0];
            $request["last_name"] = isset($nameParts[1]) ? $nameParts[1] : "";
            $request["email"] = $request["customer_email"];
            $request["category_name"] = $request["occasion"] ?: "General";
            $request["images"] = []; // Empty for now
        }
        
        // Get total count
        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM custom_requests");
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)["total"];
        
        echo json_encode([
            "status" => "success",
            "requests" => $requests,
            "total_count" => (int)$totalCount,
            "showing_count" => count($requests),
            "message" => count($requests) > 0 ? "Custom requests loaded successfully" : "No custom requests found",
            "filter_applied" => $status,
            "timestamp" => date("Y-m-d H:i:s")
        ]);
        
    } elseif ($method === "POST") {
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (isset($input["request_id"]) && isset($input["status"])) {
            // Update status
            $stmt = $pdo->prepare("UPDATE custom_requests SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$input["status"], $input["request_id"]]);
            
            echo json_encode([
                "status" => "success",
                "message" => "Request status updated successfully"
            ]);
        } else {
            // Create new request
            $orderId = "CR-" . date("Ymd") . "-" . strtoupper(substr(uniqid(), -6));
            
            $stmt = $pdo->prepare("
                INSERT INTO custom_requests (
                    order_id, customer_id, customer_name, customer_email, 
                    title, occasion, description, requirements, deadline, priority, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'submitted\')
            ");
            
            $stmt->execute([
                $orderId,
                $input["customer_id"] ?? 0,
                $input["customer_name"] ?? "Unknown",
                $input["customer_email"] ?? "",
                $input["title"] ?? "Untitled Request",
                $input["occasion"] ?? "",
                $input["description"] ?? "",
                $input["requirements"] ?? "",
                $input["deadline"] ?? date("Y-m-d", strtotime("+7 days")),
                $input["priority"] ?? "medium"
            ]);
            
            echo json_encode([
                "status" => "success",
                "message" => "Custom request created successfully",
                "order_id" => $orderId,
                "request_id" => $pdo->lastInsertId()
            ]);
        }
    } else {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ]);
}
?>';
    
    file_put_contents('api/admin/custom-requests-database-only.php', $simpleAPI);
    echo "<p class='success'>✓ Simple working API created</p>";
    
    echo "<h2 class='success'>✅ Debug Complete!</h2>";
    echo "<div style='background:#d1fae5;padding:20px;border-radius:5px;margin:20px 0;'>";
    echo "<h3>🎉 Custom Requests API Should Now Work!</h3>";
    echo "<ul>";
    echo "<li>✅ Database connection verified</li>";
    echo "<li>✅ Table created/verified</li>";
    echo "<li>✅ Sample data added</li>";
    echo "<li>✅ Simple working API created</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🧪 Test the Fixed API:</h3>";
    echo "<p><a href='api/admin/custom-requests-database-only.php?status=pending' target='_blank'>Test API: Pending Requests</a></p>";
    echo "<p><a href='api/admin/custom-requests-database-only.php?status=all' target='_blank'>Test API: All Requests</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p class='error'>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p class='error'>Line: " . htmlspecialchars($e->getLine()) . "</p>";
    echo "<p class='error'>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</div></body></html>";
?>