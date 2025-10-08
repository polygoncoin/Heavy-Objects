<?php

require_once __DIR__ . '/AutoloadHeavyObjects.php'; // phpcs:ignore

use HeavyObjects\HeavyObject;

echo '<pre>';

$stream = fopen(filename: "php://temp", mode: "wr+b");
$heavyObject = new HeavyObject(stream: $stream);

// Load/Write/Update records to file
for ($i = 0; $i < 100; $i++) {
    $row = [];
    for ($j = 0; $j < 100; $j++) {
        $row["Key{$j}"] = rand();
    }
    $heavyObject->write($row, $keys = "row:{$i}");
}

echo nl2br(PHP_EOL . memory_get_usage()) . ' Bytes';

$rows = [];

for ($i = 0; $i < 100; $i++) {
    $row = [];
    for ($j = 0; $j < 100; $j++) {
        $row["Key{$j}"] = rand();
    }
    $rows[] = $row;
}

echo nl2br(PHP_EOL . memory_get_usage()) . ' Bytes';
