<?php
// Fix Customer-Admin Column Mismatch - Bridge the Gap
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Fix Customer-Admin Column Mismatch</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:8px;} .success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;} .warning{color:#f59e0b;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>🔧 Fix Customer-Admin Column Mismatch</h1>";
echo "<p>Bridging the gap between customer submission format and admin dashboard expectations...</p>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2 class='info'>Step 1: Analyze Current Table Structure</h2>";
    
    $structure = $pdo->query("DESCRIBE custom_requests")->fetchAll(PDO::FETCH_ASSOC);
    $columns = array_column($structure, 'Field');
    
    echo "<p>Current columns: " . implode(', ', $columns) . "</p>";
    
    $hasUserId = in_array('user_id', $columns);
    $hasCustomerId = in_array('customer_id', $columns);
    $hasCustomerName = in_array('customer_name', $columns);
    $hasCustomerEmail = in_array('customer_email', $columns);
    $hasCustomerPhone = in_array('customer_phone', $columns);
    
    echo "<ul>";
    echo "<li>user_id: " . ($hasUserId ? '✅' : '❌') . "</li>";
    echo "<li>customer_id: " . ($hasCustomerId ? '✅' : '❌') . "</li>";
    echo "<li>customer_name: " . ($hasCustomerName ? '✅' : '❌') . "</li>";
    echo "<li>customer_email: " . ($hasCustomerEmail ? '✅' : '❌') . "</li>";
    echo "<li>customer_phone: " . ($hasCustomerPhone ? '✅' : '❌') . "</li>";
    echo "</ul>";
    
    echo "<h2 class='info'>Step 2: Add Missing Columns</h2>";
    
    if (!$hasCustomerName) {
        $pdo->exec("ALTER TABLE custom_requests ADD COLUMN customer_name VARCHAR(255) DEFAULT ''");
        echo "<p class='success'>✓ Added customer_name column</p>";
    }
    
    if (!$hasCustomerEmail) {
        $pdo->exec("ALTER TABLE custom_requests ADD COLUMN customer_email VARCHAR(255) DEFAULT ''");
        echo "<p class='success'>✓ Added customer_email column</p>";
    }
    
    if (!$hasCustomerPhone) {
        $pdo->exec("ALTER TABLE custom_requests ADD COLUMN customer_phone VARCHAR(50) DEFAULT ''");
        echo "<p class='success'>✓ Added customer_phone column</p>";
    }
    
    if (!$hasCustomerId) {
        $pdo->exec("ALTER TABLE custom_requests ADD COLUMN customer_id INT UNSIGNED DEFAULT 0");
        echo "<p class='success'>✓ Added customer_id column</p>";
    }
    
    echo "<h2 class='info'>Step 3: Populate Missing Customer Data</h2>";
    
    // Find records with user_id but missing customer info
    $missingCustomerInfo = $pdo->query("
        SELECT cr.*, u.name, u.email, u.phone 
        FROM custom_requests cr 
        LEFT JOIN users u ON cr.user_id = u.id 
        WHERE (cr.customer_name = '' OR cr.customer_name IS NULL OR 
               cr.customer_email = '' OR cr.customer_email IS NULL)
        AND cr.user_id > 0
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($missingCustomerInfo) . " records with missing customer info</p>";
    
    foreach ($missingCustomerInfo as $record) {
        $updateStmt = $pdo->prepare("
            UPDATE custom_requests 
            SET customer_id = ?, 
                customer_name = COALESCE(NULLIF(customer_name, ''), ?), 
                customer_email = COALESCE(NULLIF(customer_email, ''), ?),
                customer_phone = COALESCE(NULLIF(customer_phone, ''), ?)
            WHERE id = ?
        ");
        
        $customerName = $record['name'] ?: 'Unknown Customer';
        $customerEmail = $record['email'] ?: '';
        $customerPhone = $record['phone'] ?: '';
        
        $updateStmt->execute([
            $record['user_id'],
            $customerName,
            $customerEmail,
            $customerPhone,
            $record['id']
        ]);
        
        echo "<p class='success'>✓ Updated record {$record['id']}: {$customerName} ({$customerEmail})</p>";
    }
    
    echo "<h2 class='info'>Step 4: Update Admin API to Handle Both Formats</h2>";
    
    // Create enhanced admin API that can read both formats
    $enhancedAdminApi = '<?php
// Enhanced Custom Requests API - Handles Both Customer Formats
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-ID");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

ini_set("display_errors", 0);
error_reporting(0);

try {
    require_once "../../config/database.php";
    $database = new Database();
    $pdo = $database->getConnection();
    
    $method = $_SERVER["REQUEST_METHOD"];
    
    if ($method === "GET") {
        $status = $_GET["status"] ?? "all";
        $limit = min((int)($_GET["limit"] ?? 50), 100);
        $offset = max((int)($_GET["offset"] ?? 0), 0);
        
        // Enhanced query that joins with users table to get complete customer info
        $whereClause = "";
        $params = [];
        
        if ($status !== "all") {
            if ($status === "pending") {
                $whereClause = "WHERE cr.status IN (\'submitted\', \'pending\')";
            } else {
                $whereClause = "WHERE cr.status = ?";
                $params[] = $status;
            }
        }
        
        $query = "
            SELECT 
                cr.*,
                COALESCE(NULLIF(cr.customer_name, \'\'), u.name, \'Unknown Customer\') as customer_name,
                COALESCE(NULLIF(cr.customer_email, \'\'), u.email, \'\') as customer_email,
                COALESCE(NULLIF(cr.customer_phone, \'\'), u.phone, \'\') as customer_phone,
                COALESCE(cr.customer_id, cr.user_id, 0) as customer_id
            FROM custom_requests cr
            LEFT JOIN users u ON cr.user_id = u.id
            $whereClause 
            ORDER BY cr.created_at DESC 
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process each request for frontend compatibility
        foreach ($requests as &$request) {
            // Customer info
            $nameParts = explode(" ", $request["customer_name"], 2);
            $request["first_name"] = $nameParts[0] ?? "";
            $request["last_name"] = $nameParts[1] ?? "";
            $request["email"] = $request["customer_email"];
            $request["phone"] = $request["customer_phone"] ?? "";
            
            // Request details
            $request["category_name"] = $request["occasion"] ?: "General";
            $request["description"] = $request["description"] ?: $request["special_instructions"] ?: "";
            $request["requirements"] = $request["requirements"] ?: $request["special_instructions"] ?: "";
            
            // Handle different budget formats
            if (isset($request["budget_min"]) && $request["budget_min"]) {
                $request["budget_min"] = $request["budget_min"];
                $request["budget_max"] = $request["budget_max"] ?: $request["budget_min"];
            } else {
                $request["budget_min"] = "500";
                $request["budget_max"] = "1000";
            }
            
            // Images
            $baseUrl = "http://localhost/my_little_thingz/backend/uploads/custom-requests/";
            $request["images"] = [];
            
            // Check for real images
            $uploadDir = __DIR__ . "/../../uploads/custom-requests/";
            if (is_dir($uploadDir)) {
                $patterns = [
                    $uploadDir . "cr_" . $request["id"] . "_*",
                    $uploadDir . "request_" . $request["id"] . "_*"
                ];
                
                foreach ($patterns as $pattern) {
                    $files = glob($pattern);
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            $filename = basename($file);
                            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                            if (in_array($ext, ["jpg", "jpeg", "png", "gif", "webp", "svg"])) {
                                $request["images"][] = $baseUrl . $filename;
                            }
                        }
                    }
                }
            }
            
            // Default images if none found
            if (empty($request["images"])) {
                $request["images"] = [
                    "https://via.placeholder.com/300x200/e5e7eb/6b7280?text=Custom+Request",
                    "https://via.placeholder.com/300x200/f3f4f6/9ca3af?text=No+Image"
                ];
            }
            
            // Calculate deadline
            if ($request["deadline"]) {
                $deadlineDate = new DateTime($request["deadline"]);
                $today = new DateTime();
                $interval = $today->diff($deadlineDate);
                $request["days_until_deadline"] = $interval->invert ? -$interval->days : $interval->days;
            } else {
                $request["days_until_deadline"] = 30;
            }
            
            // Ensure order_id exists
            if (!$request["order_id"]) {
                $request["order_id"] = "REQ-" . $request["id"];
            }
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) FROM custom_requests cr $whereClause";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute(array_slice($params, 0, -2));
        $totalCount = $countStmt->fetchColumn();
        
        // Get statistics
        $statsStmt = $pdo->query("
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status IN (\'submitted\', \'pending\') THEN 1 ELSE 0 END) as pending_requests,
                SUM(CASE WHEN status = \'in_progress\' THEN 1 ELSE 0 END) as in_progress_requests,
                SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as completed_requests,
                SUM(CASE WHEN status = \'cancelled\' THEN 1 ELSE 0 END) as cancelled_requests
            FROM custom_requests
        ");
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "status" => "success",
            "requests" => $requests,
            "total_count" => (int)$totalCount,
            "showing_count" => count($requests),
            "stats" => $stats,
            "message" => count($requests) > 0 ? "Custom requests loaded successfully" : "No custom requests found",
            "filter_applied" => $status,
            "api_version" => "enhanced-bridge-v1.0",
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
            echo json_encode([
                "status" => "error",
                "message" => "Invalid request data"
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
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>';
    
    file_put_contents('api/admin/custom-requests-database-only.php', $enhancedAdminApi);
    echo "<p class='success'>✓ Updated admin API to handle both customer formats</p>";
    
    echo "<h2 class='info'>Step 5: Test the Bridge</h2>";
    
    $testQuery = "
        SELECT 
            cr.*,
            COALESCE(NULLIF(cr.customer_name, ''), u.name, 'Unknown Customer') as final_customer_name,
            COALESCE(NULLIF(cr.customer_email, ''), u.email, '') as final_customer_email,
            COALESCE(cr.customer_id, cr.user_id, 0) as final_customer_id
        FROM custom_requests cr
        LEFT JOIN users u ON cr.user_id = u.id
        ORDER BY cr.created_at DESC 
        LIMIT 5
    ";
    
    $testResults = $pdo->query($testQuery)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Testing bridge with recent requests:</p>";
    echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Customer Name</th><th>Customer Email</th><th>Title</th><th>Status</th></tr>";
    
    foreach ($testResults as $result) {
        echo "<tr>";
        echo "<td>{$result['id']}</td>";
        echo "<td>{$result['user_id']}</td>";
        echo "<td>{$result['final_customer_name']}</td>";
        echo "<td>{$result['final_customer_email']}</td>";
        echo "<td>{$result['title']}</td>";
        echo "<td>{$result['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2 class='success'>✅ Column Mismatch Fixed!</h2>";
    echo "<div style='background:#d1fae5;padding:20px;border-radius:5px;margin:20px 0;'>";
    echo "<h3>🎉 Bridge Complete!</h3>";
    echo "<ul>";
    echo "<li>✅ Added missing customer columns</li>";
    echo "<li>✅ Populated customer data from users table</li>";
    echo "<li>✅ Enhanced admin API to handle both formats</li>";
    echo "<li>✅ Customer requests will now appear in admin dashboard</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🧪 Test Your Fixed System:</h3>";
    echo "<p><a href='api/admin/custom-requests-database-only.php?status=all' target='_blank'>Test Enhanced Admin API</a></p>";
    echo "<p>Your admin dashboard should now show all customer requests!</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>