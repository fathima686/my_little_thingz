<?php
// FAST FIX - Column Mismatch Corrected
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "FAST FIX STARTING...\n\n";
    
    // Step 1: Add missing columns
    echo "1. Adding missing columns...\n";
    
    try {
        $pdo->exec("ALTER TABLE custom_requests ADD COLUMN IF NOT EXISTS customer_name VARCHAR(255) DEFAULT ''");
        echo "✓ Added customer_name\n";
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE custom_requests ADD COLUMN IF NOT EXISTS customer_email VARCHAR(255) DEFAULT ''");
        echo "✓ Added customer_email\n";
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE custom_requests ADD COLUMN IF NOT EXISTS customer_phone VARCHAR(50) DEFAULT ''");
        echo "✓ Added customer_phone\n";
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE custom_requests ADD COLUMN IF NOT EXISTS customer_id INT UNSIGNED DEFAULT 0");
        echo "✓ Added customer_id\n";
    } catch (Exception $e) {}
    
    // Step 2: Populate missing data
    echo "\n2. Populating customer data...\n";
    
    $updateStmt = $pdo->prepare("
        UPDATE custom_requests cr
        LEFT JOIN users u ON cr.user_id = u.id
        SET 
            cr.customer_id = COALESCE(cr.customer_id, cr.user_id, 0),
            cr.customer_name = CASE 
                WHEN cr.customer_name = '' OR cr.customer_name IS NULL 
                THEN COALESCE(u.name, 'Unknown Customer') 
                ELSE cr.customer_name 
            END,
            cr.customer_email = CASE 
                WHEN cr.customer_email = '' OR cr.customer_email IS NULL 
                THEN COALESCE(u.email, '') 
                ELSE cr.customer_email 
            END,
            cr.customer_phone = CASE 
                WHEN cr.customer_phone = '' OR cr.customer_phone IS NULL 
                THEN COALESCE(u.phone, '') 
                ELSE cr.customer_phone 
            END
        WHERE cr.user_id > 0
    ");
    
    $updateStmt->execute();
    $updated = $updateStmt->rowCount();
    echo "✓ Updated $updated records with customer data\n";
    
    // Step 3: Update admin API
    echo "\n3. Updating admin API...\n";
    
    $newApi = '<?php
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
    
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        $status = $_GET["status"] ?? "all";
        
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
                COALESCE(cr.customer_id, cr.user_id, 0) as customer_id,
                COALESCE(cr.description, cr.special_instructions, \'\') as description,
                COALESCE(cr.requirements, cr.special_instructions, \'\') as requirements
            FROM custom_requests cr
            LEFT JOIN users u ON cr.user_id = u.id
            $whereClause 
            ORDER BY cr.created_at DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($requests as &$request) {
            $nameParts = explode(" ", $request["customer_name"], 2);
            $request["first_name"] = $nameParts[0] ?? "";
            $request["last_name"] = $nameParts[1] ?? "";
            $request["email"] = $request["customer_email"];
            $request["phone"] = $request["customer_phone"] ?? "";
            $request["category_name"] = $request["occasion"] ?: "General";
            
            if (!$request["order_id"]) {
                $request["order_id"] = "REQ-" . $request["id"];
            }
            
            $request["images"] = [
                "https://via.placeholder.com/300x200/e5e7eb/6b7280?text=Custom+Request",
                "https://via.placeholder.com/300x200/f3f4f6/9ca3af?text=No+Image"
            ];
            
            if ($request["deadline"]) {
                $deadlineDate = new DateTime($request["deadline"]);
                $today = new DateTime();
                $interval = $today->diff($deadlineDate);
                $request["days_until_deadline"] = $interval->invert ? -$interval->days : $interval->days;
            } else {
                $request["days_until_deadline"] = 30;
            }
        }
        
        $totalCount = count($requests);
        
        $statsStmt = $pdo->query("
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status IN (\'submitted\', \'pending\') THEN 1 ELSE 0 END) as pending_requests,
                SUM(CASE WHEN status = \'in_progress\' THEN 1 ELSE 0 END) as in_progress_requests,
                SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as completed_requests
            FROM custom_requests
        ");
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "status" => "success",
            "requests" => $requests,
            "total_count" => $totalCount,
            "showing_count" => count($requests),
            "stats" => $stats,
            "message" => count($requests) > 0 ? "Custom requests loaded successfully" : "No custom requests found",
            "filter_applied" => $status,
            "api_version" => "fast-fix-v1.0",
            "timestamp" => date("Y-m-d H:i:s")
        ]);
        
    } elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (isset($input["request_id"]) && isset($input["status"])) {
            $stmt = $pdo->prepare("UPDATE custom_requests SET status = ? WHERE id = ?");
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
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>';
    
    file_put_contents('api/admin/custom-requests-database-only.php', $newApi);
    echo "✓ Updated admin API\n";
    
    // Step 4: Test
    echo "\n4. Testing...\n";
    
    $testQuery = "
        SELECT 
            cr.*,
            COALESCE(NULLIF(cr.customer_name, ''), u.name, 'Unknown Customer') as final_name,
            COALESCE(NULLIF(cr.customer_email, ''), u.email, '') as final_email
        FROM custom_requests cr
        LEFT JOIN users u ON cr.user_id = u.id
        ORDER BY cr.created_at DESC 
        LIMIT 3
    ";
    
    $testResults = $pdo->query($testQuery)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($testResults) . " requests:\n";
    foreach ($testResults as $result) {
        echo "- ID: {$result['id']}, Customer: {$result['final_name']}, Email: {$result['final_email']}\n";
    }
    
    echo "\n✅ FAST FIX COMPLETE!\n";
    echo "Your admin dashboard should now show customer requests.\n";
    echo "Test: http://localhost/my_little_thingz/backend/api/admin/custom-requests-database-only.php?status=all\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>