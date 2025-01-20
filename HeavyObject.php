<?php
namespace HeavyObjects;

use HeavyObjects\Source\Decode;
use HeavyObjects\Source\Encode;

class HeavyObject
{
    /**
     * Stream
     *
     * @var null|resource
     */
    private $Stream = null;

    /**
     * Changes Array
     *
     * @var array
     */
    private $TempArray = null;

    /**
     * Private Decode
     * 
     * @var null|Decode
     */
    private $Decode = null;

    /**
     * Private Encode.
     * 
     * @var null|Encode
     */
    private $Encode = null;

    /**
     * Decode constructor
     * 
     * @return void
     */
    public function __construct()
    {
        $this->Stream = fopen("php://temp", "rw+b");
        $this->Encode = new Encode($this->Stream);
    }
}
