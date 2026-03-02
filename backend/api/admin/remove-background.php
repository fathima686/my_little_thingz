<?php
/**
 * Background Removal API
 * 
 * Removes background from images using remove.bg API
 * Fallback to client-side processing if API key not available
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Only POST method allowed'
    ]);
    exit;
}

// Load .env file
function loadEnvFile($filePath) {
    if (!file_exists($filePath)) {
        return;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load config file
include_once __DIR__ . '/config.php';

// Load .env file from backend directory
loadEnvFile(__DIR__ . '/../../.env');

try {
    // Get the image data
    $imageData = null;
    $imageUrl = null;
    $isBase64 = false; // Flag to track if data is base64
    
    // Check if image is uploaded as file
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageData = file_get_contents($_FILES['image']['tmp_name']);
        error_log('Received file upload, size: ' . strlen($imageData) . ' bytes');
        
        // Validate it's actually an image
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])) {
            throw new Exception('Invalid image format. Supported: JPEG, PNG, WebP');
        }
        
        error_log('Image validated, MIME type: ' . $mimeType);
    }
    // Check if image is sent as base64
    elseif (isset($_POST['image_base64'])) {
        $base64 = $_POST['image_base64'];
        
        // Log what we received for debugging
        error_log('Received base64 data, first 50 chars: ' . substr($base64, 0, 50));
        
        // Remove data:image/...;base64, prefix if present
        if (strpos($base64, 'data:image') === 0) {
            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
            error_log('Removed data URL prefix');
        }
        
        // Validate base64 data
        if (empty($base64) || strlen($base64) < 100) {
            throw new Exception('Invalid or empty base64 data received');
        }
        
        // Test if it's valid base64
        $testDecode = base64_decode($base64, true);
        if ($testDecode === false) {
            error_log('Invalid base64 string received');
            throw new Exception('Invalid base64 encoding');
        }
        
        error_log('Base64 validation passed, length: ' . strlen($base64));
        
        // For remove.bg API, we need the base64 string, not decoded binary data
        $imageData = $base64; // Keep as base64 string
        $isBase64 = true; // Flag to indicate this is base64 data
    }
    // Check if image URL is provided
    elseif (isset($_POST['image_url'])) {
        $imageUrl = $_POST['image_url'];
    }
    else {
        throw new Exception('No image data provided');
    }
    
    // Get remove.bg API key from environment or config
    $removeBgApiKey = defined('REMOVE_BG_API_KEY') ? REMOVE_BG_API_KEY : (getenv('REMOVE_BG_API_KEY') ?: ($_ENV['REMOVE_BG_API_KEY'] ?? null));
    
    // Debug: Log API key status (first 10 characters only for security)
    error_log('Remove.bg API Key status: ' . ($removeBgApiKey ? 'Found (' . substr($removeBgApiKey, 0, 10) . '...)' : 'Not found'));
    
    if ($removeBgApiKey && strlen($removeBgApiKey) > 10) {
        // Use remove.bg API
        error_log('Using remove.bg API for background removal');
        $result = removeBackgroundWithAPI($imageData, $imageUrl, $removeBgApiKey, $isBase64 ?? false);
    } else {
        // Fallback: Return original image with instructions
        error_log('API key not configured, using fallback');
        $result = [
            'success' => false,
            'error' => 'Background removal API key not configured',
            'fallback' => true,
            'message' => 'Please configure REMOVE_BG_API_KEY in your environment variables or use client-side processing',
            'debug' => [
                'api_key_found' => !empty($removeBgApiKey),
                'api_key_length' => $removeBgApiKey ? strlen($removeBgApiKey) : 0,
                'env_loaded' => file_exists(__DIR__ . '/../../.env')
            ]
        ];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function removeBackgroundWithAPI($imageData, $imageUrl, $apiKey, $isBase64 = false) {
    $ch = curl_init('https://api.remove.bg/v1.0/removebg');
    
    if ($imageUrl) {
        // Use image URL
        $postFields = [
            'image_url' => $imageUrl,
            'size' => 'auto',
            'format' => 'png'
        ];
        error_log('Using image URL for remove.bg');
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postFields),
            CURLOPT_HTTPHEADER => [
                'X-Api-Key: ' . $apiKey,
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);
    } else {
        // Use binary image data (not base64)
        // Remove.bg prefers binary data over base64
        if ($isBase64) {
            $imageData = base64_decode($imageData);
        }
        
        error_log('Sending binary image to remove.bg, size: ' . strlen($imageData) . ' bytes');
        
        // Create multipart form data manually
        $boundary = '----WebKitFormBoundary' . uniqid();
        $postData = '';
        
        // Add size parameter
        $postData .= "--{$boundary}\r\n";
        $postData .= "Content-Disposition: form-data; name=\"size\"\r\n\r\n";
        $postData .= "auto\r\n";
        
        // Add image file
        $postData .= "--{$boundary}\r\n";
        $postData .= "Content-Disposition: form-data; name=\"image_file\"; filename=\"image.jpg\"\r\n";
        $postData .= "Content-Type: image/jpeg\r\n\r\n";
        $postData .= $imageData . "\r\n";
        $postData .= "--{$boundary}--\r\n";
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => [
                'X-Api-Key: ' . $apiKey,
                'Content-Type: multipart/form-data; boundary=' . $boundary,
                'Content-Length: ' . strlen($postData)
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('cURL error: ' . $error);
    }
    
    if ($httpCode === 200) {
        // Success - return the processed image as base64
        $base64Image = base64_encode($response);
        return [
            'success' => true,
            'image' => 'data:image/png;base64,' . $base64Image,
            'format' => 'png'
        ];
    } else {
        // API error
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['errors'][0]['title'] ?? 'Unknown error';
        
        throw new Exception('Remove.bg API error: ' . $errorMessage . ' (HTTP ' . $httpCode . ')');
    }
}
?>
