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

### Memory usage by 1000 HeavyObjects each with 100 keys (~2.5 MB)

```PHP
<?php
use Source\HeavyObject;

include_once('autoload.php');

$stream = fopen("php://temp", "wr+b");
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

### Copy Objects

```PHP
<?php
use Source\HeavyObject;

include_once('autoload.php');

$stream = fopen("php://temp", "wr+b");
$heavyObject = new HeavyObject($stream);

// Load/Write/Update records to file
for ($i=0; $i<5; $i++) {
    $row = [];
    for($j=0;$j<100;$j++) {
        $row["Key{$j}"] = rand();
    }
    $heavyObject->write($row, $keys = "row:{$i}");
    $heavyObject->write($row, $keys = "row1:keys:{$i}");
}
$heavyObject->copy('row:0','row1:0');
$heavyObject->copy('row:0','row1:keys:2');
$heavyObject->copy('row:0','row1:keys:_C_'); // row1:_C_ represents $index['row1'][]
```

### Move Objects

```PHP
<?php
use Source\HeavyObject;

include_once('autoload.php');

$stream = fopen("php://temp", "wr+b");
$heavyObject = new HeavyObject($stream);

// Load/Write/Update records to file
for ($i=0; $i<5; $i++) {
    $row = [];
    for($j=0;$j<100;$j++) {
        $row["Key{$j}"] = rand();
    }
    $heavyObject->write($row, $keys = "row:{$i}");
    $heavyObject->write($row, $keys = "row1:keys:{$i}");
}
$heavyObject->move('row:0','row1:0');
$heavyObject->move('row:0','row1:keys:2');
$heavyObject->move('row:0','row1:keys:_C_'); // row1:_C_ represents $index['row1'][]
```
