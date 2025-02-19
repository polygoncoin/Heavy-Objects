# Heavy-Objects
Manage Heavy(RAM intensive) Array/Object Collections via single File in filesystem using limited RAM.

## Examples

### Memory usage by 1000 raw objects each with 100 keys (~12 MB)

```PHP
<?php
$rows = [];

for ($i=0; $i<1000; $i++) {
    $row = [];
    for($j=0;$j<100;$j++) {
        $row["Key{$j}"] = rand();
    }
    $rows[] = $row;
}

echo '<pre>';
echo memory_get_usage(); // 11,842,144 bytes
```

### Memory usage by 1000 HeavyBbjects each with 100 keys (~2.5 MB)

```PHP
<?php
use HeavyObjects\Source\HeavyObject;

include_once('autoload.php');

$stream = fopen("php://temp", "rw+b");
$heavyObject = new HeavyObject($stream);

// Load/Write/Update records to file
for ($i=0; $i<1000; $i++) {
    $row = [];
    for($j=0;$j<100;$j++) {
        $row["Key{$j}"] = rand();
    }
    $heavyObject->write($row, $keys = "row:{$i}");
}

echo '<pre>';
echo memory_get_usage(); // 2,659,224 bytes
```
