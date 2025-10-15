<?php
/**
 * Shiprocket Token Generator
 * 
 * This script helps you generate a new Shiprocket API token
 * using your email and password.
 */

header('Content-Type: application/json');

// Get credentials from POST or use defaults
$email = $_POST['email'] ?? 'fathima686231@gmail.com';
$password = $_POST['password'] ?? '';

if (empty($password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Password is required',
        'usage' => 'POST to this script with email and password',
        'example' => [
            'email' => 'your-email@example.com',
            'password' => 'your-password'
        ]
    ]);
    exit;
}

try {
    // Shiprocket authentication endpoint
    $url = 'https://apiv2.shiprocket.in/v1/external/auth/login';
    
    $data = [
        'email' => $email,
        'password' => $password
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        
        if (isset($result['token'])) {
            // Parse token expiry
            $token = $result['token'];
            $parts = explode('.', $token);
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            $expiryTimestamp = $payload['exp'] ?? null;
            $expiryDate = $expiryTimestamp ? date('Y-m-d H:i:s', $expiryTimestamp) : 'Unknown';
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Token generated successfully!',
                'token' => $token,
                'company_id' => $result['company_id'] ?? null,
                'email' => $result['email'] ?? null,
                'id' => $result['id'] ?? null,
                'first_name' => $result['first_name'] ?? null,
                'last_name' => $result['last_name'] ?? null,
                'expires_at' => $expiryDate,
                'expires_timestamp' => $expiryTimestamp,
                'instructions' => [
                    '1. Copy the token above',
                    '2. Update backend/config/shiprocket.php',
                    '3. Replace the old token with this new one',
                    '4. Update the expires_at timestamp if needed'
                ],
                'config_format' => [
                    'company_id' => $result['company_id'] ?? null,
                    'email' => $result['email'] ?? null,
                    'id' => $result['id'] ?? null,
                    'first_name' => $result['first_name'] ?? null,
                    'last_name' => $result['last_name'] ?? null,
                    'token' => $token,
                    'token_expiry' => $expiryTimestamp,
                    'base_url' => 'https://apiv2.shiprocket.in/v1/external'
                ]
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Token not found in response',
                'response' => $result
            ], JSON_PRETTY_PRINT);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Authentication failed',
            'http_code' => $httpCode,
            'response' => json_decode($response, true)
        ], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>