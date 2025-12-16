<?php
// Lightweight config for tutorial Razorpay integrations.
// Reads credentials from environment only; do NOT hardcode secrets here.
// For local dev, you can create backend/.env with:
// RAZORPAY_KEY_ID=your_key
// RAZORPAY_KEY_SECRET=your_secret

/**
 * Best-effort loader for a local .env file (git-ignored).
 */
function rp_load_dotenv_if_present()
{
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath) || !is_readable($envPath)) {
        error_log("Razorpay config: .env file not found or not readable at: $envPath");
        return;
    }

    $content = file_get_contents($envPath);
    if ($content === false) {
        error_log("Razorpay config: Failed to read .env file");
        return;
    }

    // Remove BOM (Byte Order Mark) if present
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
    }
    // Also handle UTF-8 BOM in different format
    if (substr($content, 0, 1) === "\xEF" && ord(substr($content, 0, 1)) === 239) {
        $content = ltrim($content, "\xEF\xBB\xBF");
    }

    // Handle both Unix and Windows line endings
    $lines = preg_split('/\r?\n/', $content);
    if (!$lines) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue; // skip empty lines and comments
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $name = trim($parts[0]);
        $value = trim($parts[1]);
        if ($name === '') {
            continue;
        }
        // Only set if not already in environment
        if (getenv($name) === false) {
            putenv("$name=$value");
            error_log("Razorpay config: Set $name from .env file");
        }
    }
}

rp_load_dotenv_if_present();

$keyId = getenv('RAZORPAY_KEY_ID');
$keySecret = getenv('RAZORPAY_KEY_SECRET');

// Fallback: If putenv() didn't work, read directly from .env file
if (!$keyId || !$keySecret) {
    $envPath = __DIR__ . '/../.env';
    if (file_exists($envPath) && is_readable($envPath)) {
        $content = file_get_contents($envPath);
        // Remove BOM (Byte Order Mark) if present
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }
        // Also handle UTF-8 BOM in different format
        $content = ltrim($content, "\xEF\xBB\xBF");
        $lines = preg_split('/\r?\n/', $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);
                if ($name === 'RAZORPAY_KEY_ID' && !$keyId) {
                    $keyId = $value;
                }
                if ($name === 'RAZORPAY_KEY_SECRET' && !$keySecret) {
                    $keySecret = $value;
                }
            }
        }
    }
}

// Debug logging
error_log("Razorpay config: KEY_ID = " . ($keyId ? substr($keyId, 0, 10) . '...' : 'NOT SET'));
error_log("Razorpay config: KEY_SECRET = " . ($keySecret ? substr($keySecret, 0, 10) . '...' : 'NOT SET'));

if (!$keyId || !$keySecret) {
    $envPath = __DIR__ . '/../.env';
    $envExists = file_exists($envPath) ? 'exists' : 'does NOT exist';
    $envReadable = file_exists($envPath) && is_readable($envPath) ? 'readable' : 'not readable';
    throw new RuntimeException("Razorpay keys not set. Please configure RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in the server environment or backend/.env (git-ignored). .env file $envExists at: $envPath and is $envReadable");
}

define('RAZORPAY_KEY', $keyId);
define('RAZORPAY_SECRET', $keySecret);


