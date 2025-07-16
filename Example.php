<?php
require_once __DIR__ . '/Autoload.php'; // phpcs:ignore

use HeavyObjects\HeavyObject;

$stream = fopen(filename: "php://temp", mode: "wr+b");
$heavyObject = new HeavyObject(stream: $stream);

// Execute DB Query
$stmt = $db->select($sql);

// Load/Write/Update records to file
for ($i=0; $row = $stmt->fetch(PDO::FETCH_ASSOC); $i++) {
    $heavyObject->write(array: $row, keys: $keys = "row:{$i}");
}

// Get/Read records from file
$key = 10;
$row = $heavyObject->read(keys: "row:{$key}");
echo '<pre>';
echo 'row:'; print_r(value: $row);
echo 'Count:' . $heavyObject->count(keys: 'row');
