<?php
// Fix the upload process to sync images with custom_requests table
echo "<h1>🔧 Fix Upload Image Sync</h1>";

try {
    require_once 'backend/config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Step 1: Add image_url column to custom_requests if it doesn't exist
    echo "<h2>📋 Step 1: Ensuring image_url column exists</h2>";
    
    try {
        $pdo->exec("ALTER TABLE custom_requests ADD COLUMN image_url VARCHAR(500) DEFAULT '' AFTER description");
        echo "<p style='color: green;'>✅ Added image_url column to custom_requests table</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: blue;'>📋 image_url column already exists</p>";
        } else {
            throw $e;
        }
    }
    
    // Step 2: Update existing requests with their first image
    echo "<h2>🖼️ Step 2: Syncing existing images</h2>";
    
    $requests = $pdo->query("SELECT id, order_id, title, image_url FROM custom_requests")->fetchAll();
    $syncedCount = 0;
    
    foreach ($requests as $request) {
        if (empty($request['image_url'])) {
            // Get first image for this request
            $imageStmt = $pdo->prepare("
                SELECT image_url, filename 
                FROM custom_request_images 
                WHERE request_id = ? 
                ORDER BY uploaded_at ASC 
                LIMIT 1
            ");
            $imageStmt->execute([$request['id']]);
            $image = $imageStmt->fetch();
            
            if ($image) {
                // Update custom_requests with image URL
                $updateStmt = $pdo->prepare("UPDATE custom_requests SET image_url = ? WHERE id = ?");
                $updateStmt->execute([$image['image_url'], $request['id']]);
                
                echo "<p style='color: green;'>✅ Synced Request {$request['id']}: {$request['title']} with image: {$image['filename']}</p>";
                $syncedCount++;
            }
        } else {
            echo "<p style='color: blue;'>📋 Request {$request['id']}: {$request['title']} already has image</p>";
        }
    }
    
    echo "<p><strong>Synced $syncedCount requests with images</strong></p>";
    
    // Step 3: Create the fixed upload API
    echo "<h2>🔧 Step 3: Creating fixed upload API</h2>";
    
    $fixedUploadApi = '<?php
header(\'Content-Type: application/json\');
header(\'Access-Control-Allow-Origin: *\');
header(\'Access-Control-Allow-Methods: POST, OPTIONS\');
header(\'Access-Control-Allow-Headers: Content-Type, Authorization\');

if ($_SERVER[\'REQUEST_METHOD\'] === \'OPTIONS\') {
    http_response_code(204);
    exit;
}

if ($_SERVER[\'REQUEST_METHOD\'] !== \'POST\') {
    http_response_code(405);
    echo json_encode([\'status\' => \'error\', \'message\' => \'Only POST method allowed\']);
    exit;
}

try {
    require_once \'../../config/database.php\';
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([\'status\' => \'error\', \'message\' => \'Database connection failed\']);
    exit;
}

try {
    // Ensure image_url column exists
    try {
        $pdo->exec("ALTER TABLE custom_requests ADD COLUMN image_url VARCHAR(500) DEFAULT \'\' AFTER description");
    } catch (Exception $e) {
        // Column already exists, ignore
    }
    
    // Handle both new request creation and image upload to existing request
    if (isset($_POST[\'action\']) && $_POST[\'action\'] === \'upload_image\') {
        // Upload image to existing request
        $requestId = $_POST[\'request_id\'] ?? \'\';
        
        if (empty($requestId)) {
            http_response_code(400);
            echo json_encode([\'status\' => \'error\', \'message\' => \'Request ID is required\']);
            exit;
        }
        
        // Verify request exists
        $checkStmt = $pdo->prepare("SELECT id, title FROM custom_requests WHERE id = ?");
        $checkStmt->execute([$requestId]);
        $request = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            http_response_code(404);
            echo json_encode([\'status\' => \'error\', \'message\' => \'Request not found\']);
            exit;
        }
        
        if (!isset($_FILES[\'reference_image\']) || $_FILES[\'reference_image\'][\'error\'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode([\'status\' => \'error\', \'message\' => \'No valid image file uploaded\']);
            exit;
        }
        
        $file = $_FILES[\'reference_image\'];
        $allowedTypes = [\'image/jpeg\', \'image/png\', \'image/gif\', \'image/webp\'];
        
        if (!in_array($file[\'type\'], $allowedTypes)) {
            http_response_code(400);
            echo json_encode([\'status\' => \'error\', \'message\' => \'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed\']);
            exit;
        }
        
        // Create upload directory if it doesn\'t exist
        $uploadDir = __DIR__ . \'/../../uploads/custom-requests/\';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file[\'name\'], PATHINFO_EXTENSION);
        $filename = \'cr_\' . $requestId . \'_\' . date(\'Ymd_His\') . \'_\' . uniqid() . \'.\' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file[\'tmp_name\'], $filepath)) {
            $imageUrl = \'uploads/custom-requests/\' . $filename;
            
            // Save image reference to database
            $insertStmt = $pdo->prepare("
                INSERT INTO custom_request_images 
                (request_id, image_url, filename, original_filename, file_size, mime_type, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, ?, \'customer\')
            ");
            
            $insertStmt->execute([
                $requestId,
                $imageUrl,
                $filename,
                $file[\'name\'],
                $file[\'size\'],
                $file[\'type\']
            ]);
            
            $imageId = $pdo->lastInsertId();
            
            // FIXED: Update custom_requests table with image_url
            $updateStmt = $pdo->prepare("UPDATE custom_requests SET image_url = ?, updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([$imageUrl, $requestId]);
            
            echo json_encode([
                \'status\' => \'success\',
                \'message\' => \'Reference image uploaded successfully\',
                \'request_id\' => $requestId,
                \'image_id\' => $imageId,
                \'image_url\' => $imageUrl,
                \'full_url\' => \'http://localhost/my_little_thingz/backend/\' . $imageUrl,
                \'filename\' => $filename,
                \'original_filename\' => $file[\'name\'],
                \'file_size\' => $file[\'size\'],
                \'upload_time\' => date(\'Y-m-d H:i:s\')
            ]);
        } else {
            http_response_code(500);
            echo json_encode([\'status\' => \'error\', \'message\' => \'Failed to save uploaded file\']);
        }
        
    } else {
        // Create new custom request with optional images
        $customerName = $_POST[\'customer_name\'] ?? \'\';
        $customerEmail = $_POST[\'customer_email\'] ?? \'\';
        $customerPhone = $_POST[\'customer_phone\'] ?? \'\';
        $title = $_POST[\'title\'] ?? \'\';
        $occasion = $_POST[\'occasion\'] ?? \'\';
        $description = $_POST[\'description\'] ?? \'\';
        $requirements = $_POST[\'requirements\'] ?? \'\';
        $budgetMin = floatval($_POST[\'budget_min\'] ?? 500);
        $budgetMax = floatval($_POST[\'budget_max\'] ?? 1000);
        $deadline = $_POST[\'deadline\'] ?? date(\'Y-m-d\', strtotime(\'+30 days\'));
        
        if (empty($customerName) || empty($customerEmail) || empty($title)) {
            http_response_code(400);
            echo json_encode([\'status\' => \'error\', \'message\' => \'Customer name, email, and title are required\']);
            exit;
        }
        
        // Generate order ID
        $orderId = \'CR-\' . date(\'Ymd\') . \'-\' . strtoupper(substr(uniqid(), -6));
        
        // Insert custom request with placeholder image_url
        $insertStmt = $pdo->prepare("
            INSERT INTO custom_requests (
                order_id, customer_name, customer_email, customer_phone,
                title, occasion, description, requirements, budget_min, budget_max,
                deadline, status, source, image_url
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'submitted\', \'form\', \'\')
        ");
        
        $insertStmt->execute([
            $orderId,
            $customerName,
            $customerEmail,
            $customerPhone,
            $title,
            $occasion,
            $description,
            $requirements,
            $budgetMin,
            $budgetMax,
            $deadline
        ]);
        
        $requestId = $pdo->lastInsertId();
        $uploadedImages = [];
        $firstImageUrl = \'\';
        
        // Handle multiple reference images
        if (isset($_FILES[\'reference_images\'])) {
            $files = $_FILES[\'reference_images\'];
            
            // Create upload directory if it doesn\'t exist
            $uploadDir = __DIR__ . \'/../../uploads/custom-requests/\';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Handle multiple files
            if (is_array($files[\'name\'])) {
                $fileCount = count($files[\'name\']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($files[\'error\'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            \'name\' => $files[\'name\'][$i],
                            \'tmp_name\' => $files[\'tmp_name\'][$i],
                            \'size\' => $files[\'size\'][$i],
                            \'type\' => $files[\'type\'][$i]
                        ];
                        
                        $result = uploadSingleImage($pdo, $requestId, $file, $uploadDir);
                        $uploadedImages[] = $result;
                        
                        // Set first image as main image
                        if (empty($firstImageUrl) && !isset($result[\'error\'])) {
                            $firstImageUrl = $result[\'image_url\'];
                        }
                    }
                }
            } else {
                // Single file
                if ($files[\'error\'] === UPLOAD_ERR_OK) {
                    $result = uploadSingleImage($pdo, $requestId, $files, $uploadDir);
                    $uploadedImages[] = $result;
                    
                    if (!isset($result[\'error\'])) {
                        $firstImageUrl = $result[\'image_url\'];
                    }
                }
            }
        }
        
        // FIXED: Update custom_requests with first image URL
        if (!empty($firstImageUrl)) {
            $updateStmt = $pdo->prepare("UPDATE custom_requests SET image_url = ? WHERE id = ?");
            $updateStmt->execute([$firstImageUrl, $requestId]);
        }
        
        echo json_encode([
            \'status\' => \'success\',
            \'message\' => \'Custom request created successfully\',
            \'request_id\' => $requestId,
            \'order_id\' => $orderId,
            \'images_uploaded\' => count($uploadedImages),
            \'images\' => $uploadedImages,
            \'main_image_url\' => $firstImageUrl,
            \'created_at\' => date(\'Y-m-d H:i:s\')
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([\'status\' => \'error\', \'message\' => \'Database error: \' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([\'status\' => \'error\', \'message\' => \'Server error: \' . $e->getMessage()]);
}

function uploadSingleImage($pdo, $requestId, $file, $uploadDir) {
    $allowedTypes = [\'image/jpeg\', \'image/png\', \'image/gif\', \'image/webp\'];
    
    if (!in_array($file[\'type\'], $allowedTypes)) {
        return [\'error\' => \'Invalid file type: \' . $file[\'name\']];
    }
    
    if ($file[\'size\'] > 10 * 1024 * 1024) { // 10MB limit
        return [\'error\' => \'File too large: \' . $file[\'name\']];
    }
    
    // Generate unique filename
    $extension = pathinfo($file[\'name\'], PATHINFO_EXTENSION);
    $filename = \'cr_\' . $requestId . \'_\' . date(\'Ymd_His\') . \'_\' . uniqid() . \'.\' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file[\'tmp_name\'], $filepath)) {
        $imageUrl = \'uploads/custom-requests/\' . $filename;
        
        // Save image reference to database
        $insertStmt = $pdo->prepare("
            INSERT INTO custom_request_images 
            (request_id, image_url, filename, original_filename, file_size, mime_type, uploaded_by) 
            VALUES (?, ?, ?, ?, ?, ?, \'customer\')
        ");
        
        $insertStmt->execute([
            $requestId,
            $imageUrl,
            $filename,
            $file[\'name\'],
            $file[\'size\'],
            $file[\'type\']
        ]);
        
        return [
            \'image_id\' => $pdo->lastInsertId(),
            \'image_url\' => $imageUrl,
            \'full_url\' => \'http://localhost/my_little_thingz/backend/\' . $imageUrl,
            \'filename\' => $filename,
            \'original_filename\' => $file[\'name\'],
            \'file_size\' => $file[\'size\']
        ];
    } else {
        return [\'error\' => \'Failed to upload: \' . $file[\'name\']];
    }
}
?>';
    
    file_put_contents(__DIR__ . '/backend/api/customer/custom-request-upload-fixed.php', $fixedUploadApi);
    echo "<p style='color: green;'>✅ Created fixed upload API: backend/api/customer/custom-request-upload-fixed.php</p>";
    
    // Step 4: Replace the original upload API
    echo "<h2>🔄 Step 4: Replacing original upload API</h2>";
    
    // Backup original
    if (file_exists(__DIR__ . '/backend/api/customer/custom-request-upload.php')) {
        copy(__DIR__ . '/backend/api/customer/custom-request-upload.php', __DIR__ . '/backend/api/customer/custom-request-upload-backup.php');
        echo "<p style='color: blue;'>📋 Backed up original API to custom-request-upload-backup.php</p>";
    }
    
    // Replace with fixed version
    file_put_contents(__DIR__ . '/backend/api/customer/custom-request-upload.php', $fixedUploadApi);
    echo "<p style='color: green;'>✅ Replaced original upload API with fixed version</p>";
    
    // Step 5: Test the sync
    echo "<h2>🧪 Step 5: Testing the sync</h2>";
    
    $testRequests = $pdo->query("
        SELECT cr.id, cr.order_id, cr.title, cr.image_url, 
               COUNT(cri.id) as image_count
        FROM custom_requests cr
        LEFT JOIN custom_request_images cri ON cr.id = cri.request_id
        GROUP BY cr.id
        ORDER BY cr.created_at DESC
        LIMIT 5
    ")->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Request ID</th><th>Order ID</th><th>Title</th><th>Image URL</th><th>Image Count</th><th>Status</th>";
    echo "</tr>";
    
    foreach ($testRequests as $req) {
        $status = !empty($req['image_url']) ? '✅ Synced' : ($req['image_count'] > 0 ? '⚠️ Has images but not synced' : '❌ No images');
        $statusColor = !empty($req['image_url']) ? 'green' : ($req['image_count'] > 0 ? 'orange' : 'red');
        
        echo "<tr>";
        echo "<td>{$req['id']}</td>";
        echo "<td>{$req['order_id']}</td>";
        echo "<td>" . substr($req['title'], 0, 30) . "...</td>";
        echo "<td style='max-width: 200px; word-break: break-all;'>" . ($req['image_url'] ?: 'None') . "</td>";
        echo "<td><strong>{$req['image_count']}</strong></td>";
        echo "<td style='color: $statusColor;'><strong>$status</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #28a745;'>";
    echo "<h2 style='color: #155724; margin-top: 0;'>🎉 UPLOAD IMAGE SYNC FIXED!</h2>";
    echo "<p style='color: #155724;'><strong>What was fixed:</strong></p>";
    echo "<ul style='color: #155724;'>";
    echo "<li>✅ Added image_url column to custom_requests table</li>";
    echo "<li>✅ Synced existing images with custom_requests table</li>";
    echo "<li>✅ Fixed upload API to update image_url when images are uploaded</li>";
    echo "<li>✅ Fixed new request creation to set image_url with first uploaded image</li>";
    echo "<li>✅ Backed up original API and replaced with fixed version</li>";
    echo "</ul>";
    echo "<p style='color: #155724;'><strong>Now when customers upload images:</strong></p>";
    echo "<ul style='color: #155724;'>";
    echo "<li>✅ Images will be stored in custom_request_images table</li>";
    echo "<li>✅ First image URL will be copied to custom_requests.image_url column</li>";
    echo "<li>✅ Dashboard will immediately show the uploaded images</li>";
    echo "</ul>";
    echo "<p style='color: #155724;'><strong>Test the fixed system:</strong></p>";
    echo "<p><a href='frontend/admin/custom-requests-fixed.html' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>🎯 OPEN FIXED DASHBOARD</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #dc3545;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Error Occurred</h3>";
    echo "<p style='color: #721c24;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 20px;
    background: #f8f9fa;
}

h1, h2 {
    color: #333;
}

table {
    margin: 15px 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

th, td {
    padding: 8px 12px;
    text-align: left;
    border: 1px solid #ddd;
}

th {
    background: #f8f9fa;
    font-weight: 600;
}

a {
    color: #007bff;
    text-decoration: none;
    font-weight: 600;
}

a:hover {
    text-decoration: underline;
}
</style>