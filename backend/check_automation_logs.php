<?php
// Check if automation log file exists
$logFile = __DIR__ . '/logs/shiprocket_automation.log';

echo "=== CHECKING AUTOMATION LOGS ===\n\n";

if (file_exists($logFile)) {
    echo "Log file exists at: $logFile\n\n";
    echo "Last 50 lines:\n";
    echo str_repeat("=", 80) . "\n";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    echo implode("", $lastLines);
} else {
    echo "Log file does NOT exist at: $logFile\n";
    echo "This means automation has never run or logging is disabled.\n";
}

echo "\n\n=== CHECKING PHP ERROR LOG ===\n\n";
$phpErrorLog = ini_get('error_log');
if ($phpErrorLog && file_exists($phpErrorLog)) {
    echo "PHP error log: $phpErrorLog\n\n";
    $lines = file($phpErrorLog);
    $lastLines = array_slice($lines, -30);
    echo "Last 30 lines:\n";
    echo str_repeat("=", 80) . "\n";
    echo implode("", $lastLines);
} else {
    echo "PHP error log not found or not configured.\n";
}

echo "\n\n=== CHECKING APACHE ERROR LOG ===\n";
$apacheLog = 'c:/xampp/apache/logs/error.log';
if (file_exists($apacheLog)) {
    echo "Apache error log exists\n\n";
    $lines = file($apacheLog);
    $lastLines = array_slice($lines, -30);
    echo "Last 30 lines:\n";
    echo str_repeat("=", 80) . "\n";
    echo implode("", $lastLines);
} else {
    echo "Apache error log not found.\n";
}