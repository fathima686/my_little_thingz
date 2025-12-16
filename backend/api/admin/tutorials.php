<?php
// Admin Tutorials Management API
// Methods: GET (list), POST (create), PUT (update), DELETE (delete)

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = [
  'http://localhost',
  'http://127.0.0.1',
  'http://localhost:5173',
  'http://127.0.0.1:5173',
  'http://localhost:8080'
];
if ($origin && in_array($origin, $allowed_origins, true)) {
  header("Access-Control-Allow-Origin: $origin");
} else {
  header("Access-Control-Allow-Origin: *");
}
header("Vary: Origin");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// Check admin authentication
$adminUserId = $_SERVER['HTTP_X_ADMIN_USER_ID'] ?? null;
if (!$adminUserId) {
  http_response_code(401);
  echo json_encode(["status" => "error", "message" => "Unauthorized"]);
  exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "DB connect failed: " . $mysqli->connect_error]);
  exit;
}

// Ensure tutorials table exists
$mysqli->query("CREATE TABLE IF NOT EXISTS tutorials (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  thumbnail_url VARCHAR(255),
  video_url VARCHAR(255) NOT NULL,
  duration INT,
  difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
  price DECIMAL(10, 2) DEFAULT 0,
  is_free BOOLEAN DEFAULT 0,
  category VARCHAR(100),
  created_by INT UNSIGNED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  is_active BOOLEAN DEFAULT 1,
  INDEX idx_active (is_active),
  INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Handle file upload
function handleThumbnailUpload($file) {
  if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    return null;
  }
  
  $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
  if (!in_array($file['type'], $allowedTypes)) {
    throw new Exception('Invalid image type. Allowed: JPEG, PNG, GIF, WebP');
  }
  
  if ($file['size'] > 5 * 1024 * 1024) {
    throw new Exception('Image size must be less than 5MB');
  }
  
  $uploadDir = '../../uploads/tutorials/';
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }
  
  $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
  $filename = 'thumb_' . time() . '_' . uniqid() . '.' . $extension;
  $filepath = $uploadDir . $filename;
  
  if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    throw new Exception('Failed to upload file');
  }
  
  return 'uploads/tutorials/' . $filename;
}

// Handle video file upload
function handleVideoUpload($file) {
  if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    return null;
  }
  
  $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo'];
  if (!in_array($file['type'], $allowedTypes)) {
    throw new Exception('Invalid video type. Allowed: MP4, WebM, OGG, MOV, AVI');
  }
  
  // Allow up to 500MB for video files
  if ($file['size'] > 500 * 1024 * 1024) {
    throw new Exception('Video size must be less than 500MB');
  }
  
  $uploadDir = '../../uploads/tutorials/videos/';
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }
  
  $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
  $filename = 'video_' . time() . '_' . uniqid() . '.' . $extension;
  $filepath = $uploadDir . $filename;
  
  if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    throw new Exception('Failed to upload video file');
  }
  
  return 'uploads/tutorials/videos/' . $filename;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Predefined categories
$predefinedCategories = [
  'Hand Embroidery',
  'Resin Art',
  'Gift Making',
  'Mylanchi / Mehandi Art',
  'Candle Making',
  'Jewelry Making',
  'Clay Modeling'
];

