<?php
namespace HeavyObjects\Source;

class DecodeState
{
    /**
     * Start position
     *
     * @var null|integer
     */
    public $_S_ = null;

    /**
     * End position
     *
     * @var null|integer
     */
    public $_E_ = null;

    /**
     * Object key for parant object
     *
     * @var null|string
     */
    public $ObjectKey = null;

    /**
     * Object Values
     *
     * @var array
     */
    public $ObjectValues = [];

    /**
     * Constructor
     *
     * @param string $mode Values can be one among Array
     */
    public function __construct($objectKey = null)
    {
        $objectKey = !is_null($objectKey) ? trim($objectKey) : $objectKey;
        $this->ObjectKey = !empty($objectKey) ? $objectKey : null;
    }
}
