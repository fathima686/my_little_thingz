<?php
// Check the actual structure of custom_request_images table
echo "<h1>🔍 Checking Image Table Structure</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Check table structure
    echo "<h2>📋 Table Structure: custom_request_images</h2>";
    
    $columns = $pdo->query("DESCRIBE custom_request_images")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check sample data
    echo "<h2>📊 Sample Image Data</h2>";
    
    $sampleImages = $pdo->query("SELECT * FROM custom_request_images ORDER BY uploaded_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sampleImages)) {
        echo "<p style='color: orange;'>⚠️ No images found in table</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        foreach (array_keys($sampleImages[0]) as $column) {
            echo "<th>$column</th>";
        }
        echo "</tr>";
        
        foreach ($sampleImages as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                $displayValue = is_string($value) ? substr($value, 0, 50) : $value;
                echo "<td>$displayValue</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check what the admin API is expecting vs what exists
    echo "<h2>🔍 Column Mapping Analysis</h2>";
    
    $actualColumns = array_column($columns, 'Field');
    $expectedColumns = ['image_url', 'filename', 'original_filename', 'uploaded_at'];
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 10px; margin: 15px 0;'>";
    echo "<h4>Admin API expects these columns:</h4>";
    echo "<ul>";
    foreach ($expectedColumns as $expected) {
        $exists = in_array($expected, $actualColumns);
        $status = $exists ? "✅" : "❌";
        echo "<li>$status <strong>$expected</strong>" . ($exists ? " (exists)" : " (missing)") . "</li>";
    }
    echo "</ul>";
    
    echo "<h4>Actual columns in table:</h4>";
    echo "<ul>";
    foreach ($actualColumns as $actual) {
        echo "<li>📋 <strong>$actual</strong></li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // Suggest mapping
    echo "<h2>💡 Suggested Column Mapping</h2>";
    
    echo "<div style='background: #e8f4fd; padding: 15px; border-radius: 10px; margin: 15px 0;'>";
    echo "<p>Based on the actual table structure, here's the likely mapping:</p>";
    echo "<ul>";
    
    if (in_array('image_path', $actualColumns)) {
        echo "<li>✅ <strong>image_path</strong> → should be used instead of <strong>image_url</strong></li>";
    }
    
    if (in_array('uploaded_at', $actualColumns)) {
        echo "<li>✅ <strong>uploaded_at</strong> → matches expected column</li>";
    }
    
    if (!in_array('filename', $actualColumns)) {
        echo "<li>⚠️ <strong>filename</strong> → missing, may need to extract from image_path</li>";
    }
    
    if (!in_array('original_filename', $actualColumns)) {
        echo "<li>⚠️ <strong>original_filename</strong> → missing, may need to extract from image_path</li>";
    }
    
    echo "</ul>";
    echo "</div>";
    
    // Test image paths
    echo "<h2>🧪 Testing Image Paths</h2>";
    
    if (!empty($sampleImages)) {
        foreach (array_slice($sampleImages, 0, 3) as $img) {
            $requestId = $img['request_id'];
            $imagePath = $img['image_path'] ?? '';
            
            echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            echo "<p><strong>Request ID:</strong> $requestId</p>";
            echo "<p><strong>Image Path:</strong> $imagePath</p>";
            
            if ($imagePath) {
                $fullPath = __DIR__ . "/../../" . $imagePath;
                $exists = file_exists($fullPath);
                $status = $exists ? "✅ File exists" : "❌ File not found";
                echo "<p><strong>Full Path:</strong> $fullPath</p>";
                echo "<p><strong>Status:</strong> $status</p>";
                
                if ($exists) {
                    $baseUrl = "http://localhost/my_little_thingz/backend/";
                    echo "<p><strong>URL:</strong> <a href='{$baseUrl}{$imagePath}' target='_blank'>{$baseUrl}{$imagePath}</a></p>";
                }
            }
            echo "</div>";
        }
    }
    
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