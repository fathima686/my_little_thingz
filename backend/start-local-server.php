<?php
/**
 * Local Development Server Starter
 * This script helps start a local PHP server for development
 */

// Check if we're running from command line
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'info',
        'message' => 'This script is meant to be run from command line',
        'instructions' => [
            'Open terminal/command prompt',
            'Navigate to your project directory',
            'Run: php -S localhost:8000 -t .',
            'Or run: php backend/start-local-server.php'
        ]
    ]);
    exit;
}

echo "🚀 Starting My Little Thingz Development Server...\n";
echo "📁 Project Directory: " . __DIR__ . "\n";
echo "🌐 Server will be available at: http://localhost:8000\n";
echo "📋 API Base URL: http://localhost:8000/backend/api\n";
echo "🛑 Press Ctrl+C to stop the server\n\n";

// Change to project root directory
$projectRoot = dirname(__DIR__);
chdir($projectRoot);

echo "📂 Changed to directory: " . getcwd() . "\n";
echo "⏳ Starting PHP built-in server...\n\n";

// Start the PHP built-in server
$command = 'php -S localhost:8000 -t .';
passthru($command);
?>