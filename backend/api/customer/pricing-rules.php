<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once '../../config/database.php';

try {
	$database = new Database();
	$db = $database->getConnection();

	$artworkId = isset($_GET['artwork_id']) ? (int)$_GET['artwork_id'] : 0;
	if ($artworkId <= 0) {
		http_response_code(400);
		echo json_encode(['status'=>'error','message'=>'artwork_id is required']);
		exit;
	}

	// Detect pricing_schema presence to avoid SQL errors on older DBs
	$hasPricing = false;
	try {
		$chk = $db->query("SHOW COLUMNS FROM artworks LIKE 'pricing_schema'");
		$hasPricing = $chk && $chk->rowCount() > 0;
	} catch (Throwable $e) { $hasPricing = false; }

	$selectCols = "id, title, price";
	if ($hasPricing) { $selectCols .= ", pricing_schema"; }
	$stmt = $db->prepare("SELECT $selectCols FROM artworks WHERE id = ? LIMIT 1");
	$stmt->execute([$artworkId]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$row) { http_response_code(404); echo json_encode(['status'=>'error','message'=>'Artwork not found']); exit; }

	$basePrice = (float)$row['price'];
	$schemaRaw = $hasPricing ? ($row['pricing_schema'] ?? null) : null;
	$schema = null;
	if ($schemaRaw) {
		// MySQL JSON returns string in PDO; decode
		$schema = json_decode($schemaRaw, true);
		if (!is_array($schema)) { $schema = null; }
	}

	if (!$schema) {
		$schema = [
			'options' => []
		];
	}

	echo json_encode([
		'status' => 'success',
		'artwork_id' => (int)$row['id'],
		'title' => $row['title'],
		'base_price' => $basePrice,
		'options' => $schema['options'] ?? []
	]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}


