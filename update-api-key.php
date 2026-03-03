<?php
/**
 * Simple script to update remove.bg API key in .env file
 * Usage: php update-api-key.php YOUR_API_KEY_HERE
 */

if ($argc < 2) {
    echo "❌ Usage: php update-api-key.php YOUR_API_KEY_HERE\n";
    echo "📝 Example: php update-api-key.php abc123def456ghi789\n";
    exit(1);
}

$apiKey = $argv[1];
$envFile = __DIR__ . '/backend/.env';

if (!file_exists($envFile)) {
    echo "❌ Error: backend/.env file not found!\n";
    exit(1);
}

// Read current .env content
$content = file_get_contents($envFile);

// Replace the API key line
if (strpos($content, 'REMOVE_BG_API_KEY=') !== false) {
    // Update existing line
    $content = preg_replace('/REMOVE_BG_API_KEY=.*/', 'REMOVE_BG_API_KEY=' . $apiKey, $content);
    echo "✅ Updated existing REMOVE_BG_API_KEY\n";
} else {
    // Add new line
    $content .= "\n# Remove.bg API for Professional Background Removal\n";
    $content .= "REMOVE_BG_API_KEY=" . $apiKey . "\n";
    echo "✅ Added new REMOVE_BG_API_KEY\n";
}

// Write back to file
if (file_put_contents($envFile, $content)) {
    echo "✅ Successfully updated backend/.env\n";
    echo "📝 API Key: " . substr($apiKey, 0, 10) . "...\n";
    echo "🔄 Please restart your server for changes to take effect\n";
    echo "\n";
    echo "🎉 Ready! Now your background remover will use professional AI processing!\n";
} else {
    echo "❌ Error: Could not write to backend/.env file\n";
    exit(1);
}
?>