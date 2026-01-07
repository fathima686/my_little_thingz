<?php
/**
 * Assignment Management Database Setup Script
 * Integrates with existing tutorial system
 */

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "my_little_thingz";

try {
    // Connect to database
    $mysqli = new mysqli($host, $username, $password, $database);
    $mysqli->set_charset('utf8mb4');
    
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "Connected to database successfully.\n";
    
    // Read and execute the schema file
    $schemaFile = __DIR__ . '/database/assignment_management_schema.sql';
    
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $sql = file_get_contents($schemaFile);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    echo "Executing " . count($statements) . " SQL statements...\n";
    
    // Execute each statement
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) continue;
        
        echo "Executing statement " . ($index + 1) . "...\n";
        
        if (!$mysqli->query($statement)) {
            echo "Warning: Statement " . ($index + 1) . " failed: " . $mysqli->error . "\n";
            echo "Statement: " . substr($statement, 0, 100) . "...\n";
        }
    }
    
    // Verify tables were created
    $tables = ['subjects', 'topics', 'assignments', 'submissions', 'evaluations', 'assignment_audit_log'];
    
    echo "\nVerifying table creation:\n";
    foreach ($tables as $table) {
        $result = $mysqli->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "✓ Table '$table' created successfully\n";
        } else {
            echo "✗ Table '$table' not found\n";
        }
    }
    
    // Check if subjects have been populated
    $result = $mysqli->query("SELECT COUNT(*) as count FROM subjects");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "\n✓ Subjects table populated with " . $row['count'] . " subjects\n";
    }
    
    // Check if topics have been populated
    $result = $mysqli->query("SELECT COUNT(*) as count FROM topics");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✓ Topics table populated with " . $row['count'] . " topics\n";
    }
    
    // Show subject-topic hierarchy
    echo "\nSubject-Topic Hierarchy:\n";
    $result = $mysqli->query("
        SELECT s.name as subject, t.name as topic 
        FROM subjects s 
        LEFT JOIN topics t ON s.id = t.subject_id 
        ORDER BY s.name, t.name
    ");
    
    if ($result) {
        $currentSubject = '';
        while ($row = $result->fetch_assoc()) {
            if ($row['subject'] !== $currentSubject) {
                $currentSubject = $row['subject'];
                echo "\n📚 " . $currentSubject . ":\n";
            }
            if ($row['topic']) {
                echo "  - " . $row['topic'] . "\n";
            }
        }
    }
    
    echo "\n🎉 Assignment Management database setup completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Verify the database structure in your MySQL client\n";
    echo "2. Test the API endpoints once they are implemented\n";
    echo "3. Check the assignment_hierarchy view for subject-topic relationships\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

$mysqli->close();
?>