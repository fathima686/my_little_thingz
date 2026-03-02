<?php
/**
 * Migration Script: Transition to Simplified Authenticity System
 * 
 * This script:
 * 1. Creates new simplified tables
 * 2. Migrates essential data from complex system
 * 3. Updates existing practice uploads
 * 4. Provides rollback capability
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== Simplified Image Authenticity System Migration ===\n\n";
    
    // Step 1: Create simplified tables
    echo "Step 1: Creating simplified database schema...\n";
    $schemaSQL = file_get_contents(__DIR__ . '/database/simplified-authenticity-schema.sql');
    
    // Execute schema in parts to handle multiple statements
    $statements = explode(';', $schemaSQL);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^(USE|COMMIT|--)/i', $statement)) {
            try {
                $pdo->exec($statement);
            } catch (Exception $e) {
                if (!strpos($e->getMessage(), 'already exists')) {
                    echo "Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    echo "✓ Schema created successfully\n\n";
    
    // Step 2: Migrate existing data if old system exists
    echo "Step 2: Migrating existing authenticity data...\n";
    
    // Check if old tables exist
    $oldTablesExist = false;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'image_authenticity_metadata'");
        $oldTablesExist = $stmt->rowCount() > 0;
    } catch (Exception $e) {
        // Old tables don't exist, skip migration
    }
    
    if ($oldTablesExist) {
        echo "Found existing authenticity data, migrating...\n";
        
        // Migrate essential data from old system
        $migrateStmt = $pdo->prepare("
            INSERT IGNORE INTO image_authenticity_simple 
            (image_id, image_type, user_id, tutorial_id, category, phash, 
             evaluation_status, admin_decision, requires_review, flagged_reason, 
             metadata_notes, created_at)
            SELECT 
                iam.image_id,
                iam.image_type,
                COALESCE(pu.user_id, 1) as user_id,
                COALESCE(pu.tutorial_id, 1) as tutorial_id,
                COALESCE(iam.tutorial_category, 'general') as category,
                iam.perceptual_hash as phash,
                CASE 
                    WHEN iam.risk_level = 'highly_suspicious' THEN 'reused'
                    WHEN iam.risk_level = 'suspicious' THEN 'highly_similar'
                    WHEN iam.requires_admin_review = 1 THEN 'needs_admin_review'
                    ELSE 'unique'
                END as evaluation_status,
                CASE 
                    WHEN arq.admin_decision = 'approved' THEN 'approved'
                    WHEN arq.admin_decision = 'rejected' THEN 'rejected'
                    WHEN arq.admin_decision = 'false_positive' THEN 'false_positive'
                    ELSE 'pending'
                END as admin_decision,
                iam.requires_admin_review,
                JSON_UNQUOTE(JSON_EXTRACT(iam.flagged_reasons, '$[0]')) as flagged_reason,
                CONCAT('Migrated: ', COALESCE(iam.verification_method, 'unknown')) as metadata_notes,
                iam.created_at
            FROM image_authenticity_metadata iam
            LEFT JOIN admin_review_queue arq ON iam.image_id = arq.image_id AND iam.image_type = arq.image_type
            LEFT JOIN practice_uploads pu ON iam.image_id LIKE CONCAT('%', pu.id, '%')
            WHERE iam.image_id IS NOT NULL
        ");
        
        $migratedCount = $migrateStmt->execute() ? $migrateStmt->rowCount() : 0;
        echo "✓ Migrated {$migratedCount} authenticity records\n";
        
        // Migrate admin review queue
        $migrateReviewStmt = $pdo->prepare("
            INSERT IGNORE INTO admin_review_simple 
            (image_id, image_type, user_id, tutorial_id, category, 
             evaluation_status, flagged_reason, admin_decision, flagged_at)
            SELECT 
                arq.image_id,
                arq.image_type,
                arq.user_id,
                arq.tutorial_id,
                COALESCE(arq.tutorial_category, 'general') as category,
                CASE 
                    WHEN arq.risk_level = 'highly_suspicious' THEN 'reused'
                    WHEN arq.risk_level = 'suspicious' THEN 'highly_similar'
                    ELSE 'needs_admin_review'
                END as evaluation_status,
                JSON_UNQUOTE(JSON_EXTRACT(arq.flagged_reasons, '$[0]')) as flagged_reason,
                COALESCE(arq.admin_decision, 'pending') as admin_decision,
                arq.flagged_at
            FROM admin_review_queue arq
            WHERE arq.admin_decision = 'pending'
        ");
        
        $migratedReviews = $migrateReviewStmt->execute() ? $migrateReviewStmt->rowCount() : 0;
        echo "✓ Migrated {$migratedReviews} pending reviews\n";
        
    } else {
        echo "No existing authenticity data found, starting fresh\n";
    }
    
    echo "\n";
    
    // Step 3: Update practice uploads to work with new system
    echo "Step 3: Updating practice uploads...\n";
    
    // Add new columns if they don't exist
    try {
        $pdo->exec("ALTER TABLE practice_uploads ADD COLUMN IF NOT EXISTS authenticity_status ENUM('pending', 'verified', 'flagged', 'approved') DEFAULT 'pending'");
        $pdo->exec("ALTER TABLE practice_uploads ADD COLUMN IF NOT EXISTS progress_approved TINYINT(1) DEFAULT 0");
        echo "✓ Updated practice_uploads table structure\n";
    } catch (Exception $e) {
        echo "Note: practice_uploads already updated or error: " . $e->getMessage() . "\n";
    }
    
    // Update learning_progress table
    try {
        $pdo->exec("ALTER TABLE learning_progress ADD COLUMN IF NOT EXISTS practice_admin_approved TINYINT(1) DEFAULT 0");
        echo "✓ Updated learning_progress table structure\n";
    } catch (Exception $e) {
        echo "Note: learning_progress already updated or error: " . $e->getMessage() . "\n";
    }
    
    // Step 4: Set default categories for tutorials
    echo "Step 4: Setting tutorial categories...\n";
    
    $categoryUpdates = [
        'embroidery' => ['embroidery', 'stitch', 'needle', 'thread'],
        'painting' => ['paint', 'canvas', 'brush', 'acrylic', 'watercolor'],
        'drawing' => ['draw', 'sketch', 'pencil', 'charcoal'],
        'crafts' => ['craft', 'diy', 'handmade', 'creative'],
        'jewelry' => ['jewelry', 'bead', 'wire', 'pendant'],
        'pottery' => ['pottery', 'clay', 'ceramic', 'wheel'],
        'woodwork' => ['wood', 'carving', 'furniture', 'timber'],
        'textile' => ['fabric', 'sewing', 'quilting', 'weaving'],
        'photography' => ['photo', 'camera', 'lens', 'portrait'],
        'digital_art' => ['digital', 'photoshop', 'illustrator', 'graphic']
    ];
    
    $categorizedCount = 0;
    foreach ($categoryUpdates as $category => $keywords) {
        $keywordPattern = implode('|', $keywords);
        $stmt = $pdo->prepare("
            UPDATE tutorials 
            SET category = ? 
            WHERE (LOWER(title) REGEXP ? OR LOWER(description) REGEXP ?)
            AND (category IS NULL OR category = '' OR category = 'general')
        ");
        $stmt->execute([$category, $keywordPattern, $keywordPattern]);
        $categorizedCount += $stmt->rowCount();
    }
    
    // Set remaining tutorials to 'general'
    $stmt = $pdo->exec("UPDATE tutorials SET category = 'general' WHERE category IS NULL OR category = ''");
    
    echo "✓ Categorized {$categorizedCount} tutorials\n";
    
    // Step 5: Create sample admin user if needed
    echo "Step 5: Ensuring admin user exists...\n";
    
    try {
        // Check if admin user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@mylittlethingz.com'");
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            // Create admin user
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, created_at) 
                VALUES ('Admin User', 'admin@mylittlethingz.com', ?, NOW())
            ");
            $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
            $adminId = $pdo->lastInsertId();
            
            // Add admin role
            $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, 1)");
            $stmt->execute([$adminId]);
            
            echo "✓ Created admin user (admin@mylittlethingz.com / admin123)\n";
        } else {
            echo "✓ Admin user already exists\n";
        }
    } catch (Exception $e) {
        echo "Note: Could not create admin user: " . $e->getMessage() . "\n";
    }
    
    // Step 6: Generate summary report
    echo "\nStep 6: Migration Summary\n";
    echo "========================\n";
    
    // Count records in new tables
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM image_authenticity_simple");
    $authCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_review_simple WHERE admin_decision = 'pending'");
    $pendingCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tutorials WHERE category != 'general'");
    $categorizedTutorials = $stmt->fetch()['count'];
    
    echo "• Authenticity records: {$authCount}\n";
    echo "• Pending reviews: {$pendingCount}\n";
    echo "• Categorized tutorials: {$categorizedTutorials}\n";
    
    // System configuration summary
    echo "\nSystem Configuration:\n";
    echo "• Detection method: Perceptual hash (pHash) only\n";
    echo "• Similarity threshold: Hamming distance ≤ 5\n";
    echo "• Comparison scope: Same category only\n";
    echo "• Evaluation states: unique, reused, highly_similar, needs_admin_review\n";
    echo "• Admin approval required for progress credit\n";
    echo "• Certificate eligibility: 80% progress + admin-approved practice\n";
    
    echo "\nNext Steps:\n";
    echo "1. Test the new system with sample uploads\n";
    echo "2. Train admins on the simplified review interface\n";
    echo "3. Update frontend to use corrected APIs\n";
    echo "4. Monitor false positive rates and adjust if needed\n";
    
    echo "\nAPI Endpoints:\n";
    echo "• Practice Upload: /backend/api/pro/practice-upload-corrected.php\n";
    echo "• Admin Review: /backend/api/admin/simple-authenticity-review.php\n";
    echo "• Certificate: /backend/api/pro/certificate-corrected.php\n";
    echo "• Admin Dashboard: /frontend/admin/simple-authenticity-dashboard.html\n";
    
    echo "\n=== Migration Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// Optional: Create rollback script
file_put_contents(__DIR__ . '/rollback-simplified-authenticity.sql', "
-- Rollback script for simplified authenticity system
-- Run this if you need to revert changes

-- Drop new tables
DROP TABLE IF EXISTS image_authenticity_simple;
DROP TABLE IF EXISTS admin_review_simple;

-- Remove new columns from existing tables
ALTER TABLE practice_uploads DROP COLUMN IF EXISTS authenticity_status;
ALTER TABLE practice_uploads DROP COLUMN IF EXISTS progress_approved;
ALTER TABLE learning_progress DROP COLUMN IF EXISTS practice_admin_approved;

-- Reset tutorial categories to general
UPDATE tutorials SET category = 'general';

-- Note: This rollback does not restore old complex system data
-- You would need to restore from backup for full rollback
");

echo "\nRollback script created: rollback-simplified-authenticity.sql\n";
?>