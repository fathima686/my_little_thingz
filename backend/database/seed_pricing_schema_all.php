<?php
// Set per-artwork pricing_schema based on inferred category/title keywords.
// Run: php backend/database/seed_pricing_schema_all.php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli('localhost', 'root', '', 'my_little_thingz');
$mysqli->set_charset('utf8mb4');

header('Content-Type: application/json');

function hasColumn(mysqli $db, string $table, string $column): bool {
	try {
		$res = $db->query("SHOW COLUMNS FROM `" . $db->real_escape_string($table) . "` LIKE '" . $db->real_escape_string($column) . "'");
		return $res && $res->num_rows > 0;
	} catch (Throwable $e) { return false; }
}

try {
	if (!hasColumn($mysqli, 'artworks', 'pricing_schema')) {
		echo json_encode(['status'=>'error','message'=>'artworks.pricing_schema column missing. Run migrate_add_pricing_schema.php first.']);
		exit;
	}

	$rows = $mysqli->query("SELECT id, title, category_id FROM artworks");
	$updated = 0;
	while ($row = $rows->fetch_assoc()) {
		$id = (int)$row['id'];
		$title = strtolower((string)$row['title']);

		$schema = null;
		if (str_contains($title, 'choco') || str_contains($title, 'chocolate')) {
			// Chocolates: flavor, boxSize, messageLength
			$schema = [ 'options' => [
				'flavor' => [ 'type' => 'select', 'values' => [
					[ 'value' => 'milk', 'delta' => ['type' => 'flat', 'value' => 0] ],
					[ 'value' => 'dark', 'delta' => ['type' => 'flat', 'value' => 20] ],
					[ 'value' => 'white', 'delta' => ['type' => 'flat', 'value' => 10] ],
				]],
				'boxSize' => [ 'type' => 'select', 'values' => [
					[ 'value' => '6pc', 'delta' => ['type' => 'flat', 'value' => 0] ],
					[ 'value' => '12pc', 'delta' => ['type' => 'flat', 'value' => 150] ],
					[ 'value' => '24pc', 'delta' => ['type' => 'flat', 'value' => 350] ],
				]],
				'messageLength' => [ 'type' => 'range', 'unit' => 'chars', 'tiers' => [
					[ 'max' => 30, 'delta' => ['type' => 'flat', 'value' => 0] ],
					[ 'max' => 80, 'delta' => ['type' => 'flat', 'value' => 99] ],
					[ 'max' => 160, 'delta' => ['type' => 'flat', 'value' => 199] ],
				]]
			]];
		} elseif (str_contains($title, 'frame') || str_contains($title, 'print') || str_contains($title, 'portrait')) {
			// Prints/frames: size, frame, material
			$schema = [ 'options' => [
				'size' => [ 'type' => 'select', 'values' => [
					[ 'value' => 'A5', 'delta' => ['type' => 'flat', 'value' => 0] ],
					[ 'value' => 'A4', 'delta' => ['type' => 'flat', 'value' => 150] ],
					[ 'value' => 'A3', 'delta' => ['type' => 'flat', 'value' => 350] ],
				]],
				'frame' => [ 'type' => 'select', 'values' => [
					[ 'value' => 'none', 'delta' => ['type' => 'flat', 'value' => 0] ],
					[ 'value' => 'basic', 'delta' => ['type' => 'flat', 'value' => 199] ],
					[ 'value' => 'premium', 'delta' => ['type' => 'flat', 'value' => 399] ],
				]],
				'material' => [ 'type' => 'select', 'values' => [
					[ 'value' => 'paper', 'delta' => ['type' => 'flat', 'value' => 0] ],
					[ 'value' => 'canvas', 'delta' => ['type' => 'flat', 'value' => 199] ],
				]]
			]];
		} else {
			// Generic: textLength only (safe default)
			$schema = [ 'options' => [
				'textLength' => [ 'type' => 'range', 'unit' => 'chars', 'tiers' => [
					[ 'max' => 30, 'delta' => ['type' => 'flat', 'value' => 0] ],
					[ 'max' => 80, 'delta' => ['type' => 'flat', 'value' => 99] ],
					[ 'max' => 160, 'delta' => ['type' => 'flat', 'value' => 199] ],
				]]
			]];
		}

		$json = $mysqli->real_escape_string(json_encode($schema));
		$mysqli->query("UPDATE artworks SET pricing_schema='$json' WHERE id=$id");
		$updated++;
	}

	echo json_encode(['status'=>'success','updated'=>$updated]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}








