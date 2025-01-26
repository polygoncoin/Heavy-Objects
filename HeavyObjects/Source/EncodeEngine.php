<?php
namespace HeavyObjects\Source;

use HeavyObjects\Source\EncodeState;

class EncodeEngine
{
    /**
     * Stream
     *
     * @var null|resource
     */
    private $Stream = null;

    /**
     * Private Current EncodeState
     *
     * @var EncodeState[]
     */
    private $EncodeState = [];

    /**
     * Private Current EncodeState
     *
     * @var null|EncodeState
     */
    private $CurrentEncodeState = null;

    /**
     * Private Characters to be escaped
     *
     * @var string[]
     */
    private $Escape = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c", ' ');

    /**
     * Private Characters that are escaped
     *
     * @var string[]
     */
    private $Replace = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b", ' ');

    /**
     * Encode constructor
     *
     * @param resource $Stream
     * @return void
     */
    public function __construct(&$Stream)
    {
        $this->Stream = &$Stream;
    }

    /**
     * Write to temporary stream
     *
     * @return void
     */
    public function write($str)
    {
        fwrite($this->Stream, $str);
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
        $str = str_replace($this->Escape, $this->Replace, $str);
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
        if ($this->CurrentEncodeState) {
            $this->write($this->CurrentEncodeState->Comma);
        }
        if (is_array($arr)) {
            $this->write(json_encode($arr));
        } else {
            $this->write($this->escape($arr));
        }
        if ($this->CurrentEncodeState) {
            $this->CurrentEncodeState->Comma = ',';
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
        if ($this->CurrentEncodeState->Mode !== 'Array') {
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
        if ($this->CurrentEncodeState->Mode !== 'Object') {
            throw new Exception('Mode should be Object');
        }
        $this->write($this->CurrentEncodeState->Comma);
        $this->write($this->escape($key) . ':');
        $this->CurrentEncodeState->Comma = '';
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
        if ($this->CurrentEncodeState) {
            $this->write($this->CurrentEncodeState->Comma);
            array_push($this->EncodeState, $this->CurrentEncodeState);
        }
        $this->CurrentEncodeState = new EncodeState('Array');
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
        $this->CurrentEncodeState = null;
        if (count($this->EncodeState)>0) {
            $this->CurrentEncodeState = array_pop($this->EncodeState);
            $this->CurrentEncodeState->Comma = ',';
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
        if ($this->CurrentEncodeState) {
            if ($this->CurrentEncodeState->Mode === 'Object' && is_null($key)) {
                throw new Exception('Object inside an Object should be supported with a Key');
            }
            $this->write($this->CurrentEncodeState->Comma);
            array_push($this->EncodeState, $this->CurrentEncodeState);
        }
        $this->CurrentEncodeState = new EncodeState('Object');
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
        $this->CurrentEncodeState = null;
        if (count($this->EncodeState)>0) {
            $this->CurrentEncodeState = array_pop($this->EncodeState);
            $this->CurrentEncodeState->Comma = ',';
        }
    }

    /**
     * Checks was properly closed.
     *
     * @return void
     */
    public function end()
    {
        while ($this->CurrentEncodeState && $this->CurrentEncodeState->Mode) {
            switch ($this->CurrentEncodeState->Mode) {
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
