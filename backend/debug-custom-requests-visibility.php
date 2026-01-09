<?php
// Debug Custom Requests Visibility Issue
header("Content-Type: text/html; charset=UTF-8");

echo "<h2>🔍 Custom Requests Visibility Debug</h2>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h3>1. Database Tables Check</h3>";
    
    // Check if tables exist
    $tables = $pdo->query("SHOW TABLES LIKE 'custom_request%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "<strong>Tables found:</strong> " . implode(", ", $tables) . "<br><br>";
    
    // Check custom_requests table structure
    echo "<h3>2. Custom Requests Table Structure</h3>";
    $columns = $pdo->query("DESCRIBE custom_requests")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Check all custom requests
    echo "<h3>3. All Custom Requests in Database</h3>";
    $allRequests = $pdo->query("
        SELECT 
            id, order_id, customer_name, customer_email, title, 
            status, workflow_stage, created_at, source
        FROM custom_requests 
        ORDER BY created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allRequests)) {
        echo "<p style='color: red;'>❌ No custom requests found in database!</p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($allRequests) . " custom requests</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Order ID</th><th>Customer</th><th>Email</th><th>Title</th><th>Status</th><th>Workflow Stage</th><th>Created</th><th>Source</th></tr>";
        foreach ($allRequests as $req) {
            echo "<tr>";
            echo "<td>{$req['id']}</td>";
            echo "<td>{$req['order_id']}</td>";
            echo "<td>{$req['customer_name']}</td>";
            echo "<td>{$req['customer_email']}</td>";
            echo "<td>{$req['title']}</td>";
            echo "<td>{$req['status']}</td>";
            echo "<td>{$req['workflow_stage']}</td>";
            echo "<td>{$req['created_at']}</td>";
            echo "<td>{$req['source']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
    // Test the old admin API
    echo "<h3>4. Testing Old Admin API</h3>";
    try {
        $oldApiUrl = "http://localhost/my_little_thingz/backend/api/admin/custom-requests-database-only.php";
        $oldApiResponse = file_get_contents($oldApiUrl);
        $oldApiData = json_decode($oldApiResponse, true);
        
        if ($oldApiData && isset($oldApiData['status'])) {
            echo "<p style='color: green;'>✅ Old API Response: {$oldApiData['status']}</p>";
            if (isset($oldApiData['requests'])) {
                echo "<p>Old API found " . count($oldApiData['requests']) . " requests</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Old API failed or returned invalid JSON</p>";
            echo "<pre>" . htmlspecialchars($oldApiResponse) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Old API Error: " . $e->getMessage() . "</p>";
    }
    
    // Test the new workflow API
    echo "<h3>5. Testing New Workflow API</h3>";
    try {
        $newApiUrl = "http://localhost/my_little_thingz/backend/api/admin/workflow-manager.php?action=requests";
        $newApiResponse = file_get_contents($newApiUrl);
        $newApiData = json_decode($newApiResponse, true);
        
        if ($newApiData && isset($newApiData['status'])) {
            echo "<p style='color: green;'>✅ New API Response: {$newApiData['status']}</p>";
            if (isset($newApiData['requests'])) {
                echo "<p>New API found " . count($newApiData['requests']) . " requests</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ New API failed or returned invalid JSON</p>";
            echo "<pre>" . htmlspecialchars($newApiResponse) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ New API Error: " . $e->getMessage() . "</p>";
    }
    
    // Check for missing columns in workflow system
    echo "<h3>6. Workflow System Compatibility Check</h3>";
    $requiredColumns = ['workflow_stage', 'product_type', 'admin_id', 'started_at'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $col) {
        $exists = false;
        foreach ($columns as $dbCol) {
            if ($dbCol['Field'] === $col) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $missingColumns[] = $col;
        }
    }
    
    if (empty($missingColumns)) {
        echo "<p style='color: green;'>✅ All required workflow columns exist</p>";
    } else {
        echo "<p style='color: red;'>❌ Missing columns: " . implode(", ", $missingColumns) . "</p>";
        echo "<p><strong>Fix:</strong> Run the workflow database setup to add missing columns</p>";
    }
    
    // Check product_categories table
    echo "<h3>7. Product Categories Check</h3>";
    try {
        $categories = $pdo->query("SELECT * FROM product_categories")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($categories)) {
            echo "<p style='color: red;'>❌ No product categories found</p>";
        } else {
            echo "<p style='color: green;'>✅ Found " . count($categories) . " product categories</p>";
            foreach ($categories as $cat) {
                echo "- {$cat['name']} ({$cat['type']})<br>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Product categories table missing or error: " . $e->getMessage() . "</p>";
    }
    
    // Check recent submissions
    echo "<h3>8. Recent Customer Submissions</h3>";
    $recentRequests = $pdo->query("
        SELECT * FROM custom_requests 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recentRequests)) {
        echo "<p style='color: orange;'>⚠️ No requests submitted in the last 24 hours</p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($recentRequests) . " recent requests</p>";
        foreach ($recentRequests as $req) {
            echo "- ID {$req['id']}: {$req['title']} by {$req['customer_name']} at {$req['created_at']}<br>";
        }
    }
    
    echo "<h3>9. Recommended Actions</h3>";
    echo "<ol>";
    echo "<li><strong>If no requests found:</strong> Check if customer submission form is working</li>";
    echo "<li><strong>If requests exist but not showing in admin:</strong> Check API endpoints</li>";
    echo "<li><strong>If missing workflow columns:</strong> Run workflow database setup</li>";
    echo "<li><strong>If API errors:</strong> Check file paths and permissions</li>";
    echo "</ol>";
    
    echo "<h3>10. Quick Fix Actions</h3>";
    echo "<button onclick=\"location.href='setup-workflow-database.php'\" style='padding: 10px 20px; margin: 5px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer;'>Setup Workflow Database</button>";
    echo "<button onclick=\"location.href='api/admin/custom-requests-database-only.php'\" style='padding: 10px 20px; margin: 5px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;'>Test Old Admin API</button>";
    echo "<button onclick=\"location.href='api/admin/workflow-manager.php?action=requests'\" style='padding: 10px 20px; margin: 5px; background: #17a2b8; color: white; border: none; border-radius: 5px; cursor: pointer;'>Test New Workflow API</button>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Database Error:</strong> " . $e->getMessage() . "</p>";
}
?>