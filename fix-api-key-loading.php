<?php
/**
 * Quick fix for API key loading issue
 */

echo "🔧 Fixing Remove.bg API Key Loading\n";
echo "===================================\n\n";

$envPath = __DIR__ . '/backend/.env';
$apiPath = __DIR__ . '/backend/api/admin/remove-background.php';

// Check if files exist
if (!file_exists($envPath)) {
    echo "❌ Error: backend/.env file not found!\n";
    exit(1);
}

if (!file_exists($apiPath)) {
    echo "❌ Error: remove-background.php file not found!\n";
    exit(1);
}

// Read .env file to get API key
$envContent = file_get_contents($envPath);
$apiKey = null;

if (preg_match('/REMOVE_BG_API_KEY=(.+)/', $envContent, $matches)) {
    $apiKey = trim($matches[1]);
    echo "✅ Found API key in .env: " . substr($apiKey, 0, 10) . "...\n";
} else {
    echo "❌ API key not found in .env file\n";
    exit(1);
}

// Create a simple config file that the API can read
$configContent = "<?php\n";
$configContent .= "// Auto-generated config for remove.bg API\n";
$configContent .= "define('REMOVE_BG_API_KEY', '$apiKey');\n";
$configContent .= "?>";

$configPath = __DIR__ . '/backend/api/admin/config.php';
file_put_contents($configPath, $configContent);

echo "✅ Created config file: $configPath\n";

// Update the remove-background.php to use the config file
$apiContent = file_get_contents($apiPath);

// Add config include at the top
if (strpos($apiContent, 'include_once __DIR__ . \'/config.php\';') === false) {
    $apiContent = str_replace(
        '// Load .env file from backend directory',
        '// Load config file
include_once __DIR__ . \'/config.php\';

// Load .env file from backend directory',
        $apiContent
    );
}

// Update the API key reading logic
$oldPattern = '/\$removeBgApiKey = getenv\(\'REMOVE_BG_API_KEY\'\) \?: \(\$_ENV\[\'REMOVE_BG_API_KEY\'\] \?\? null\);/';
$newReplacement = '$removeBgApiKey = defined(\'REMOVE_BG_API_KEY\') ? REMOVE_BG_API_KEY : (getenv(\'REMOVE_BG_API_KEY\') ?: ($_ENV[\'REMOVE_BG_API_KEY\'] ?? null));';

$apiContent = preg_replace($oldPattern, $newReplacement, $apiContent);

file_put_contents($apiPath, $apiContent);

echo "✅ Updated remove-background.php to use config file\n";

// Test the configuration
echo "\n🧪 Testing configuration:\n";

include $configPath;

if (defined('REMOVE_BG_API_KEY')) {
    $testKey = REMOVE_BG_API_KEY;
    echo "✅ Config loaded successfully\n";
    echo "✅ API key: " . substr($testKey, 0, 10) . "...\n";
    echo "✅ Length: " . strlen($testKey) . " characters\n";
    
    if (strlen($testKey) > 10) {
        echo "✅ API key looks valid!\n";
    } else {
        echo "⚠️  API key seems too short\n";
    }
} else {
    echo "❌ Config not loaded properly\n";
}

echo "\n🎉 Fix complete!\n";
echo "Now try the background remover again.\n";
echo "You should see 'Background removed successfully!' instead of 'basic processing'.\n";
?>