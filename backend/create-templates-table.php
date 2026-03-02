<?php
/**
 * Create design_templates table for the template gallery
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>Creating Design Templates Table</h2>";
    
    // Create design_templates table
    $sql = "CREATE TABLE IF NOT EXISTS design_templates (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL DEFAULT 'other',
        description TEXT,
        thumbnail_path VARCHAR(500),
        template_data LONGTEXT NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_by INT UNSIGNED DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_category (category),
        INDEX idx_active (is_active),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ Table 'design_templates' created successfully!</p>";
    
    // Check if table has any data
    $stmt = $pdo->query("SELECT COUNT(*) FROM design_templates");
    $count = $stmt->fetchColumn();
    
    echo "<p><strong>Current templates count:</strong> {$count}</p>";
    
    if ($count == 0) {
        echo "<h3>Inserting Sample Templates</h3>";
        
        // Insert sample templates
        $sampleTemplates = [
            [
                'name' => 'Happy Birthday Card',
                'category' => 'birthday',
                'description' => 'Colorful birthday card template',
                'template_data' => json_encode([
                    'canvas' => ['width' => 600, 'height' => 400, 'backgroundColor' => '#ffe6cc'],
                    'elements' => [
                        [
                            'type' => 'text',
                            'content' => 'Happy Birthday!',
                            'x' => 200,
                            'y' => 150,
                            'fontSize' => 36,
                            'fontFamily' => 'Arial',
                            'fontWeight' => 'bold',
                            'fill' => '#ff6b6b'
                        ]
                    ]
                ])
            ],
            [
                'name' => 'Name Frame Template',
                'category' => 'name-frame',
                'description' => 'Elegant name frame with border',
                'template_data' => json_encode([
                    'canvas' => ['width' => 600, 'height' => 400, 'backgroundColor' => '#e3f2fd'],
                    'elements' => [
                        [
                            'type' => 'shape',
                            'shape' => 'rectangle',
                            'x' => 100,
                            'y' => 100,
                            'width' => 400,
                            'height' => 200,
                            'fill' => 'transparent',
                            'stroke' => '#6366f1',
                            'strokeWidth' => 6
                        ],
                        [
                            'type' => 'text',
                            'content' => 'Your Name',
                            'x' => 250,
                            'y' => 180,
                            'fontSize' => 28,
                            'fontFamily' => 'Arial',
                            'fill' => '#6366f1'
                        ]
                    ]
                ])
            ],
            [
                'name' => 'Inspirational Quote',
                'category' => 'quotes',
                'description' => 'Beautiful gradient background with quote',
                'template_data' => json_encode([
                    'canvas' => ['width' => 600, 'height' => 400, 'backgroundColor' => '#8b5cf6'],
                    'elements' => [
                        [
                            'type' => 'text',
                            'content' => '"Be the change you wish to see"',
                            'x' => 300,
                            'y' => 180,
                            'fontSize' => 24,
                            'fontFamily' => 'Georgia',
                            'fontStyle' => 'italic',
                            'fill' => '#ffffff',
                            'textAlign' => 'center'
                        ]
                    ]
                ])
            ]
        ];
        
        $insertSql = "INSERT INTO design_templates (name, category, description, template_data, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($insertSql);
        
        foreach ($sampleTemplates as $template) {
            $stmt->execute([
                $template['name'],
                $template['category'],
                $template['description'],
                $template['template_data']
            ]);
            echo "<p style='color: blue;'>✅ Inserted template: {$template['name']}</p>";
        }
        
        echo "<p style='color: green;'><strong>✅ Sample templates inserted successfully!</strong></p>";
    }
    
    // Test the API
    echo "<h3>Testing Template API</h3>";
    
    // Simulate API call
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['action'] = 'list';
    $_SERVER['HTTP_X_ADMIN_USER_ID'] = '1';
    
    ob_start();
    include 'api/admin/template-gallery.php';
    $apiResponse = ob_get_clean();
    
    echo "<p><strong>API Response:</strong></p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto;'>";
    echo htmlspecialchars($apiResponse);
    echo "</pre>";
    
    $decoded = json_decode($apiResponse, true);
    if ($decoded && $decoded['status'] === 'success') {
        echo "<p style='color: green;'>✅ Template API is working correctly!</p>";
        echo "<p><strong>Templates found:</strong> " . count($decoded['data']['templates']) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Template API has issues</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { font-size: 12px; }
</style>