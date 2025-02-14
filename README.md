# Heavy-Objects
Manage Heavy(RAM intensive) Array/Object Collections via single File in filesystem using limited RAM.

## Examples

```PHP
<?php
use HeavyObjects\Source\HeavyObject;

include_once('autoload.php');

$stream = fopen("php://temp", "rw+b");
$heavyObject = new HeavyObject($stream);

// Execute DB Query
$stmt = $db->select($sql);

// Load/Write/Update records to file
for ($i=0; $row = $stmt->fetch(PDO::FETCH_ASSOC); $i++) {
    $heavyObject->write($row, $keys = "row:{$i}");
}

// Get/Read particular records from file
$key = 10;
$row = $heavyObject->read("row:{$key}");
print_r($row);
```
