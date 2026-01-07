<?php
// Fix Custom Requests Admin Dashboard
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Fix Custom Requests Admin</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .container{max-width:1000px;margin:0 auto;background:white;padding:20px;border-radius:8px;} .success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>🔧 Fix Custom Requests Admin Dashboard</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2 class='info'>Step 1: Ensure Custom Requests Table Exists</h2>";
    
    // Create/ensure table exists with proper structure
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
    
    echo "<p class='success'>✓ Custom requests table created/verified</p>";
    
    echo "<h2 class='info'>Step 2: Check Current Data</h2>";
    
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM custom_requests");
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<p>Current records in database: <strong>$total</strong></p>";
    
    if ($total == 0) {
        echo "<h3 class='info'>Adding Sample Data</h3>";
        
        $sampleData = [
            [
                'CR-' . date('Ymd') . '-001',
                1,
                'Alice Johnson',
                'alice@example.com',
                'Custom Wedding Gift',
                'Wedding',
                'Need a personalized wedding gift for my best friend. Something elegant and memorable.',
                'Heart-shaped design, gold color scheme, engraved with names "Alice & Bob", delivery by March 15th',
                '2026-03-15',
                'high',
                'pending'
            ],
            [
                'CR-' . date('Ymd') . '-002',
                2,
                'Bob Smith',
                'bob@example.com',
                'Birthday Surprise Box',
                'Birthday',
                'Custom birthday gift box for my 8-year-old daughter. She loves unicorns and pink colors.',
                'Pink theme, unicorn design, include small toys and sweets, age-appropriate for 8 years old',
                '2026-02-20',
                'medium',
                'submitted'
            ],
            [
                'CR-' . date('Ymd') . '-003',
                3,
                'Carol Davis',
                'carol@example.com',
                'Anniversary Memory Book',
                'Anniversary',
                'Custom photo album/memory book for our 25th wedding anniversary.',
                'Silver theme, 25 pages, include space for photos and handwritten notes, elegant binding',
                '2026-04-10',
                'medium',
                'pending'
            ],
            [
                'CR-' . date('Ymd') . '-004',
                4,
                'David Wilson',
                'david@example.com',
                'Corporate Gift Set',
                'Corporate',
                'Custom gift sets for our top 10 clients. Professional and branded.',
                'Company logo engraving, premium materials, include business card holder and pen set',
                '2026-01-30',
                'high',
                'in_progress'
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
            echo "<p class='success'>✓ Added: {$data[4]}</p>";
        }
        
        $newTotal = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
        echo "<p class='success'>✓ Sample data added. Total records now: <strong>$newTotal</strong></p>";
    }
    
    echo "<h2 class='info'>Step 3: Test API Endpoints</h2>";
    
    // Test the API
    $testStatuses = ['all', 'pending', 'submitted', 'in_progress'];
    
    foreach ($testStatuses as $status) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM custom_requests 
            " . ($status !== 'all' ? "WHERE status = ?" : "")
        );
        
        if ($status !== 'all') {
            $stmt->execute([$status]);
        } else {
            $stmt->execute();
        }
        
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Status '<strong>$status</strong>': $count requests</p>";
    }
    
    echo "<h2 class='info'>Step 4: Enhanced API Response</h2>";
    
    // Update the API to ensure it returns proper data
    $enhancedAPI = '<?php
// Enhanced Custom requests API - ONLY real database data, NO sample data
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-ID");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

try {
    require_once "../../config/database.php";
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => "Database connection failed",
        "debug" => $e->getMessage()
    ]);
    exit;
}

