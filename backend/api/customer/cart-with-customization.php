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

            $hasImage = !empty($_FILES['reference_images']) && (
              (is_array($_FILES['reference_images']['error']) && count(array_filter($_FILES['reference_images']['error'], function ($e) { return (int)$e === UPLOAD_ERR_OK; })) > 0)
              || (!is_array($_FILES['reference_images']['error']) && (int)$_FILES['reference_images']['error'] === UPLOAD_ERR_OK)
            );
            if (!$hasImage) { echo json_encode(['status'=>'error','message'=>'At least one reference image is required']); exit; }

            // Ensure columns exist (best effort)
            $hasOccasion = false; $hasSource = false;
            try { $rs = $db->query("SHOW COLUMNS FROM custom_requests LIKE 'occasion'"); $hasOccasion = $rs && $rs->rowCount() > 0; } catch (Throwable $e) {}
            try { $rs2 = $db->query("SHOW COLUMNS FROM custom_requests LIKE 'source'"); $hasSource = $rs2 && $rs2->rowCount() > 0; } catch (Throwable $e) {}

            // Insert request (minimal fields)
            if ($hasOccasion && $hasSource) {
                $st = $db->prepare("INSERT INTO custom_requests (user_id, title, description, category_id, occasion, budget_min, budget_max, deadline, special_instructions, source, status, created_at) VALUES (?, ?, ?, NULL, ?, NULL, NULL, ?, '', 'cart', 'pending', NOW())");
                $title = 'Cart customization - ' . $occasion . ' - ' . $deadline;
                $st->execute([$user_id, $title, $description, $occasion, $deadline]);
            } elseif ($hasOccasion && !$hasSource) {
                $st = $db->prepare("INSERT INTO custom_requests (user_id, title, description, category_id, occasion, budget_min, budget_max, deadline, special_instructions, status, created_at) VALUES (?, ?, ?, NULL, ?, NULL, NULL, ?, '', 'pending', NOW())");
                $title = 'Cart customization - ' . $occasion . ' - ' . $deadline;
                $st->execute([$user_id, $title, $description, $occasion, $deadline]);
            } elseif (!$hasOccasion && $hasSource) {
                $st = $db->prepare("INSERT INTO custom_requests (user_id, title, description, category_id, budget_min, budget_max, deadline, special_instructions, source, status, created_at) VALUES (?, ?, ?, NULL, NULL, NULL, ?, '', 'cart', 'pending', NOW())");
                $title = 'Cart customization - ' . $deadline;
                $st->execute([$user_id, $title, $description, $deadline]);
            } else {
                $st = $db->prepare("INSERT INTO custom_requests (user_id, title, description, category_id, budget_min, budget_max, deadline, special_instructions, status, created_at) VALUES (?, ?, ?, NULL, NULL, NULL, ?, '', 'pending', NOW())");
                $title = 'Cart customization - ' . $deadline;
                $st->execute([$user_id, $title, $description, $deadline]);
            }
            $requestId = (int)$db->lastInsertId();

            // Save images
            $uploadDir = __DIR__ . '/../../uploads/custom-requests/';
            if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }
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
                        $sti = $db->prepare("INSERT INTO custom_request_images (request_id, image_path, uploaded_at) VALUES (?, ?, NOW())");
                        $sti->execute([$requestId, $relPath]);
                    }
                }
            }

            // Optionally add item to cart immediately so it appears in cart
            $addedToCart = false;
            if ($artwork_id) {
                // Validate artwork
                $artwork_stmt = $db->prepare("SELECT id FROM artworks WHERE id=? AND status='active'");
                $artwork_stmt->execute([$artwork_id]);
                if ($artwork_stmt->fetch()) {
                    // Insert or update cart
                    $check_stmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id=? AND artwork_id=?");
                    $check_stmt->execute([$user_id, $artwork_id]);
                    $existing = $check_stmt->fetch();
                    if ($existing) {
                        $newq = ((int)$existing['quantity']) + $quantity;
                        $upd = $db->prepare("UPDATE cart SET quantity=? WHERE id=?");
                        $addedToCart = $upd->execute([$newq, $existing['id']]);
                    } else {
                        $ins = $db->prepare("INSERT INTO cart (user_id, artwork_id, quantity, added_at) VALUES (?, ?, ?, NOW())");
                        $addedToCart = $ins->execute([$user_id, $artwork_id, $quantity]);
                    }
                }
            }

            echo json_encode(['status'=>'success','message'=>'Customization request created','request_id'=>$requestId,'added_to_cart'=>$addedToCart]);
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
