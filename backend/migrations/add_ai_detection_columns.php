<?php
/**
 * Database Migration: Add AI Detection Columns
 * 
 * Adds multi-layer AI detection fields to craft_image_validation_v2 table
 * 
 * New columns:
 * - ai_risk_score: Cumulative risk score (0-100)
 * - ai_risk_level: Risk level (low/medium/high/unknown)
 * - ai_detection_decision: Detection decision (pass/flag/reject)
 * - ai_detection_evidence: Full detection evidence JSON
 * - metadata_ai_keywords: AI generator keywords found
 * - exif_camera_present: Whether camera EXIF data exists
 * - texture_laplacian_variance: Texture smoothness score
 * - watermark_detected: Whether watermark detected
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== AI Detection Columns Migration ===\n\n";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'craft_image_validation_v2'");
    if ($stmt->rowCount() === 0) {
        echo "❌ Table craft_image_validation_v2 does not exist\n";
        echo "   Please run the main validation service first to create the table\n";
        exit(1);
    }
    
    echo "✓ Table craft_image_validation_v2 exists\n\n";
    
    // Get existing columns
    $stmt = $pdo->query("SHOW COLUMNS FROM craft_image_validation_v2");
    $existingColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['Field'];
    }
    
    echo "Existing columns: " . count($existingColumns) . "\n\n";
    
    // Define new columns to add
    $columnsToAdd = [
        [
            'name' => 'ai_risk_score',
            'definition' => 'INT(11) DEFAULT 0 AFTER classification_data',
            'description' => 'Cumulative AI risk score (0-100)'
        ],
        [
            'name' => 'ai_risk_level',
            'definition' => "ENUM('low', 'medium', 'high', 'unknown') DEFAULT 'unknown' AFTER ai_risk_score",
            'description' => 'AI risk level classification'
        ],
        [
            'name' => 'ai_detection_decision',
            'definition' => "ENUM('pass', 'flag', 'reject') DEFAULT 'pass' AFTER ai_risk_level",
            'description' => 'AI detection decision'
        ],
        [
            'name' => 'ai_detection_evidence',
            'definition' => 'JSON DEFAULT NULL AFTER ai_detection_decision',
            'description' => 'Full AI detection evidence from all layers'
        ],
        [
            'name' => 'metadata_ai_keywords',
            'definition' => 'JSON DEFAULT NULL AFTER ai_detection_evidence',
            'description' => 'AI generator keywords found in metadata'
        ],
        [
            'name' => 'exif_camera_present',
            'definition' => 'TINYINT(1) DEFAULT NULL AFTER metadata_ai_keywords',
            'description' => 'Whether camera EXIF metadata is present'
        ],
        [
            'name' => 'texture_laplacian_variance',
            'definition' => 'DECIMAL(10,2) DEFAULT NULL AFTER exif_camera_present',
            'description' => 'Laplacian variance for texture smoothness'
        ],
        [
            'name' => 'watermark_detected',
            'definition' => 'TINYINT(1) DEFAULT 0 AFTER texture_laplacian_variance',
            'description' => 'Whether AI platform watermark detected'
        ]
    ];
    
    // Add missing columns
    $addedCount = 0;
    $skippedCount = 0;
    
    foreach ($columnsToAdd as $column) {
        if (in_array($column['name'], $existingColumns)) {
            echo "⊘ Column '{$column['name']}' already exists - skipping\n";
            $skippedCount++;
            continue;
        }
        
        try {
            $sql = "ALTER TABLE craft_image_validation_v2 ADD COLUMN {$column['name']} {$column['definition']}";
            $pdo->exec($sql);
            echo "✓ Added column '{$column['name']}' - {$column['description']}\n";
            $addedCount++;
        } catch (Exception $e) {
            echo "✗ Failed to add column '{$column['name']}': {$e->getMessage()}\n";
        }
    }
    
    echo "\n";
    
    // Add indexes for new columns
    echo "Adding indexes for AI detection columns...\n";
    
    $indexesToAdd = [
        ['name' => 'idx_ai_risk_level', 'column' => 'ai_risk_level'],
        ['name' => 'idx_ai_detection_decision', 'column' => 'ai_detection_decision']
    ];
    
    foreach ($indexesToAdd as $index) {
        try {
            // Check if index exists
            $stmt = $pdo->query("SHOW INDEX FROM craft_image_validation_v2 WHERE Key_name = '{$index['name']}'");
            if ($stmt->rowCount() > 0) {
                echo "⊘ Index '{$index['name']}' already exists - skipping\n";
                continue;
            }
            
            $sql = "ALTER TABLE craft_image_validation_v2 ADD INDEX {$index['name']} ({$index['column']})";
            $pdo->exec($sql);
            echo "✓ Added index '{$index['name']}' on column '{$index['column']}'\n";
        } catch (Exception $e) {
            echo "⊘ Index '{$index['name']}' may already exist or error: {$e->getMessage()}\n";
        }
    }
    
    echo "\n=== Migration Summary ===\n";
    echo "Columns added: $addedCount\n";
    echo "Columns skipped (already exist): $skippedCount\n";
    echo "Total new columns: " . count($columnsToAdd) . "\n";
    
    if ($addedCount > 0) {
        echo "\n✅ Migration completed successfully!\n";
        echo "   AI detection columns are now available in craft_image_validation_v2 table\n";
    } else {
        echo "\n✓ No changes needed - all columns already exist\n";
    }
    
    echo "\n=== Verification ===\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM craft_image_validation_v2");
    $finalColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $finalColumns[] = $row['Field'];
    }
    echo "Final column count: " . count($finalColumns) . "\n";
    
    // Check for AI detection columns
    $aiColumns = array_filter($finalColumns, function($col) {
        return strpos($col, 'ai_') === 0;
    });
    echo "AI detection columns: " . count($aiColumns) . "\n";
    
    if (count($aiColumns) >= 8) {
        echo "✅ All AI detection columns present\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
