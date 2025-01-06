<?php
namespace HeavyObject;

class EncodeObject
{
    public $Mode = '';
    public $Comma = '';

    /**
     * Constructor
     *
     * @param string $Mode Values can be one among Array/Object
     */
    public function __construct($mode)
    {
        $this->Mode = $mode;
    }
}
