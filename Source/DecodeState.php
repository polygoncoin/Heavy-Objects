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
     * Object / Array
     *
     * @var string
     */
    public $Mode = '';

    /**
     * Object key for parant object
     *
     * @var null|string
     */
    public $ObjectKey = null;
    
    /**
     * Array key for parant object
     *
     * @var null|string
     */
    public $ArrayKey = null;

    /**
     * Object Values
     *
     * @var array
     */
    public $ObjectValues = [];

    /**
     * Array Values
     *
     * @var string[]
     */
    public $ArrayValues = [];

    /**
     * Constructor
     *
     * @param string $mode Values can be one among Array
     */
    public function __construct($mode, $objectKey = null)
    {
        $this->Mode = $mode;

        $objectKey = !is_null($objectKey) ? trim($objectKey) : $objectKey;
        $this->ObjectKey = !empty($objectKey) ? $objectKey : null;
    }
}
