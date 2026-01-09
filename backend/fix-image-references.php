<?php
// Fix image references after data migration
echo "<h1>🔧 Fixing Image References</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Step 1: Check current image references
    echo "<h2>📋 Step 1: Current Image References</h2>";
    
    $imageRefs = $pdo->query("
        SELECT 
            cri.id as image_id,
            cri.request_id as current_request_id,
            cri.image_path,
            cr.id as main_table_id,
            cr.order_id,
            cr.title
        FROM custom_request_images cri
        LEFT JOIN custom_requests cr ON cri.request_id = cr.id
        ORDER BY cri.uploaded_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Image ID</th><th>Current Request ID</th><th>Main Table Match</th><th>Order ID</th><th>Title</th><th>Image Path</th></tr>";
    
    $orphanedImages = 0;
    $linkedImages = 0;
    
    foreach ($imageRefs as $ref) {
        $isOrphaned = is_null($ref['main_table_id']);
        if ($isOrphaned) {
            $orphanedImages++;
            $rowStyle = "background: #f8d7da;";
        } else {
            $linkedImages++;
            $rowStyle = "background: #d4edda;";
        }
        
        echo "<tr style='$rowStyle'>";
        echo "<td>{$ref['image_id']}</td>";
        echo "<td>{$ref['current_request_id']}</td>";
        echo "<td>" . ($isOrphaned ? "❌ No match" : "✅ {$ref['main_table_id']}") . "</td>";
        echo "<td>" . ($ref['order_id'] ?? 'N/A') . "</td>";
        echo "<td>" . (isset($ref['title']) ? substr($ref['title'], 0, 30) . '...' : 'N/A') . "</td>";
        echo "<td>" . substr($ref['image_path'], 0, 40) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: blue;'>📊 Summary: <strong>$linkedImages</strong> images properly linked, <strong>$orphanedImages</strong> orphaned</p>";
    
    if ($orphanedImages > 0) {
        // Step 2: Find the mapping between old and new IDs
        echo "<h2>🔍 Step 2: Finding ID Mapping</h2>";
        
        // Get the mapping from backup table to main table based on creation time and title
        $mapping = $pdo->query("
            SELECT 
                crb.id as backup_id,
                crb.title as backup_title,
                crb.created_at as backup_created,
                cr.id as main_id,
                cr.title as main_title,
                cr.created_at as main_created
            FROM custom_requests_backup crb
            INNER JOIN custom_requests cr ON (
                crb.title = cr.title 
                AND DATE(crb.created_at) = DATE(cr.created_at)
            )
            ORDER BY crb.id
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p style='color: blue;'>📊 Found " . count($mapping) . " ID mappings</p>";
        
        if (!empty($mapping)) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr style='background: #f0f0f0;'><th>Backup ID</th><th>Main ID</th><th>Title</th><th>Created Date</th></tr>";
            
            foreach (array_slice($mapping, 0, 10) as $map) {
                echo "<tr>";
                echo "<td>{$map['backup_id']}</td>";
                echo "<td>{$map['main_id']}</td>";
                echo "<td>" . substr($map['backup_title'], 0, 40) . "...</td>";
                echo "<td>" . date('Y-m-d', strtotime($map['backup_created'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Step 3: Update image references
            echo "<h2>🔄 Step 3: Updating Image References</h2>";
            
            $updated = 0;
            $errors = 0;
            
            foreach ($mapping as $map) {
                try {
                    $updateStmt = $pdo->prepare("
                        UPDATE custom_request_images 
                        SET request_id = ? 
                        WHERE request_id = ?
                    ");
                    
                    $result = $updateStmt->execute([$map['main_id'], $map['backup_id']]);
                    $affectedRows = $updateStmt->rowCount();
                    
                    if ($affectedRows > 0) {
                        echo "<p style='color: green;'>✅ Updated $affectedRows images: Request {$map['backup_id']} → {$map['main_id']}</p>";
                        $updated += $affectedRows;
                    }
                    
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ Error updating {$map['backup_id']}: " . $e->getMessage() . "</p>";
                    $errors++;
                }
            }
            
            echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #28a745;'>";
            echo "<h4 style='color: #155724;'>🎉 Update Complete!</h4>";
            echo "<p style='color: #155724;'>✅ Updated <strong>$updated</strong> image references</p>";
            echo "<p style='color: #155724;'>❌ Errors: <strong>$errors</strong></p>";
            echo "</div>";
            
        } else {
            echo "<p style='color: orange;'>⚠️ No ID mappings found. Images may need manual fixing.</p>";
        }
    }
    
    // Step 4: Verify the fix
    echo "<h2>✅ Step 4: Verification</h2>";
    
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
    echo "<p style='color: #0c5460;'>❌ Orphaned: <strong>{$finalCheck['orphaned_images']}</strong></p>";
    echo "</div>";
    
    if ($finalCheck['orphaned_images'] == 0) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0; text-align: center;'>";
        echo "<h3 style='color: #155724;'>🎉 SUCCESS! All Images Fixed!</h3>";
        echo "<p style='color: #155724;'>All image references are now properly linked to the main table.</p>";
        echo "</div>";
    }
    
    // Test links
    echo "<h2>🔗 Test the Fix</h2>";
    
    echo "<div style='background: #e8f4fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<ul>";
    echo "<li><a href='test-fixed-image-api.php' target='_blank' style='color: #007cba; font-weight: bold;'>🧪 Test Image API Again</a></li>";
    echo "<li><a href='api/admin/custom-requests-database-only.php' target='_blank' style='color: #007cba; font-weight: bold;'>📊 Test Admin API</a></li>";
    echo "<li><a href='../frontend/admin/custom-requests-dashboard.html' target='_blank' style='color: #007cba; font-weight: bold;'>👨‍💼 Open Admin Dashboard</a></li>";
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