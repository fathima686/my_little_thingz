<?php
// Adds pricing_schema JSON column to artworks if not present
// Run via: php backend/database/migrate_add_pricing_schema.php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli('localhost', 'root', '', 'my_little_thingz');
$mysqli->set_charset('utf8mb4');

function columnExists(mysqli $db, string $table, string $column): bool {
	try {
		$res = $db->query("SHOW COLUMNS FROM `" . $db->real_escape_string($table) . "` LIKE '" . $db->real_escape_string($column) . "'");
		return $res && $res->num_rows > 0;
	} catch (Throwable $e) { return false; }
}

try {
	if (!columnExists($mysqli, 'artworks', 'pricing_schema')) {
		// Prefer JSON if supported; fallback to TEXT
		$engine = 'JSON';
		try { $mysqli->query("ALTER TABLE artworks ADD COLUMN pricing_schema JSON NULL AFTER description"); }
		catch (Throwable $e) {
			$mysqli->query("ALTER TABLE artworks ADD COLUMN pricing_schema TEXT NULL AFTER description");
			$engine = 'TEXT';
		}
		echo json_encode(['status'=>'success','message'=>'pricing_schema column added','type'=>$engine]);
	} else {
		echo json_encode(['status'=>'ok','message'=>'pricing_schema already exists']);
	}
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

// Seed a couple of simple schemas for demo if artworks exist (non-destructive)
try {
	$res = $mysqli->query("SELECT id, title FROM artworks ORDER BY id ASC LIMIT 5");
	while ($row = $res->fetch_assoc()) {
		$id = (int)$row['id'];
		// Only set if null
		$chk = $mysqli->query("SELECT pricing_schema FROM artworks WHERE id=$id");
		$val = $chk->fetch_assoc()['pricing_schema'] ?? null;
		if ($val === null || $val === '') {
			$schema = [
				'base_key' => 'base',
				'options' => [
					'size' => [
						'type' => 'select',
						'values' => [
							['value' => 'A5', 'delta' => ['type' => 'flat', 'value' => 0]],
							['value' => 'A4', 'delta' => ['type' => 'flat', 'value' => 150]],
							['value' => 'A3', 'delta' => ['type' => 'flat', 'value' => 350]]
						]
					],
					'frame' => [
						'type' => 'select',
						'values' => [
							['value' => 'none', 'delta' => ['type' => 'flat', 'value' => 0]],
							['value' => 'basic', 'delta' => ['type' => 'flat', 'value' => 199]],
							['value' => 'premium', 'delta' => ['type' => 'flat', 'value' => 399]]
						]
					],
					'textLength' => [
						'type' => 'range',
						'unit' => 'chars',
						'tiers' => [
							['max' => 30, 'delta' => ['type' => 'flat', 'value' => 0]],
							['max' => 80, 'delta' => ['type' => 'flat', 'value' => 99]],
							['max' => 160, 'delta' => ['type' => 'flat', 'value' => 199]]
						]
					]
				]
			];
			$json = $mysqli->real_escape_string(json_encode($schema));
			$mysqli->query("UPDATE artworks SET pricing_schema='$json' WHERE id=$id");
		}
	}
	echo "\n" . json_encode(['status'=>'ok','message'=>'seeded demo pricing_schema where missing']);
} catch (Throwable $e) {
	// ignore seed errors
}



