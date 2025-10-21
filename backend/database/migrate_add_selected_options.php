<?php
// Adds selected_options JSON/TEXT columns to cart and order_items
// Run via: php backend/database/migrate_add_selected_options.php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli('localhost', 'root', '', 'my_little_thingz');
$mysqli->set_charset('utf8mb4');

function columnExists(mysqli $db, string $table, string $column): bool {
	try {
		$res = $db->query("SHOW COLUMNS FROM `" . $db->real_escape_string($table) . "` LIKE '" . $db->real_escape_string($column) . "'");
		return $res && $res->num_rows > 0;
	} catch (Throwable $e) { return false; }
}

header('Content-Type: application/json');

try {
	$results = [];

	// cart.selected_options
	if (!columnExists($mysqli, 'cart', 'selected_options')) {
		try {
			$mysqli->query("ALTER TABLE cart ADD COLUMN selected_options JSON NULL AFTER quantity");
			$results[] = ['table' => 'cart', 'column' => 'selected_options', 'type' => 'JSON', 'status' => 'added'];
		} catch (Throwable $e) {
			$mysqli->query("ALTER TABLE cart ADD COLUMN selected_options TEXT NULL AFTER quantity");
			$results[] = ['table' => 'cart', 'column' => 'selected_options', 'type' => 'TEXT', 'status' => 'added_fallback'];
		}
	} else {
		$results[] = ['table' => 'cart', 'column' => 'selected_options', 'status' => 'exists'];
	}

	// order_items.selected_options
	if (!columnExists($mysqli, 'order_items', 'selected_options')) {
		try {
			$mysqli->query("ALTER TABLE order_items ADD COLUMN selected_options JSON NULL AFTER price");
			$results[] = ['table' => 'order_items', 'column' => 'selected_options', 'type' => 'JSON', 'status' => 'added'];
		} catch (Throwable $e) {
			$mysqli->query("ALTER TABLE order_items ADD COLUMN selected_options TEXT NULL AFTER price");
			$results[] = ['table' => 'order_items', 'column' => 'selected_options', 'type' => 'TEXT', 'status' => 'added_fallback'];
		}
	} else {
		$results[] = ['table' => 'order_items', 'column' => 'selected_options', 'status' => 'exists'];
	}

	echo json_encode(['status' => 'success', 'changes' => $results]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}