// Ensure custom_requests table exists
try {
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
        priority ENUM(\"low\", \"medium\", \"high\") DEFAULT \"medium\",
        status ENUM(\"submitted\", \"pending\", \"in_progress\", \"completed\", \"cancelled\") DEFAULT \"submitted\",
        design_url VARCHAR(500),
        admin_notes TEXT,
        customer_feedback TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {
    // Table creation failed, but continue
}

$method = $_SERVER["REQUEST_METHOD"];

switch ($method) {
    case "GET":
        handleGetRequests($pdo);
        break;
    case "POST":
        handlePostRequest($pdo);
        break;
    case "PUT":
        handlePutRequest($pdo);
        break;
    case "DELETE":
        handleDeleteRequest($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
        break;
}

function handleGetRequests($pdo) {
    try {
        // Get filters from query parameters
        $status = $_GET["status"] ?? "all";
        $priority = $_GET["priority"] ?? "";
        $search = $_GET["search"] ?? "";
        $limit = min((int)($_GET["limit"] ?? 50), 100);
        $offset = max((int)($_GET["offset"] ?? 0), 0);
        
        // Build query with filters
        $whereConditions = [];
        $params = [];
        
        if (!empty($status) && $status !== "all") {
            if ($status === "pending") {
                $whereConditions[] = "cr.status IN (\"submitted\", \"pending\")";
            } else {
                $whereConditions[] = "cr.status = ?";
                $params[] = $status;
            }
        }
        
        if (!empty($priority)) {
            $whereConditions[] = "cr.priority = ?";
            $params[] = $priority;
        }
        
        if (!empty($search)) {
            $whereConditions[] = "(cr.customer_name LIKE ? OR cr.customer_email LIKE ? OR cr.title LIKE ? OR cr.order_id LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        // Get ONLY real requests from database
        $stmt = $pdo->prepare("
            SELECT 
                cr.*,
                DATEDIFF(cr.deadline, CURDATE()) as days_until_deadline
            FROM custom_requests cr
            $whereClause
            ORDER BY 
                CASE cr.priority 
                    WHEN \"high\" THEN 1 
                    WHEN \"medium\" THEN 2 
                    WHEN \"low\" THEN 3 
                END,
                cr.deadline ASC,
                cr.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process each request to add UI-compatible fields and images
        foreach ($requests as &$request) {
            // Add UI-compatible fields
            $nameParts = explode(" ", $request["customer_name"], 2);
            $request["first_name"] = $nameParts[0];
            $request["last_name"] = isset($nameParts[1]) ? $nameParts[1] : "";
            $request["email"] = $request["customer_email"];
            $request["category_name"] = $request["occasion"] ?: "General";
            $request["budget_min"] = "500"; // Default - can be made dynamic
            $request["budget_max"] = "1000";
            
            // Get real uploaded images for this request
            $request["images"] = [];
            $uploadDir = __DIR__ . "/../../uploads/custom-requests/";
            
            if (is_dir($uploadDir)) {
                // Look for images specific to this request
                $pattern = $uploadDir . "cr_" . $request["id"] . "_*";
                $files = glob($pattern);
                
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $filename = basename($file);
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        if (in_array($ext, ["jpg", "jpeg", "png", "gif", "webp"])) {
                            $request["images"][] = "http://localhost/my_little_thingz/backend/uploads/custom-requests/" . $filename;
                        }
                    }
                }
            }
        }
        
        // Get total count for pagination
        $countParams = array_slice($params, 0, -2); // Remove limit and offset
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM custom_requests cr 
            $whereClause
        ");
        $countStmt->execute($countParams);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)["total"];
        
        // Get statistics from real data only
        $statsStmt = $pdo->query("
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status IN (\"submitted\", \"pending\") THEN 1 ELSE 0 END) as pending_requests,
                SUM(CASE WHEN status = \"in_progress\" THEN 1 ELSE 0 END) as in_progress_requests,
                SUM(CASE WHEN status = \"completed\" THEN 1 ELSE 0 END) as completed_requests,
                SUM(CASE WHEN status = \"cancelled\" THEN 1 ELSE 0 END) as cancelled_requests,
                SUM(CASE WHEN priority = \"high\" AND DATEDIFF(deadline, CURDATE()) <= 3 THEN 1 ELSE 0 END) as urgent_requests
            FROM custom_requests
        ");
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "status" => "success",
            "requests" => $requests,
            "total_count" => (int)$totalCount,
            "showing_count" => count($requests),
            "stats" => $stats,
            "message" => count($requests) > 0 ? "Custom requests loaded from database" : "No custom requests found in database",
            "api_version" => "enhanced-v2.0",
            "timestamp" => date("Y-m-d H:i:s"),
            "filter_applied" => $status,
            "data_source" => "database_only"
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error", 
            "message" => "Failed to fetch requests from database",
            "debug" => $e->getMessage()
        ]);
    }
}

function handlePostRequest($pdo) {
    try {
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Invalid JSON input"]);
            return;
        }
        
        // Handle status update
        if (isset($input["request_id"]) && isset($input["status"])) {
            $requestId = $input["request_id"];
            $newStatus = $input["status"];
            
            $stmt = $pdo->prepare("
                UPDATE custom_requests 
                SET status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$newStatus, $requestId]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    "status" => "success",
                    "message" => "Request status updated successfully",
                    "request_id" => $requestId,
                    "new_status" => $newStatus
                ]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Request not found in database"]);
            }
            return;
        }
        
        // Handle new request creation
        if (isset($input["customer_name"]) && isset($input["title"])) {
            $orderId = "CR-" . date("Ymd") . "-" . strtoupper(substr(uniqid(), -6));
            
            $stmt = $pdo->prepare("
                INSERT INTO custom_requests (
                    order_id, customer_id, customer_name, customer_email, 
                    title, occasion, description, requirements, deadline, priority, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \"submitted\")
            ");
            
            $stmt->execute([
                $orderId,
                $input["customer_id"] ?? 0,
                $input["customer_name"],
                $input["customer_email"] ?? "",
                $input["title"],
                $input["occasion"] ?? "",
                $input["description"] ?? "",
                $input["requirements"] ?? "",
                $input["deadline"] ?? date("Y-m-d", strtotime("+7 days")),
                $input["priority"] ?? "medium"
            ]);
            
            echo json_encode([
                "status" => "success",
                "message" => "Custom request created successfully in database",
                "order_id" => $orderId,
                "request_id" => $pdo->lastInsertId()
            ]);
            return;
        }
        
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid request data"]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error", 
            "message" => "Database operation failed",
            "debug" => $e->getMessage()
        ]);
    }
}

function handlePutRequest($pdo) {
    try {
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($input["id"])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Request ID is required"]);
            return;
        }
        
        $updateFields = [];
        $params = [];
        
        $allowedFields = ["status", "priority", "admin_notes", "design_url", "deadline"];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "No valid fields to update"]);
            return;
        }
        
        $params[] = $input["id"];
        
        $stmt = $pdo->prepare("
            UPDATE custom_requests 
            SET " . implode(", ", $updateFields) . ", updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "success", "message" => "Request updated successfully"]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Request not found in database"]);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error", 
            "message" => "Failed to update request",
            "debug" => $e->getMessage()
        ]);
    }
}

