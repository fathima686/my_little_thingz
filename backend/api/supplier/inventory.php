<?php
// Supplier Inventory API (per-supplier CRUD + filters)
// - Adds pagination, sorting, stricter validation, safer CORS, and clearer responses

// --- CORS ---
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost:5173', 'http://127.0.0.1:5173'];
if (in_array($origin, $allowed, true)) {
  header("Access-Control-Allow-Origin: $origin");
} else {
  header("Access-Control-Allow-Origin: http://localhost:5173");
}
header('Vary: Origin');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-SUPPLIER-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli('localhost', 'root', '', 'my_little_thingz');
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'DB connect failed: ' . $mysqli->connect_error]);
  exit;
}

// Ensure base table exists (and migrate columns if missing)
$mysqli->query("CREATE TABLE IF NOT EXISTS materials (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  sku VARCHAR(80) NULL,
  quantity INT NOT NULL DEFAULT 0,
  unit VARCHAR(32) NOT NULL DEFAULT 'pcs',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(supplier_id),
  CONSTRAINT fk_materials_supplier FOREIGN KEY (supplier_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB");

try {
  $colsRes = $mysqli->query('SHOW COLUMNS FROM materials');
  $have = [];
  while ($row = $colsRes->fetch_assoc()) { $have[strtolower($row['Field'])] = true; }
  $alters = [];
  if (empty($have['category'])) { $alters[] = "ADD COLUMN category VARCHAR(60) NOT NULL DEFAULT '' AFTER name"; }
  if (empty($have['type']))     { $alters[] = "ADD COLUMN type VARCHAR(60) NOT NULL DEFAULT '' AFTER category"; }
  if (empty($have['size']))     { $alters[] = "ADD COLUMN size VARCHAR(60) NOT NULL DEFAULT '' AFTER type"; }
  if (empty($have['color']))    { $alters[] = "ADD COLUMN color VARCHAR(60) NULL AFTER size"; }
  if (empty($have['grade']))    { $alters[] = "ADD COLUMN grade VARCHAR(60) NULL AFTER color"; }
  if (empty($have['brand']))    { $alters[] = "ADD COLUMN brand VARCHAR(60) NULL AFTER grade"; }
  if (empty($have['tags']))     { $alters[] = "ADD COLUMN tags VARCHAR(255) NULL AFTER brand"; }
  if (empty($have['location'])) { $alters[] = "ADD COLUMN location VARCHAR(120) NULL AFTER tags"; }
  if (empty($have['availability'])) { $alters[] = "ADD COLUMN availability ENUM('available','out_of_stock') NOT NULL DEFAULT 'available' AFTER location"; }
  if (empty($have['image_url'])) { $alters[] = "ADD COLUMN image_url VARCHAR(500) NULL AFTER availability"; }
  if (empty($have['attributes_json'])) { $alters[] = "ADD COLUMN attributes_json TEXT NULL AFTER image_url"; }
  if (!empty($alters)) { $mysqli->query('ALTER TABLE materials ' . implode(', ', $alters)); }
} catch (Throwable $e) { /* ignore migration errors */ }

$method = $_SERVER['REQUEST_METHOD'];

// --- Helpers ---
function read_body_json(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}
function s($v): string { return trim((string)$v); }
function clamp_int($val, $min, $max): int { $val = (int)$val; if ($val < $min) return $min; if ($val > $max) return $max; return $val; }
function allow_enum(string $v, array $allowed, string $fallback): string { $v = strtolower(s($v)); return in_array($v, $allowed, true) ? $v : $fallback; }
function limit_len(?string $v, int $max): string { $v = s($v ?? ''); return ($max > 0 && strlen($v) > $max) ? substr($v, 0, $max) : $v; }

// --- Auth (temporary) ---
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : (int)($_SERVER['HTTP_X_SUPPLIER_ID'] ?? 0);
if ($supplier_id <= 0) {
  http_response_code(401);
  echo json_encode(['status' => 'error', 'message' => 'Missing supplier_id']);
  exit;
}

// Enforce supplier approval for write operations
$isApproved = true; // default to true to not block GET; verify below
try {
  $chk = $mysqli->prepare('SELECT sp.status FROM supplier_profiles sp WHERE sp.user_id=? LIMIT 1');
  $chk->bind_param('i', $supplier_id);
  $chk->execute();
  $res = $chk->get_result();
  if ($row = $res->fetch_assoc()) { $isApproved = ($row['status'] === 'approved'); }
  $chk->close();
} catch (Throwable $e) { /* ignore; treat as approved for GET */ }

try {
  if ($method === 'GET') {
    // Filters
    $q = isset($_GET['q']) ? s($_GET['q']) : '';
    $category = isset($_GET['category']) ? s($_GET['category']) : '';
    $type = isset($_GET['type']) ? s($_GET['type']) : '';
    $size = isset($_GET['size']) ? s($_GET['size']) : '';
    $color = isset($_GET['color']) ? s($_GET['color']) : '';
    $min_qty = isset($_GET['min_qty']) ? (int)$_GET['min_qty'] : null;

    // Pagination & sorting
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? clamp_int($_GET['limit'], 1, 100) : 20;
    $offset = ($page - 1) * $limit;
    $sortMap = [
      'name' => 'name',
      'updated_at' => 'updated_at',
      'created_at' => 'created_at',
      'quantity' => 'quantity'
    ];
    $sort_by = $_GET['sort_by'] ?? 'name';
    $sort_col = $sortMap[$sort_by] ?? 'name';
    $sort_dir = strtolower($_GET['sort_dir'] ?? 'asc');
    $sort_dir = in_array($sort_dir, ['asc','desc'], true) ? $sort_dir : 'asc';

    // Build base WHERE
    $where = ' WHERE supplier_id=?';
    $types = 'i';
    $params = [$supplier_id];
    if ($q !== '') {
      $where .= ' AND (name LIKE ? OR sku LIKE ? OR category LIKE ? OR type LIKE ? OR size LIKE ? OR color LIKE ? OR grade LIKE ? OR brand LIKE ? OR tags LIKE ?)';
      $like = "%{$q}%";
      $types .= 'sssssssss';
      array_push($params, $like, $like, $like, $like, $like, $like, $like, $like, $like);
    }
    if ($category !== '') { $where .= ' AND category = ?'; $types .= 's'; $params[] = $category; }
    if ($type !== '')     { $where .= ' AND type = ?';     $types .= 's'; $params[] = $type; }
    if ($size !== '')     { $where .= ' AND size = ?';     $types .= 's'; $params[] = $size; }
    if ($color !== '')    { $where .= ' AND color = ?';    $types .= 's'; $params[] = $color; }
    if ($min_qty !== null){ $where .= ' AND quantity >= ?'; $types .= 'i'; $params[] = $min_qty; }

    // Count total
    $sqlCount = 'SELECT COUNT(*) AS cnt FROM materials' . $where;
    $stCount = $mysqli->prepare($sqlCount);
    $stCount->bind_param($types, ...$params);
    $stCount->execute();
    $rc = $stCount->get_result()->fetch_assoc();
    $total = (int)($rc['cnt'] ?? 0);
    $stCount->close();

    // Fetch page
    $sql = 'SELECT id, name, sku, quantity, unit, category, type, size, color, grade, brand, tags, location, availability, image_url, attributes_json, updated_at, created_at'
         . ' FROM materials' . $where . " ORDER BY $sort_col $sort_dir LIMIT ? OFFSET ?";
    $typesPage = $types . 'ii';
    $paramsPage = array_merge($params, [$limit, $offset]);

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($typesPage, ...$paramsPage);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);

    $total_pages = (int)ceil($total / max(1, $limit));
    echo json_encode([
      'status' => 'success',
      'items' => $rows,
      'page' => $page,
      'limit' => $limit,
      'total' => $total,
      'total_pages' => $total_pages,
      'sort_by' => $sort_col,
      'sort_dir' => $sort_dir
    ]);

  } elseif ($method === 'POST') {
    // Block write if not approved
    if (!$isApproved) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Supplier not approved']); exit; }

    $b = read_body_json();

    // Validate & sanitize
    $name = limit_len($b['name'] ?? '', 120);
    $sku  = limit_len($b['sku'] ?? '', 80);
    $qty  = max(0, (int)($b['quantity'] ?? 0));
    $unit = limit_len($b['unit'] ?? 'pcs', 32);
    $category = limit_len($b['category'] ?? '', 60);
    $type = limit_len($b['type'] ?? '', 60);
    $size = limit_len($b['size'] ?? '', 60);
    $color = limit_len($b['color'] ?? '', 60);
    $grade = limit_len($b['grade'] ?? '', 60);
    $brand = limit_len($b['brand'] ?? '', 60);
    $tags = limit_len($b['tags'] ?? '', 255);
    $location = limit_len($b['location'] ?? '', 120);
    $availability = allow_enum($b['availability'] ?? 'available', ['available','out_of_stock'], 'available');
    $image_url = limit_len($b['image_url'] ?? '', 500);

    $attributes = $b['attributes'] ?? null; // array or json string
    if (is_array($attributes)) { $attributes_json = json_encode($attributes, JSON_UNESCAPED_UNICODE); }
    elseif (is_string($attributes)) { $attributes_json = $attributes; }
    else { $attributes_json = null; }

    if ($name === '') { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Name required']); exit; }

    $stmt = $mysqli->prepare("INSERT INTO materials
      (supplier_id, name, sku, quantity, unit, category, type, size, color, grade, brand, tags, location, availability, image_url, attributes_json)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param('ississssssssssss', $supplier_id, $name, $sku, $qty, $unit, $category, $type, $size, $color, $grade, $brand, $tags, $location, $availability, $image_url, $attributes_json);
    $stmt->execute();
    $newId = $stmt->insert_id;

    // Return the newly created row
    $sel = $mysqli->prepare('SELECT id, name, sku, quantity, unit, category, type, size, color, grade, brand, tags, location, availability, image_url, attributes_json, updated_at, created_at FROM materials WHERE id=? AND supplier_id=?');
    $sel->bind_param('ii', $newId, $supplier_id);
    $sel->execute();
    $row = $sel->get_result()->fetch_assoc();

    echo json_encode(['status' => 'success', 'id' => $newId, 'item' => $row]);

  } elseif ($method === 'PUT') {
    // Block write if not approved
    if (!$isApproved) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Supplier not approved']); exit; }

    $b = read_body_json();
    $id  = (int)($b['id'] ?? 0);
    if ($id <= 0) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Invalid id']); exit; }

    // Allowed fields with validators
    $fields = [
      'name' => function($v){ return limit_len($v, 120); },
      'sku' => function($v){ return limit_len($v, 80); },
      'quantity' => function($v){ $n = (int)$v; return $n < 0 ? 0 : $n; },
      'unit' => function($v){ return limit_len($v, 32); },
      'category' => function($v){ return limit_len($v, 60); },
      'type' => function($v){ return limit_len($v, 60); },
      'size' => function($v){ return limit_len($v, 60); },
      'color' => function($v){ return limit_len($v, 60); },
      'grade' => function($v){ return limit_len($v, 60); },
      'brand' => function($v){ return limit_len($v, 60); },
      'tags' => function($v){ return limit_len($v, 255); },
      'location' => function($v){ return limit_len($v, 120); },
      'availability' => function($v){ return allow_enum($v, ['available','out_of_stock'], 'available'); },
      'image_url' => function($v){ return limit_len($v, 500); },
      'attributes_json' => function($v){ return (string)$v; },
    ];

    // Normalize attributes input
    if (array_key_exists('attributes', $b)) {
      if (is_array($b['attributes'])) { $b['attributes_json'] = json_encode($b['attributes'], JSON_UNESCAPED_UNICODE); }
      elseif (is_string($b['attributes'])) { $b['attributes_json'] = $b['attributes']; }
    }

    $setParts = [];
    $types = '';
    $params = [];

    foreach ($fields as $key => $validator) {
      if (array_key_exists($key, $b)) {
        $setParts[] = "$key = ?";
        $val = $validator($b[$key]);
        // Bind types
        if ($key === 'quantity') { $types .= 'i'; $params[] = (int)$val; }
        else { $types .= 's'; $params[] = s((string)$val); }
      }
    }

    if (empty($setParts)) { echo json_encode(['status'=>'success','message'=>'No changes']); exit; }

    $sql = 'UPDATE materials SET ' . implode(', ', $setParts) . ' WHERE id=? AND supplier_id=?';
    $types .= 'ii';
    $params[] = $id;
    $params[] = $supplier_id;

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    // Return the updated row
    $sel = $mysqli->prepare('SELECT id, name, sku, quantity, unit, category, type, size, color, grade, brand, tags, location, availability, attributes_json, updated_at, created_at FROM materials WHERE id=? AND supplier_id=?');
    $sel->bind_param('ii', $id, $supplier_id);
    $sel->execute();
    $row = $sel->get_result()->fetch_assoc();

    echo json_encode(['status' => 'success', 'item' => $row]);

  } elseif ($method === 'DELETE') {
    // Block write if not approved
    if (!$isApproved) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Supplier not approved']); exit; }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Invalid id']); exit; }

    $stmt = $mysqli->prepare('DELETE FROM materials WHERE id=? AND supplier_id=?');
    $stmt->bind_param('ii', $id, $supplier_id);
    $stmt->execute();

    if ($stmt->affected_rows <= 0) {
      http_response_code(404);
      echo json_encode(['status' => 'error', 'message' => 'Not found']);
      exit;
    }

    echo json_encode(['status' => 'success']);

  } else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}