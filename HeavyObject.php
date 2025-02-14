<?php
use HeavyObjects\Source\HeavyObject;

include_once('autoload.php');

$stream = fopen("filename.txt", "wr+b");
// $stream = fopen("php://temp", "rw+b");
$heavyObject = new HeavyObject($stream);
$heavyObject->init();

// Execute DB Query
$stmt = $db->select($sql);

// Load/Write records to file
for ($i=0; $row = $stmt->fetch(PDO::FETCH_ASSOC); $i++) {
    $heavyObject->write($row, $keys = "row:{$i}");
}

// Get/Read records from file
$key = 10;
$row = $heavyObject->read("row:{$key}");
print_r($row);