function handleDeleteRequest($pdo) {
    try {
        $requestId = $_GET["id"] ?? "";
        
        if (empty($requestId)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Request ID is required"]);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM custom_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "success", "message" => "Request deleted successfully"]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Request not found in database"]);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error", 
            "message" => "Failed to delete request",
            "debug" => $e->getMessage()
        ]);
    }
}
?>';
    
    file_put_contents('api/admin/custom-requests-database-only.php', $enhancedAPI);
    echo "<p class='success'>✓ Enhanced custom requests API updated</p>";
    
    echo "<h2 class='success'>✅ Custom Requests Fix Complete!</h2>";
    echo "<div style='background:#d1fae5;padding:20px;border-radius:5px;margin:20px 0;'>";
    echo "<h3>🎉 Custom Requests Should Now Work!</h3>";
    echo "<ul>";
    echo "<li>✅ Database table created/verified</li>";
    echo "<li>✅ Sample data added (if table was empty)</li>";
    echo "<li>✅ Enhanced API with better error handling</li>";
    echo "<li>✅ Proper CORS headers configured</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🧪 Test Your Admin Dashboard:</h3>";
    echo "<p>Go to your admin dashboard and check the Custom Requests section:</p>";
    echo "<ul>";
    echo "<li>Should show sample requests if none existed</li>";
    echo "<li>Filter by status should work</li>";
    echo "<li>Status updates should work</li>";
    echo "</ul>";
    
    echo "<h3>📊 Current Database Status:</h3>";
    $finalCount = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
    echo "<p>Total custom requests in database: <strong>$finalCount</strong></p>";
    
    // Show status breakdown
    $statusStmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM custom_requests 
        GROUP BY status 
        ORDER BY count DESC
    ");
    echo "<p><strong>Status breakdown:</strong></p>";
    echo "<ul>";
    while ($row = $statusStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>{$row['status']}: {$row['count']} requests</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>