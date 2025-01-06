<?php
namespace HeavyObject;

use HeavyObject\Decode;
use HeavyObject\Encode;

class HeavyObject
{
    /**
     * File Handle
     *
     * @var resource
     */
    private $FileHandle = null;

    /**
     * Private Decode.
     */
    private Decode $Decode = null;

    /**
     * Private Encode.
     */
    private Encode $Encode = null;

    /**
     * Decode constructor
     * 
     * @param resource $FileHandle
     * @return void
     */
    public function __construct(&$FileHandle)
    {
        if (!$FileHandle) {
            die('Invalid file');
        }


        $this->FileHandle = &$FileHandle;

        // File Stats - Check for size
        $fileStats = fstat($this->FileHandle);
        if (isset($fileStats['size']) && $fileStats['size'] > $this->maxFileLength) {
            die('File size greater than allowed size');
        }
    }
}
