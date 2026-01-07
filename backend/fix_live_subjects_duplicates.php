<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Fixing duplicate live subjects...\n";

    // 1. Ensure UNIQUE constraint exists on name column
    echo "1. Ensuring UNIQUE constraint on name column...\n";
    try {
        // Check if unique constraint exists
        $checkStmt = $db->query("
            SELECT COUNT(*) as cnt 
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'live_subjects' 
            AND CONSTRAINT_TYPE = 'UNIQUE' 
            AND CONSTRAINT_NAME LIKE '%name%'
        ");
        $hasUnique = $checkStmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
        
        if (!$hasUnique) {
            // Remove duplicates first, then add unique constraint
            echo "   Removing duplicate entries...\n";
            
            // Find duplicates
            $dupStmt = $db->query("
                SELECT name, COUNT(*) as cnt, GROUP_CONCAT(id ORDER BY id) as ids
                FROM live_subjects
                GROUP BY name
                HAVING cnt > 1
            ");
            $duplicates = $dupStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($duplicates as $dup) {
                $ids = explode(',', $dup['ids']);
                // Keep the first ID, delete the rest
                $keepId = array_shift($ids);
                $deleteIds = implode(',', $ids);
                
                // Update any sessions pointing to deleted subjects
                $db->exec("
                    UPDATE live_sessions 
                    SET subject_id = {$keepId} 
                    WHERE subject_id IN ({$deleteIds})
                ");
                
                // Delete duplicate subjects
                $db->exec("DELETE FROM live_subjects WHERE id IN ({$deleteIds})");
                echo "   ✓ Removed duplicates for '{$dup['name']}' (kept ID: {$keepId})\n";
            }
            
            // Add unique constraint
            $db->exec("ALTER TABLE live_subjects ADD UNIQUE KEY unique_name (name)");
            echo "   ✓ Added UNIQUE constraint on name column\n";
        } else {
            echo "   ✓ UNIQUE constraint already exists\n";
        }
    } catch (Exception $e) {
        echo "   ⚠ Error: " . $e->getMessage() . "\n";
    }

    // 2. Clean up any remaining duplicates (case-insensitive)
    echo "\n2. Cleaning up case-insensitive duplicates...\n";
    $caseDupStmt = $db->query("
        SELECT LOWER(name) as lower_name, COUNT(*) as cnt, GROUP_CONCAT(id ORDER BY id) as ids, GROUP_CONCAT(name) as names
        FROM live_subjects
        GROUP BY LOWER(name)
        HAVING cnt > 1
    ");
    $caseDuplicates = $caseDupStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($caseDuplicates as $dup) {
        $ids = explode(',', $dup['ids']);
        $names = explode(',', $dup['names']);
        // Keep the first one (prefer exact case match)
        $keepId = array_shift($ids);
        $keepName = array_shift($names);
        $deleteIds = implode(',', $ids);
        
        // Update sessions
        $db->exec("
            UPDATE live_sessions 
            SET subject_id = {$keepId} 
            WHERE subject_id IN ({$deleteIds})
        ");
        
        // Delete duplicates
        $db->exec("DELETE FROM live_subjects WHERE id IN ({$deleteIds})");
        echo "   ✓ Merged case variants for '{$dup['lower_name']}' (kept: '{$keepName}', ID: {$keepId})\n";
    }
    
    if (empty($caseDuplicates)) {
        echo "   ✓ No case-insensitive duplicates found\n";
    }

    // 3. Show final count
    $countStmt = $db->query("SELECT COUNT(*) as cnt FROM live_subjects WHERE is_active = 1");
    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "\n✅ Done! Total unique active subjects: {$count}\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>


