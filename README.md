# Heavy-Objects
Manage Heavy(RAM intensive) Array/Object Collections via single File in filesystem using limited RAM.

## Examples

```PHP
<?php
use HeavyObjects\Source\HeavyObject;

include_once('autoload.php');

$stream = fopen("php://temp", "rw+b");
$heavyObject = new HeavyObject($stream);

// Load/Write records to file
for ($i = 0; $i < 10000; $i++) {
    $row = ['key' => 123];
    $heavyObject->write($row, $keys = "row:{$i}");
}

// Update records in file
for ($i = 0; $i < 10000; $i++) {
    $row = ['key' => rand()];
    $heavyObject->write($row, $keys = "row:{$i}");
}

// Get/Read records from file
for ($i = 0; $i < 10000; $i++) {
    $row = $heavyObject->read("row:{$i}");
}

echo '<pre>';
echo 'row:'; print_r($row);
echo 'Count:' . $heavyObject->count('row');
```
