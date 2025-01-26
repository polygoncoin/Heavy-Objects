<?php
namespace HeavyObjects\Source;

use HeavyObjects\Source\EncodeEngine;

class Encode
{
    /**
     * Stream
     *
     * @var null|resource
     */
    private $Stream = null;

    /**
     * Allowed File length
     *
     * @var integer
     */
    private $MaxFileLength = 4 * 1024 * 1024 * 1024; // 4 GB

    /**
     * Private DecodeEngine
     *
     * @var null|EncodeEngine
     */
    private $EncodeEngine = null;

    /**
     * Decode constructor
     *
     * @param resource $Stream
     * @return void
     */
    public function __construct(&$Stream)
    {
        $this->Stream = &$Stream;
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        // Init Encode Engine
        $this->EncodeEngine = new EncodeEngine($this->Stream);
    }
}
