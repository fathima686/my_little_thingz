<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user ID from headers
    $user_id = null;
    if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') {
        $user_id = $_SERVER['HTTP_X_USER_ID'];
    }
    if (!$user_id && function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) {
            if (strtolower($k) === 'x-user-id' && $v !== '') {
                $user_id = $v;
                break;
            }
        }
    }
    if (!$user_id && isset($_GET['user_id']) && $_GET['user_id'] !== '') {
        $user_id = $_GET['user_id'];
    }

    if (!$user_id) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User ID required'
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // If multipart/form-data with images is sent, treat this as creating a minimal cart customization request
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? strtolower($_SERVER['CONTENT_TYPE']) : '';
        $isMultipart = strpos($contentType, 'multipart/form-data') !== false || (!empty($_FILES['reference_images']));

        if ($isMultipart) {
            // Validate required fields for cart customization
            $description = $_POST['description'] ?? '';
            $occasion = $_POST['occasion'] ?? '';
            $deadline = $_POST['date'] ?? ($_POST['deadline'] ?? '');
            $artwork_id = isset($_POST['artwork_id']) ? (int)$_POST['artwork_id'] : null;
            $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

            if (trim($description) === '') { echo json_encode(['status'=>'error','message'=>'Description is required']); exit; }
            if (trim($occasion) === '') { echo json_encode(['status'=>'error','message'=>'Occasion is required']); exit; }
            if (trim($deadline) === '') { echo json_encode(['status'=>'error','message'=>'Date is required']); exit; }
            
            // Set title before building SQL
            $title = $_POST['title'] ?? '';
            if (trim($title) === '') {
                $title = 'Cart customization - ' . $occasion . ' - ' . $deadline;
            }

            $hasImage = !empty($_FILES['reference_images']) && (
              (is_array($_FILES['reference_images']['error']) && count(array_filter($_FILES['reference_images']['error'], function ($e) { return (int)$e === UPLOAD_ERR_OK; })) > 0)
              || (!is_array($_FILES['reference_images']['error']) && (int)$_FILES['reference_images']['error'] === UPLOAD_ERR_OK)
            );
            if (!$hasImage) { echo json_encode(['status'=>'error','message'=>'At least one reference image is required']); exit; }

            // Dynamically build INSERT statement based on existing columns
            function hasColumnPDO($db, $table, $column) {
                try {
                    $rs = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
                    return $rs && $rs->rowCount() > 0;
                } catch (Throwable $e) {
                    return false;
                }
            }
            
            // Check which user ID column exists (user_id or customer_id) - prefer customer_id
            $hasUserId = hasColumnPDO($db, 'custom_requests', 'user_id');
            $hasCustomerId = hasColumnPDO($db, 'custom_requests', 'customer_id');
            
            // Prioritize customer_id if it exists, otherwise use user_id
            if ($hasCustomerId) {
                $userIdColumn = 'customer_id';
            } elseif ($hasUserId) {
                $userIdColumn = 'user_id';
            } else {
                // Neither exists - default to customer_id and try to add it
                $userIdColumn = 'customer_id';
                try {
                    $db->exec("ALTER TABLE custom_requests ADD COLUMN customer_id INT NULL DEFAULT 0 AFTER id");
                } catch (Throwable $e) {
                    // Ignore if it fails
                }
            }
            
            // Check all optional columns
            $colChecks = [
                'category_id' => hasColumnPDO($db, 'custom_requests', 'category_id'),
                'occasion' => hasColumnPDO($db, 'custom_requests', 'occasion'),
                'budget_min' => hasColumnPDO($db, 'custom_requests', 'budget_min'),
                'budget_max' => hasColumnPDO($db, 'custom_requests', 'budget_max'),
                'deadline' => hasColumnPDO($db, 'custom_requests', 'deadline'),
                'special_instructions' => hasColumnPDO($db, 'custom_requests', 'special_instructions'),
                'source' => hasColumnPDO($db, 'custom_requests', 'source'),
                'status' => hasColumnPDO($db, 'custom_requests', 'status'),
                'created_at' => hasColumnPDO($db, 'custom_requests', 'created_at')
            ];
            
            // Build columns and values arrays dynamically
            $columns = [$userIdColumn, 'title', 'description']; // Required columns
            $placeholders = ['?', '?', '?']; // For userId, title, description
            $executeValues = [$user_id, $title, $description];
            
            // Add optional columns if they exist
            if ($colChecks['category_id']) {
                $columns[] = 'category_id';
                $placeholders[] = 'NULL';
            }
            
            if ($colChecks['occasion']) {
                $columns[] = 'occasion';
                $placeholders[] = '?';
                $executeValues[] = $occasion;
            }
            
            if ($colChecks['budget_min']) {
                $columns[] = 'budget_min';
                $placeholders[] = 'NULL';
            }
            
            if ($colChecks['budget_max']) {
                $columns[] = 'budget_max';
                $placeholders[] = 'NULL';
            }
            
            if ($colChecks['deadline']) {
                $columns[] = 'deadline';
                $placeholders[] = '?';
                $executeValues[] = $deadline;
            }
            
            if ($colChecks['special_instructions']) {
                $columns[] = 'special_instructions';
                $placeholders[] = "''";
            }
            
            if ($colChecks['source']) {
                $columns[] = 'source';
                $placeholders[] = "'cart'";
            }
            
            if ($colChecks['status']) {
                $columns[] = 'status';
                $placeholders[] = "'pending'";
            }
            
            if ($colChecks['created_at']) {
                $columns[] = 'created_at';
                $placeholders[] = 'NOW()';
            }
            
            // Build and execute SQL
            $columnList = implode(', ', $columns);
            $placeholderList = implode(', ', $placeholders);
            $sql = "INSERT INTO custom_requests ($columnList) VALUES ($placeholderList)";
            
            $st = $db->prepare($sql);
            if (!$st) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement']);
                exit;
            }
            
            $st->execute($executeValues);
            $requestId = (int)$db->lastInsertId();

            // Save images
            $uploadDir = __DIR__ . '/../../uploads/custom-requests/';
            if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }
            
            // Check which image columns exist
            $hasImagePath = hasColumnPDO($db, 'custom_request_images', 'image_path');
            $hasImageUrl = hasColumnPDO($db, 'custom_request_images', 'image_url');
            $hasFilename = hasColumnPDO($db, 'custom_request_images', 'filename');
            
            $files = $_FILES['reference_images'];
            $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
            $names    = is_array($files['name']) ? $files['name'] : [$files['name']];
            $errors   = is_array($files['error']) ? $files['error'] : [$files['error']];
            for ($i = 0; $i < count($tmpNames); $i++) {
                if ($errors[$i] === UPLOAD_ERR_OK) {
                    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $names[$i]);
                    $fileName = uniqid('cart_', true) . '_' . $safeName;
                    $filePath = $uploadDir . $fileName;
                    if (move_uploaded_file($tmpNames[$i], $filePath)) {
                        $relPath = 'uploads/custom-requests/' . $fileName;
                        
                        // Build INSERT dynamically based on available columns
                        if ($hasImageUrl) {
                            // Use image_url column
                            if ($hasFilename) {
                                $sti = $db->prepare("INSERT INTO custom_request_images (request_id, image_url, filename, uploaded_at) VALUES (?, ?, ?, NOW())");
                                $sti->execute([$requestId, $relPath, $names[$i]]);
                            } else {
                                $sti = $db->prepare("INSERT INTO custom_request_images (request_id, image_url, uploaded_at) VALUES (?, ?, NOW())");
                                $sti->execute([$requestId, $relPath]);
                            }
                        } elseif ($hasImagePath) {
                            // Use image_path column
                            if ($hasFilename) {
                                $sti = $db->prepare("INSERT INTO custom_request_images (request_id, image_path, filename, uploaded_at) VALUES (?, ?, ?, NOW())");
                                $sti->execute([$requestId, $relPath, $names[$i]]);
                            } else {
                                $sti = $db->prepare("INSERT INTO custom_request_images (request_id, image_path, uploaded_at) VALUES (?, ?, NOW())");
                                $sti->execute([$requestId, $relPath]);
                            }
                        } else {
                            // Fallback: try image_url
                            if ($hasFilename) {
                                $sti = $db->prepare("INSERT INTO custom_request_images (request_id, image_url, filename, uploaded_at) VALUES (?, ?, ?, NOW())");
                                $sti->execute([$requestId, $relPath, $names[$i]]);
                            } else {
                                $sti = $db->prepare("INSERT INTO custom_request_images (request_id, image_url, uploaded_at) VALUES (?, ?, NOW())");
                                $sti->execute([$requestId, $relPath]);
                            }
                        }
                    }
                }
            }

            // Do NOT add to cart immediately. Store intended artwork and quantity on the request
            $addedToCart = false;
            if ($artwork_id) {
                // Ensure columns exist (best effort)
                try { $rsA = $db->query("SHOW COLUMNS FROM custom_requests LIKE 'artwork_id'"); $hasArtworkCol = $rsA && $rsA->rowCount() > 0; } catch (Throwable $e) { $hasArtworkCol = false; }
                try { $rsQ = $db->query("SHOW COLUMNS FROM custom_requests LIKE 'requested_quantity'"); $hasQtyCol = $rsQ && $rsQ->rowCount() > 0; } catch (Throwable $e) { $hasQtyCol = false; }
                try {
                    if (!$hasArtworkCol) { $db->exec("ALTER TABLE custom_requests ADD COLUMN artwork_id INT NULL AFTER source"); }
                } catch (Throwable $e) {}
                try {
                    if (!$hasQtyCol) { $db->exec("ALTER TABLE custom_requests ADD COLUMN requested_quantity INT NOT NULL DEFAULT 1 AFTER artwork_id"); }
                } catch (Throwable $e) {}
                // Persist mapping for later cart insertion upon admin approval
                try {
                    $updMap = $db->prepare("UPDATE custom_requests SET artwork_id = ?, requested_quantity = ? WHERE id = ?");
                    $updMap->execute([$artwork_id, max(1, (int)$quantity), $requestId]);
                } catch (Throwable $e) {}
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Customization request created',
                'request_id' => $requestId,
                'added_to_cart' => false,
                'will_move_to_cart_on_approval' => true
            ]);
            exit;
        }

        // JSON flow (original): add to cart and optionally link an approved customization request
        $input = json_decode(file_get_contents('php://input'), true);
        $artwork_id = $input['artwork_id'] ?? null;
        $quantity = $input['quantity'] ?? 1;
        $customization_request_id = $input['customization_request_id'] ?? null;

        if (!$artwork_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Artwork ID required'
            ]);
            exit;
        }

        // Check if artwork exists and is available
        $artwork_query = "SELECT id, availability FROM artworks WHERE id = ? AND status = 'active'";
        $artwork_stmt = $db->prepare($artwork_query);
        $artwork_stmt->execute([$artwork_id]);
        $artwork = $artwork_stmt->fetch();

        if (!$artwork) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Artwork not found'
            ]);
            exit;
        }

        // If customization request ID is provided, check if it's approved
        if ($customization_request_id) {
            $customization_query = "SELECT id, status FROM custom_requests WHERE id = ? AND user_id = ?";
            $customization_stmt = $db->prepare($customization_query);
            $customization_stmt->execute([$customization_request_id, $user_id]);
            $customization = $customization_stmt->fetch();

            if (!$customization) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Customization request not found'
                ]);
                exit;
            }

            if ($customization['status'] !== 'completed') {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Customization request must be approved by admin before adding to cart'
                ]);
                exit;
            }
        }

        // Check if item already exists in cart
        $check_query = "SELECT id, quantity FROM cart WHERE user_id = ? AND artwork_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$user_id, $artwork_id]);
        $existing_item = $check_stmt->fetch();

        if ($existing_item) {
            // Update quantity
            $new_quantity = $existing_item['quantity'] + $quantity;
            $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $result = $update_stmt->execute([$new_quantity, $existing_item['id']]);
        } else {
            // Add new item
            $insert_query = "INSERT INTO cart (user_id, artwork_id, quantity, added_at) VALUES (?, ?, ?, NOW())";
            $insert_stmt = $db->prepare($insert_query);
            $result = $insert_stmt->execute([$user_id, $artwork_id, $quantity]);
        }

        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Item added to cart successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to add item to cart'
            ]);
        }

    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Method not allowed'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
