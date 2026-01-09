<?php
// Check all image mappings and fix mismatched request IDs
echo "<h1>🔍 Checking All Image Mappings</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Step 1: Check all images and their current request_id mappings
    echo "<h2>📋 Step 1: All Images in Database</h2>";
    
    $allImages = $pdo->query("
        SELECT 
            cri.id as image_id,
            cri.request_id as current_request_id,
            cri.image_path,
            cri.uploaded_at,
            cr.id as main_table_id,
            cr.order_id,
            cr.title
        FROM custom_request_images cri
        LEFT JOIN custom_requests cr ON cri.request_id = cr.id
        ORDER BY cri.uploaded_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: blue;'>📊 Found " . count($allImages) . " total images</p>";
    
    $orphanedImages = [];
    $linkedImages = [];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Image ID</th><th>Current Request ID</th><th>Main Table Match</th><th>Order ID</th><th>Image Path</th><th>Status</th></tr>";
    
    foreach ($allImages as $img) {
        $isOrphaned = is_null($img['main_table_id']);
        if ($isOrphaned) {
            $orphanedImages[] = $img;
            $rowStyle = "background: #f8d7da;";
            $status = "❌ Orphaned";
        } else {
            $linkedImages[] = $img;
            $rowStyle = "background: #d4edda;";
            $status = "✅ Linked";
        }
        
        echo "<tr style='$rowStyle'>";
        echo "<td>{$img['image_id']}</td>";
        echo "<td>{$img['current_request_id']}</td>";
        echo "<td>" . ($img['main_table_id'] ?? 'None') . "</td>";
        echo "<td>" . ($img['order_id'] ?? 'N/A') . "</td>";
        echo "<td>" . substr($img['image_path'], 0, 40) . "...</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #e8f4fd; padding: 15px; border-radius: 10px; margin: 15px 0;'>";
    echo "<h4>📊 Summary:</h4>";
    echo "<p>✅ <strong>" . count($linkedImages) . "</strong> images properly linked</p>";
    echo "<p>❌ <strong>" . count($orphanedImages) . "</strong> images orphaned (not linked to main table)</p>";
    echo "</div>";
    
    if (count($orphanedImages) > 0) {
        // Step 2: Try to find correct mappings for orphaned images
        echo "<h2>🔍 Step 2: Finding Correct Mappings for Orphaned Images</h2>";
        
        // Get all requests from main table
        $mainRequests = $pdo->query("
            SELECT id, order_id, title, customer_name, created_at 
            FROM custom_requests 
            ORDER BY created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all requests from backup table for reference
        $backupRequests = $pdo->query("
            SELECT id, title, user_id, created_at 
            FROM custom_requests_backup 
            ORDER BY created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p style='color: blue;'>📊 Main table has " . count($mainRequests) . " requests</p>";
        echo "<p style='color: blue;'>📊 Backup table has " . count($backupRequests) . " requests</p>";
        
        // Try to map orphaned images to correct requests
        $mappingCandidates = [];
        
        foreach ($orphanedImages as $orphanedImg) {
            $orphanedRequestId = $orphanedImg['current_request_id'];
            
            // Look for matching backup request
            $backupMatch = null;
            foreach ($backupRequests as $backup) {
                if ($backup['id'] == $orphanedRequestId) {
                    $backupMatch = $backup;
                    break;
                }
            }
            
            if ($backupMatch) {
                // Try to find corresponding main table request
                $mainMatch = null;
                foreach ($mainRequests as $main) {
                    // Match by title and similar creation date
                    if (
                        $main['title'] == $backupMatch['title'] &&
                        date('Y-m-d', strtotime($main['created_at'])) == date('Y-m-d', strtotime($backupMatch['created_at']))
                    ) {
                        $mainMatch = $main;
                        break;
                    }
                }
                
                if ($mainMatch) {
                    $mappingCandidates[] = [
                        'image_id' => $orphanedImg['image_id'],
                        'current_request_id' => $orphanedRequestId,
                        'new_request_id' => $mainMatch['id'],
                        'backup_title' => $backupMatch['title'],
                        'main_title' => $mainMatch['title'],
                        'main_order_id' => $mainMatch['order_id'],
                        'image_path' => $orphanedImg['image_path']
                    ];
                }
            }
        }
        
        echo "<p style='color: green;'>🎯 Found " . count($mappingCandidates) . " mapping candidates</p>";
        
        if (!empty($mappingCandidates)) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr style='background: #f0f0f0;'><th>Image ID</th><th>Old Request ID</th><th>New Request ID</th><th>Order ID</th><th>Title</th><th>Image</th></tr>";
            
            foreach ($mappingCandidates as $candidate) {
                echo "<tr style='background: #fff3cd;'>";
                echo "<td>{$candidate['image_id']}</td>";
                echo "<td>{$candidate['current_request_id']}</td>";
                echo "<td>{$candidate['new_request_id']}</td>";
                echo "<td>{$candidate['main_order_id']}</td>";
                echo "<td>" . substr($candidate['main_title'], 0, 30) . "...</td>";
                echo "<td>" . substr($candidate['image_path'], 0, 30) . "...</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Step 3: Execute the mapping updates
            echo "<h2>🔄 Step 3: Updating Image Mappings</h2>";
            
            $updated = 0;
            $errors = 0;
            
            foreach ($mappingCandidates as $candidate) {
                try {
                    $updateStmt = $pdo->prepare("
                        UPDATE custom_request_images 
                        SET request_id = ? 
                        WHERE id = ?
                    ");
                    
                    $result = $updateStmt->execute([$candidate['new_request_id'], $candidate['image_id']]);
                    
                    if ($result) {
                        echo "<p style='color: green;'>✅ Updated image {$candidate['image_id']}: {$candidate['current_request_id']} → {$candidate['new_request_id']}</p>";
                        $updated++;
                    } else {
                        echo "<p style='color: red;'>❌ Failed to update image {$candidate['image_id']}</p>";
                        $errors++;
                    }
                    
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ Error updating image {$candidate['image_id']}: " . $e->getMessage() . "</p>";
                    $errors++;
                }
            }
            
            echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #28a745;'>";
            echo "<h4 style='color: #155724;'>🎉 Mapping Update Complete!</h4>";
            echo "<p style='color: #155724;'>✅ Successfully updated: <strong>$updated</strong> image mappings</p>";
            echo "<p style='color: #155724;'>❌ Errors: <strong>$errors</strong></p>";
            echo "</div>";
        }
    }
    
    // Step 4: Final verification
    echo "<h2>✅ Step 4: Final Verification</h2>";
    
    $finalCheck = $pdo->query("
        SELECT 
            COUNT(*) as total_images,
            COUNT(cr.id) as linked_images,
            COUNT(*) - COUNT(cr.id) as orphaned_images
        FROM custom_request_images cri
        LEFT JOIN custom_requests cr ON cri.request_id = cr.id
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "<div style='background: #e8f4fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h4 style='color: #0c5460;'>📊 Final Status:</h4>";
    echo "<p style='color: #0c5460;'>📷 Total images: <strong>{$finalCheck['total_images']}</strong></p>";
    echo "<p style='color: #0c5460;'>✅ Properly linked: <strong>{$finalCheck['linked_images']}</strong></p>";
    echo "<p style='color: #0c5460;'>❌ Still orphaned: <strong>{$finalCheck['orphaned_images']}</strong></p>";
    echo "</div>";
    
    // Show requests with images now
    echo "<h2>📊 Requests with Images (After Fix)</h2>";
    
    $requestsWithImages = $pdo->query("
        SELECT 
            cr.id,
            cr.order_id,
            cr.title,
            cr.customer_name,
            COUNT(cri.id) as image_count
        FROM custom_requests cr
        INNER JOIN custom_request_images cri ON cr.id = cri.request_id
        GROUP BY cr.id
        ORDER BY cr.created_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>🎯 Now " . count($requestsWithImages) . " requests have images!</p>";
    
    if (!empty($requestsWithImages)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Request ID</th><th>Order ID</th><th>Customer</th><th>Title</th><th>Image Count</th></tr>";
        
        foreach ($requestsWithImages as $req) {
            echo "<tr style='background: #d4edda;'>";
            echo "<td>{$req['id']}</td>";
            echo "<td>{$req['order_id']}</td>";
            echo "<td>{$req['customer_name']}</td>";
            echo "<td>" . substr($req['title'], 0, 40) . "...</td>";
            echo "<td><strong>{$req['image_count']}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    if ($finalCheck['orphaned_images'] == 0) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0; text-align: center;'>";
        echo "<h3 style='color: #155724;'>🎉 SUCCESS! All Images Fixed!</h3>";
        echo "<p style='color: #155724;'>All images are now properly linked to requests in the main table.</p>";
        echo "</div>";
    }
    
    // Test links
    echo "<h2>🔗 Test the Complete Fix</h2>";
    
    echo "<div style='background: #e8f4fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<ul>";
    echo "<li><a href='api/admin/custom-requests-database-only.php' target='_blank' style='color: #007cba; font-weight: bold;'>📊 Test Admin API</a> - Should show all images</li>";
    echo "<li><a href='../frontend/admin/custom-requests-dashboard.html' target='_blank' style='color: #007cba; font-weight: bold;'>👨‍💼 Open Admin Dashboard</a> - All images should display</li>";
    echo "<li><a href='final-image-test.php' target='_blank' style='color: #007cba;'>🧪 Test Specific Request</a> - Verify image loading</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #dc3545;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Error Occurred</h3>";
    echo "<p style='color: #721c24;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 20px;
    background: #f8f9fa;
}

h1, h2, h3 {
    color: #333;
}

table {
    margin: 15px 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

th, td {
    padding: 8px;
    text-align: left;
    border: 1px solid #ddd;
    font-size: 12px;
}

th {
    background: #f8f9fa;
    font-weight: 600;
}

ul {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

li {
    margin: 8px 0;
}

a {
    color: #007cba;
    text-decoration: none;
    font-weight: 600;
}

a:hover {
    text-decoration: underline;
}
</style>