<?php
namespace HeavyObject;

class DecodeObject
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
     * Assoc / Array
     *
     * @var string
     */
    public $Mode = '';

    /**
     * Assoc key for parant object
     *
     * @var null|string
     */
    public $AssocKey = null;
    
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
    public $AssocValues = [];

    /**
     * Array Values
     *
     * @var string[]
     */
    */
    public $ArrayValues = [];

    /**
     * Constructor
     *
     * @param string $mode Values can be one among Array
     */
    public function __construct($mode, $assocKey = null)
    {
        $this->Mode = $mode;

        $assocKey = !is_null($assocKey) ? trim($assocKey) : $assocKey;
        $this->AssocKey = !empty($assocKey) ? $assocKey : null;
    }
}
