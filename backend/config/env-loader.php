<?php
/**
 * Environment Variable Loader
 * Loads .env file and makes variables available via getenv() and $_ENV
 */

class EnvLoader {
    private static $loaded = false;
    
    public static function load($envPath = null) {
        if (self::$loaded) {
            return;
        }
        
        if ($envPath === null) {
            $envPath = __DIR__ . '/../.env';
        }
        
        if (!file_exists($envPath)) {
            error_log("Warning: .env file not found at: $envPath");
            self::$loaded = true;
            return;
        }
        
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                // Set in environment
                if (!empty($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
        
        self::$loaded = true;
    }
}
