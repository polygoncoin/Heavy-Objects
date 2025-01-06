<?php
namespace HeavyObject;

use HeavyObject\EncodeObject;

class Encode
{
    /**
     * Temporary Stream
     *
     * @var string
     */
    private $TempStream = '';

    /**
     * Private Current EncodeObject
     *
     * @var EncodeObject[]
     */
    private $EncodeObject = [];

    /**
     * Private Current EncodeObject
     *
     * @var null|EncodeObject
     */
    private $CurrentEncodeObject = null;

    /**
     * Encode constructor
     */
    public function __construct()
    {
        $this->TempStream = fopen("php://temp", "w+b");
    }

    /**
     * Write to temporary stream
     * 
     * @return void
     */
    public function write($str)
    {
        fwrite($this->TempStream, $str);
    }

    /**
     * Escape the string key or value
     *
     * @param string $str key or value string.
     * @return string
     */
    private function escape($str)
    {
        if (is_null($str)) return 'null';
        $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
        $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");
        $str = str_replace($escapers, $replacements, $str);
        return '"' . $str . '"';
    }

    /**
     * Encodes both simple and associative array
     *
     * @param $arr string value escaped and array value json_encode function is applied.  
     * @return void
     */
    public function encode($arr)
    {
        if ($this->CurrentEncodeObject) {
            $this->write($this->CurrentEncodeObject->Comma);
        }
        if (is_array($arr)) {
            $this->write(json_encode($arr));
        } else {
            $this->write($this->escape($arr));
        }
        if ($this->CurrentEncodeObject) {
            $this->CurrentEncodeObject->Comma = ',';
        }
    }

    /**
     * Add simple array/value as in the format.
     *
     * @param $value data type is string/array. This is used to add value/array in the current Array.
     * @return void
     */
    public function addValue($value)
    {
        if ($this->CurrentEncodeObject->Mode !== 'Array') {
            throw new Exception('Mode should be Array');
        }
        $this->encode($value);
    }

    /**
     * Add simple array/value as in the format.
     *
     * @param string $key   key of associative array
     * @param        $value data type is string/array. This is used to add value/array in the current Array.
     * @return void
     */
    public function addKeyValue($key, $value)
    {
        if ($this->CurrentEncodeObject->Mode !== 'Object') {
            throw new Exception('Mode should be Object');
        }
        $this->write($this->CurrentEncodeObject->Comma);
        $this->write($this->escape($key) . ':');
        $this->CurrentEncodeObject->Comma = '';
        $this->encode($value);
    }

    /**
     * Start simple array
     *
     * @param null|string $key Used while creating simple array inside an associative array and $key is the key.
     * @return void
     */
    public function startArray($key = null)
    {
        if ($this->CurrentEncodeObject) {
            $this->write($this->CurrentEncodeObject->Comma);
            array_push($this->EncodeObject, $this->CurrentEncodeObject);
        }
        $this->CurrentEncodeObject = new EncodeObject('Array');
        if (!is_null($key)) {
            $this->write($this->escape($key) . ':');
        }
        $this->write('[');
    }

    /**
     * End simple array
     *
     * @return void
     */
    public function endArray()
    {
        $this->write(']');
        $this->CurrentEncodeObject = null;
        if (count($this->EncodeObject)>0) {
            $this->CurrentEncodeObject = array_pop($this->EncodeObject);
            $this->CurrentEncodeObject->Comma = ',';
        }
    }

    /**
     * Start simple array
     *
     * @param null|string $key Used while creating associative array inside an associative array and $key is the key.
     * @return void
     */
    public function startObject($key = null)
    {
        if ($this->CurrentEncodeObject) {
            if ($this->CurrentEncodeObject->Mode === 'Object' && is_null($key)) {
                throw new Exception('Object inside an Object should be supported with a Key');
            }
            $this->write($this->CurrentEncodeObject->Comma);
            array_push($this->EncodeObject, $this->CurrentEncodeObject);
        }
        $this->CurrentEncodeObject = new EncodeObject('Object');
        if (!is_null($key)) {
            $this->write($this->escape($key) . ':');
        }
        $this->write('{');
    }

    /**
     * End associative array
     *
     * @return void
     */
    public function endObject()
    {
        $this->write('}');
        $this->CurrentEncodeObject = null;
        if (count($this->EncodeObject)>0) {
            $this->CurrentEncodeObject = array_pop($this->EncodeObject);
            $this->CurrentEncodeObject->Comma = ',';
        }
    }

    /**
     * Checks was properly closed.
     *
     * @return void
     */
    public function end()
    {
        while ($this->CurrentEncodeObject && $this->CurrentEncodeObject->Mode) {
            switch ($this->CurrentEncodeObject->Mode) {
                case 'Array':
                    $this->endArray();
                    break;
                case 'Object':
                    $this->endObject();
                    break;
            }
        }
    }

    /** 
     * destruct functipn 
     */ 
    public function __destruct() 
    { 
        $this->end();
    }
}
