<?php
// Clean Sample Data Only - Quick Fix
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Clean Sample Data</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;} .success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;} .warning{color:#f59e0b;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>🧹 Clean Sample Data</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2 class='info'>Before Cleanup:</h2>";
    
    $beforeCount = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
    echo "<p>Total requests before cleanup: <strong>$beforeCount</strong></p>";
    
    if ($beforeCount > 0) {
        $allBefore = $pdo->query("SELECT id, customer_name, customer_email, title, created_at FROM custom_requests ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse:collapse;width:100%;margin:10px 0;'>";
        echo "<tr><th>ID</th><th>Customer</th><th>Email</th><th>Title</th><th>Type</th></tr>";
        
        $sampleEmails = [
            'alice.johnson@email.com',
            'michael.chen@email.com', 
            'sarah.williams@email.com',
            'robert.davis@email.com'
        ];
        
        foreach ($allBefore as $req) {
            $isSample = in_array($req['customer_email'], $sampleEmails) || 
                       preg_match('/CR-\d{8}-\d{3}/', $req['id']) ||
                       in_array($req['customer_name'], ['Alice Johnson', 'Michael Chen', 'Sarah Williams', 'Robert Davis']);
            
            $rowClass = $isSample ? 'style="background:#fef3c7;"' : 'style="background:#d1fae5;"';
            $type = $isSample ? 'SAMPLE' : 'REAL';
            
            echo "<tr $rowClass>";
            echo "<td>{$req['id']}</td>";
            echo "<td>{$req['customer_name']}</td>";
            echo "<td>{$req['customer_email']}</td>";
            echo "<td>{$req['title']}</td>";
            echo "<td><strong>$type</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2 class='warning'>Removing Sample Data...</h2>";
    
    // Remove sample data by email
    $sampleEmails = [
        'alice.johnson@email.com',
        'michael.chen@email.com', 
        'sarah.williams@email.com',
        'robert.davis@email.com'
    ];
    
    $totalRemoved = 0;
    foreach ($sampleEmails as $email) {
        $stmt = $pdo->prepare("DELETE FROM custom_requests WHERE customer_email = ?");
        $stmt->execute([$email]);
        $removed = $stmt->rowCount();
        $totalRemoved += $removed;
        if ($removed > 0) {
            echo "<p class='success'>✓ Removed $removed sample records for: $email</p>";
        }
    }
    
    // Remove by sample names
    $sampleNames = ['Alice Johnson', 'Michael Chen', 'Sarah Williams', 'Robert Davis'];
    foreach ($sampleNames as $name) {
        $stmt = $pdo->prepare("DELETE FROM custom_requests WHERE customer_name = ?");
        $stmt->execute([$name]);
        $removed = $stmt->rowCount();
        $totalRemoved += $removed;
        if ($removed > 0) {
            echo "<p class='success'>✓ Removed $removed sample records for: $name</p>";
        }
    }
    
    // Remove by order ID pattern
    $stmt = $pdo->prepare("DELETE FROM custom_requests WHERE order_id LIKE ?");
    $stmt->execute(['CR-' . date('Ymd') . '-%']);
    $removed = $stmt->rowCount();
    $totalRemoved += $removed;
    if ($removed > 0) {
        echo "<p class='success'>✓ Removed $removed sample records by order ID pattern</p>";
    }
    
    echo "<h2 class='info'>After Cleanup:</h2>";
    
    $afterCount = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
    echo "<p>Total requests after cleanup: <strong>$afterCount</strong></p>";
    echo "<p>Sample records removed: <strong>$totalRemoved</strong></p>";
    
    if ($afterCount > 0) {
        $allAfter = $pdo->query("SELECT * FROM custom_requests ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3 class='success'>✅ Your Real Custom Requests:</h3>";
        
        foreach ($allAfter as $req) {
            echo "<div style='border:1px solid #10b981;background:#d1fae5;padding:15px;margin:10px 0;border-radius:5px;'>";
            echo "<h4>{$req['title']}</h4>";
            echo "<p><strong>Customer:</strong> {$req['customer_name']} ({$req['customer_email']})</p>";
            echo "<p><strong>Phone:</strong> " . ($req['customer_phone'] ?: 'N/A') . "</p>";
            echo "<p><strong>Status:</strong> {$req['status']}</p>";
            echo "<p><strong>Description:</strong> " . ($req['description'] ?: 'N/A') . "</p>";
            echo "<p><strong>Requirements:</strong> " . ($req['requirements'] ?: 'N/A') . "</p>";
            echo "<p><strong>Budget:</strong> $" . ($req['budget_min'] ?: '0') . " - $" . ($req['budget_max'] ?: '0') . "</p>";
            echo "<p><strong>Deadline:</strong> " . ($req['deadline'] ?: 'N/A') . "</p>";
            echo "<p><strong>Created:</strong> {$req['created_at']}</p>";
            echo "</div>";
        }
    } else {
        echo "<div style='background:#fef3c7;padding:15px;border-radius:5px;margin:15px 0;'>";
        echo "<h3>⚠️ No Real Custom Requests Found</h3>";
        echo "<p>Your database now contains only real customer requests. Since the count is 0, this means:</p>";
        echo "<ul>";
        echo "<li>✅ All sample data has been successfully removed</li>";
        echo "<li>📝 No real customers have submitted custom requests yet</li>";
        echo "<li>🎯 Your admin dashboard will now show an empty list (which is correct)</li>";
        echo "</ul>";
        echo "<p>When real customers submit custom requests, they will appear in your admin dashboard.</p>";
        echo "</div>";
    }
    
    echo "<h2 class='success'>✅ Cleanup Complete!</h2>";
    echo "<div style='background:#d1fae5;padding:20px;border-radius:5px;margin:20px 0;'>";
    echo "<h3>🎉 Summary:</h3>";
    echo "<ul>";
    echo "<li>✅ Removed all sample/test data</li>";
    echo "<li>✅ Kept only real customer requests</li>";
    echo "<li>✅ Admin dashboard will show accurate data</li>";
    echo "<li>✅ System ready for real customer requests</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🧪 Test Your Clean System:</h3>";
    echo "<p><a href='api/admin/custom-requests-database-only.php?status=all' target='_blank'>View Clean Custom Requests API</a></p>";
    echo "<p>Your admin dashboard will now show only real customer requests!</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>