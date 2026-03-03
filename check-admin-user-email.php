<?php
require_once 'backend/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h1>🔍 Check Admin User Email</h1>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .alert { padding: 15px; margin: 20px 0; border-radius: 4px; }
        .alert.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .alert.info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
    </style>";
    
    echo "<div class='container'>";
    
    // Check user ID 5 specifically
    $userId = 5;
    echo "<h2>👤 User ID {$userId} Details</h2>";
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<div class='alert success'>✅ User found</div>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($user as $key => $value) {
            echo "<tr><td><strong>{$key}</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
        
        if (empty($user['email'])) {
            echo "<div class='alert error'>❌ Email is empty for this user!</div>";
            
            // Let's update the email for this user
            echo "<h3>🔧 Fixing Email</h3>";
            $updateStmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
            $newEmail = "admin{$userId}@mylittlethingz.com";
            $updateStmt->execute([$newEmail, $userId]);
            
            echo "<div class='alert success'>✅ Updated email to: {$newEmail}</div>";
        } else {
            echo "<div class='alert success'>✅ Email is set: {$user['email']}</div>";
        }
    } else {
        echo "<div class='alert error'>❌ User ID {$userId} not found</div>";
    }
    
    // Check all users to see email status
    echo "<h2>📋 All Users Email Status</h2>";
    
    $allStmt = $conn->prepare("SELECT id, first_name, last_name, email, created_at FROM users ORDER BY id");
    $allStmt->execute();
    $allUsers = $allStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Created</th><th>Status</th></tr>";
    
    foreach ($allUsers as $user) {
        $emailStatus = empty($user['email']) ? '❌ Empty' : '✅ Set';
        $emailClass = empty($user['email']) ? 'error' : 'success';
        
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['first_name']} {$user['last_name']}</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . date('M j, Y', strtotime($user['created_at'])) . "</td>";
        echo "<td><span style='color: " . ($emailClass === 'success' ? '#155724' : '#721c24') . ";'>{$emailStatus}</span></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert error'>❌ Error: " . $e->getMessage() . "</div>";
}
?>