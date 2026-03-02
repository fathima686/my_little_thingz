<?php
/**
 * Setup MySQL limits for large data handling
 */

require_once "database.php";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Setting up MySQL limits for large data handling...\n";
    
    // Increase limits
    $pdo->exec("SET GLOBAL max_allowed_packet = 67108864"); // 64MB
    $pdo->exec("SET GLOBAL net_buffer_length = 32768"); // 32KB
    
    // Check current settings
    $result = $pdo->query("SHOW VARIABLES LIKE 'max_allowed_packet'");
    $packet = $result->fetch();
    
    $result = $pdo->query("SHOW VARIABLES LIKE 'net_buffer_length'");
    $buffer = $result->fetch();
    
    echo "✓ max_allowed_packet: " . number_format($packet['Value']) . " bytes (" . round($packet['Value']/1024/1024, 1) . " MB)\n";
    echo "✓ net_buffer_length: " . number_format($buffer['Value']) . " bytes (" . round($buffer['Value']/1024, 1) . " KB)\n";
    
    echo "\nMySQL limits configured successfully!\n";
    echo "Note: These settings will reset when MySQL restarts.\n";
    echo "For permanent settings, add to your MySQL configuration file (my.cnf or my.ini):\n";
    echo "[mysqld]\n";
    echo "max_allowed_packet = 64M\n";
    echo "net_buffer_length = 32K\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>