// GET - List tutorials or categories
if ($method === 'GET') {
  if ($action === 'categories') {
    // Get unique categories from database and merge with predefined
    $result = $mysqli->query("SELECT DISTINCT category FROM tutorials WHERE category IS NOT NULL AND category != '' AND is_active = 1 ORDER BY category");
    $dbCategories = [];
    while ($row = $result->fetch_assoc()) {
      $dbCategories[] = $row['category'];
    }
    // Merge predefined with database categories, remove duplicates
    $allCategories = array_unique(array_merge($predefinedCategories, $dbCategories));
    sort($allCategories);
    echo json_encode([
      "status" => "success",
      "categories" => array_values($allCategories)
    ]);
    exit;
  }
  
  // Get all tutorials
  $result = $mysqli->query("
    SELECT 
      id, 
      title, 
      description, 
      thumbnail_url, 
      video_url, 
      duration, 
      difficulty_level, 
      price, 
      is_free, 
      category, 
      created_at, 
      updated_at,
      is_active
    FROM tutorials 
    ORDER BY created_at DESC
  ");
  
  $tutorials = [];
  while ($row = $result->fetch_assoc()) {
    $row['price'] = (float)$row['price'];
    $row['duration'] = (int)$row['duration'];
    $row['is_free'] = (bool)$row['is_free'];
    $row['is_active'] = (bool)$row['is_active'];
    $tutorials[] = $row;
  }
  
  echo json_encode([
    "status" => "success",
    "tutorials" => $tutorials
  ]);
  exit;
}

// POST - Create or Update tutorial (when _method=PUT is present, it's an update)
if ($method === 'POST') {
  try {
    $isUpdate = isset($_GET['_method']) && $_GET['_method'] === 'PUT';
    $tutorialId = $_GET['id'] ?? null;
    
    if ($isUpdate && !$tutorialId) {
      throw new Exception('Tutorial ID is required for update');
    }
    
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $video_url = $_POST['video_url'] ?? '';
    $duration = !empty($_POST['duration']) ? (int)$_POST['duration'] : null;
    $difficulty_level = $_POST['difficulty_level'] ?? 'beginner';
    $price = !empty($_POST['price']) ? (float)$_POST['price'] : 0;
    $is_free = isset($_POST['is_free']) && $_POST['is_free'] === '1';
    $category = $_POST['category'] ?? '';
    
    if (empty($title) || empty($category)) {
      throw new Exception('Title and category are required');
    }
    
    // Handle video upload - priority: uploaded file > URL > existing (for updates)
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
      $video_path = handleVideoUpload($_FILES['video']);
      $video_url = $video_path; // Store file path instead of URL
    } elseif (empty($video_url) && $isUpdate) {
      // Keep existing video for updates if no new one provided
      $existing = $mysqli->query("SELECT video_url FROM tutorials WHERE id = $tutorialId");
      if ($row = $existing->fetch_assoc()) {
        $video_url = $row['video_url'];
      }
    } elseif (empty($video_url)) {
      throw new Exception('Either upload a video file or provide a video URL');
    }
    
    // Handle thumbnail upload
    $thumbnail_url = null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
      $thumbnail_url = handleThumbnailUpload($_FILES['thumbnail']);
    } elseif (!empty($_POST['thumbnail_url'])) {
      $thumbnail_url = $_POST['thumbnail_url'];
    } elseif ($isUpdate) {
      // Keep existing thumbnail for updates if no new one provided
      $existing = $mysqli->query("SELECT thumbnail_url FROM tutorials WHERE id = $tutorialId");
      if ($row = $existing->fetch_assoc()) {
        $thumbnail_url = $row['thumbnail_url'];
      }
    }
    
    if ($isUpdate) {
      // Update existing tutorial
      $check = $mysqli->prepare("SELECT id FROM tutorials WHERE id = ?");
      $check->bind_param("i", $tutorialId);
      $check->execute();
      if ($check->get_result()->num_rows === 0) {
        throw new Exception('Tutorial not found');
      }
      
      $stmt = $mysqli->prepare("
        UPDATE tutorials 
        SET title = ?, description = ?, thumbnail_url = ?, video_url = ?, duration = ?, 
            difficulty_level = ?, price = ?, is_free = ?, category = ?
        WHERE id = ?
      ");
      
      $stmt->bind_param(
        "ssssisdsii",
        $title,
        $description,
        $thumbnail_url,
        $video_url,
        $duration,
        $difficulty_level,
        $price,
        $is_free,
        $category,
        $tutorialId
      );
      
      $stmt->execute();
      
      echo json_encode([
        "status" => "success",
        "message" => "Tutorial updated successfully"
      ]);
    } else {
      // Create new tutorial
      $stmt = $mysqli->prepare("
        INSERT INTO tutorials 
        (title, description, thumbnail_url, video_url, duration, difficulty_level, price, is_free, category, created_by, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
      ");
      
      $stmt->bind_param(
        "ssssisdsis",
        $title,
        $description,
        $thumbnail_url,
        $video_url,
        $duration,
        $difficulty_level,
        $price,
        $is_free,
        $category,
        $adminUserId
      );
      
      $stmt->execute();
      $tutorialId = $mysqli->insert_id;
      
      echo json_encode([
        "status" => "success",
        "message" => "Tutorial created successfully",
        "tutorial_id" => $tutorialId
      ]);
    }
  } catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
      "status" => "error",
      "message" => $e->getMessage()
    ]);
  }
  exit;
}

