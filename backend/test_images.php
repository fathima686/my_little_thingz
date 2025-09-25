<?php
header('Content-Type: application/json');

$mysqli = new mysqli('localhost', 'root', '', 'my_little_thingz');
if ($mysqli->connect_error) {
    echo json_encode(['error' => 'Connection failed: ' . $mysqli->connect_error]);
    exit;
}

$result = $mysqli->query('SELECT id, request_id, image_path FROM custom_request_images LIMIT 5');
$images = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    $result->free();
}

echo json_encode(['images' => $images]);
$mysqli->close();
?>