// PUT - Update tutorial
if ($method === 'PUT') {
  try {
    $tutorialId = $_GET['id'] ?? null;
    if (!$tutorialId) {
      throw new Exception('Tutorial ID is required');
    }
    
    // Handle both multipart/form-data and JSON
    if (!empty($_POST)) {
      $data = $_POST;
    } else {
      parse_str(file_get_contents('php://input'), $data);
    }
    
    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $video_url = $data['video_url'] ?? '';
    $duration = !empty($data['duration']) ? (int)$data['duration'] : null;
    $difficulty_level = $data['difficulty_level'] ?? 'beginner';
    $price = !empty($data['price']) ? (float)$data['price'] : 0;
    $is_free = isset($data['is_free']) && ($data['is_free'] === '1' || $data['is_free'] === true);
    $category = $data['category'] ?? '';
    
    if (empty($title) || empty($video_url) || empty($category)) {
      throw new Exception('Title, video URL, and category are required');
    }
    
    // Check if tutorial exists
    $check = $mysqli->prepare("SELECT id, thumbnail_url FROM tutorials WHERE id = ?");
    $check->bind_param("i", $tutorialId);
    $check->execute();
    $existingResult = $check->get_result();
    if ($existingResult->num_rows === 0) {
      throw new Exception('Tutorial not found');
    }
    $existing = $existingResult->fetch_assoc();
    
    // Handle thumbnail - if new file uploaded, use it; otherwise keep existing or use URL
    $thumbnail_url = $existing['thumbnail_url'];
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
      $thumbnail_url = handleThumbnailUpload($_FILES['thumbnail']);
    } elseif (!empty($data['thumbnail_url'])) {
      $thumbnail_url = $data['thumbnail_url'];
    }
    
    $stmt = $mysqli->prepare("
      UPDATE tutorials 
      SET title = ?, description = ?, thumbnail_url = ?, video_url = ?, duration = ?, 
          difficulty_level = ?, price = ?, is_free = ?, category = ?
      WHERE id = ?
    ");
    
    $stmt->bind_param(
      "ssssisdsii",
      $title,
      $description,
      $thumbnail_url,
      $video_url,
      $duration,
      $difficulty_level,
      $price,
      $is_free,
      $category,
      $tutorialId
    );
    
    $stmt->execute();
    
    echo json_encode([
      "status" => "success",
      "message" => "Tutorial updated successfully"
    ]);
  } catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
      "status" => "error",
      "message" => $e->getMessage()
    ]);
  }
  exit;
}

// DELETE - Delete tutorial
if ($method === 'DELETE') {
  try {
    $tutorialId = $_GET['id'] ?? null;
    if (!$tutorialId) {
      throw new Exception('Tutorial ID is required');
    }
    
    $stmt = $mysqli->prepare("DELETE FROM tutorials WHERE id = ?");
    $stmt->bind_param("i", $tutorialId);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
      throw new Exception('Tutorial not found');
    }
    
    echo json_encode([
      "status" => "success",
      "message" => "Tutorial deleted successfully"
    ]);
  } catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
      "status" => "error",
      "message" => $e->getMessage()
    ]);
  }
  exit;
}

http_response_code(405);
echo json_encode(["status" => "error", "message" => "Method not allowed"]);

