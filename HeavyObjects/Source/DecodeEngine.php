<?php
namespace HeavyObjects\Source;

use HeavyObjects\Source\DecodeState;

class Engine
{
    /**
     * Stream
     *
     * @var null|resource
     */
    private $Stream = null;

    /**
     * Private Array of DecodeState
     *
     * @var DecodeState[]
     */
    private $DecodeState = [];

    /**
     * Private Current DecodeState
     *
     * @var null|DecodeState
     */
    private $CurrentDecodeState = null;

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
     * Object start position
     *
     * @var null|integer
     */
    public $_S_ = null;

    /**
     * Object file end position
     *
     * @var null|integer
     */
    public $_E_ = null;

    /**
     * Starts from $_S_ till $_E_
     *
     * @var null|integer
     */
    private $CharCounter = null;

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
     * Start processing the Object string
     *
     * @param boolean $index Index output
     * @return void
     */
    public function process($index = false)
    {
        // Flags Variable
        $quote = false;

        // Values inside Quotes
        $keyValue = '';
        $valueValue = '';

        // Values without Quotes
        $nullStr = null;

        // Variable Mode - key/value;
        $varMode = 'keyValue';

        $strToEscape  = '';
        $prevIsEscape = false;

        $this->CharCounter = $this->_S_ !== null ? $this->_S_ : 0;
        fseek($this->Stream, $this->CharCounter, SEEK_SET);

        for(;
            (
                ($char = fgetc($this->Stream)) !== false &&
                (
                    ($this->_E_ === null) ||
                    ($this->_E_ !== null && $this->CharCounter <= $this->_E_)
                )
            )
            ;$this->CharCounter++
        ) {
            switch (true) {
                case $quote === false:
                    switch (true) {
                        // Start of Key or value inside quote
                        case $char === '"':
                            $quote = true;
                            $nullStr = '';
                            break;

                        //Switch Mode to value collection after colon
                        case $char === ':':
                            $varMode = 'valueValue';
                            break;

                        // Start or End of Array
                        case in_array($char, ['[',']','{','}']):
                            $arr = $this->handleDecodeOpenClose($char, $keyValue, $nullStr, $index);
                            if ($arr !== false) {
                                yield $arr['key'] => $arr['value'];
                            }
                            $keyValue = $valueValue = '';
                            $varMode = 'keyValue';
                            break;

                        // Check for null values
                        case $char === ',' && !is_null($nullStr):
                            $nullStr = $this->checkNullStr($nullStr);
                            switch ($this->CurrentDecodeState->Mode) {
                                case 'Array':
                                    $this->CurrentDecodeState->ArrayValues[] = $nullStr;
                                    break;
                                case 'Object':
                                    if (!empty($keyValue)) {
                                        $this->CurrentDecodeState->ObjectValues[$keyValue] = $nullStr;
                                    }
                                    break;
                            }
                            $nullStr = null;
                            $keyValue = $valueValue = '';
                            $varMode = 'keyValue';
                            break;

                        //Switch Mode to value collection after colon
                        case in_array($char, $this->Escape):
                            break;

                        // Append char to null string
                        case !in_array($char, $this->Escape):
                            $nullStr .= $char;
                            break;
                    }
                    break;

                case $quote === true:
                    switch (true) {
                        // Collect string to be escaped
                        case $varMode === 'valueValue' && ($char === '\\' || ($prevIsEscape && in_array($strToEscape . $char , $this->Replace))):
                            $strToEscape .= $char;
                            $prevIsEscape = true;
                            break;

                        // Escape value with char
                        case $varMode === 'valueValue' && $prevIsEscape === true && in_array($strToEscape . $char , $this->Replace):
                            $$varMode .= str_replace($this->Replace, $this->Escape, $strToEscape . $char);
                            $strToEscape = '';
                            $prevIsEscape = false;
                            break;

                        // Escape value without char
                        case $varMode === 'valueValue' && $prevIsEscape === true && in_array($strToEscape , $this->Replace):
                            $$varMode .= str_replace($this->Replace, $this->Escape, $strToEscape) . $char;
                            $strToEscape = '';
                            $prevIsEscape = false;
                            break;

                        // Closing double quotes
                        case $char === '"':
                            $quote = false;
                            switch (true) {
                                // Closing qoute of Key
                                case $varMode === 'keyValue':
                                    $varMode = 'valueValue';
                                    break;

                                // Closing qoute of Value
                                case $varMode === 'valueValue':
                                    $this->CurrentDecodeState->ObjectValues[$keyValue] = $valueValue;
                                    $keyValue = $valueValue = '';
                                    $varMode = 'keyValue';
                                    break;
                            }
                            break;

                        // Collect values for key or value
                        default:
                            $$varMode .= $char;
                    }
                    break;
            }
        }
        $this->DecodeState = [];
        $this->CurrentDecodeState = null;
    }

    /**
     * Get Object string
     *
     * @param boolean $index Index output
     * @return string
     */
    public function getObjectString()
    {
        $offset = $this->_S_ !== null ? $this->_S_ : 0;
        $length = $this->_E_ - $offset + 1;

        return stream_get_contents($this->Stream, $length, $offset);
    }

    /**
     * Handles array / object open close char
     *
     * @param string $char     Character among any one "[" "]" "{" "}"
     * @param string $keyValue String value of key of an object
     * @param string $nullStr  String present in Object without double quotes
     * @param boolean   $index    Index output
     * @return array
     */
    private function handleDecodeOpenClose($char, $keyValue, $nullStr, $index)
    {
        $arr = false;
        switch ($char) {
            case '[':
                if (!$index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => $this->getObjectDecodeValues()
                    ];
                }
                $this->increment();
                $this->startOfArray($keyValue);
                break;
            case '{':
                if (!$index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => $this->getObjectDecodeValues()
                    ];
                }
                $this->increment();
                $this->startOfObject($keyValue);
                break;
            case ']':
                if (!empty($keyValue)) {
                    $this->CurrentDecodeState->ArrayValues[] = $keyValue;
                    if (is_null($this->CurrentDecodeState->ArrayKey)) {
                        $this->CurrentDecodeState->ArrayKey = 0;
                    } else {
                        $this->CurrentDecodeState->ArrayKey++;
                    }
                }
                if ($index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => [
                            '_S_' => $this->CurrentDecodeState->_S_,
                            '_E_' => $this->CharCounter
                        ]
                    ];
                } else {
                    if (!empty($this->CurrentDecodeState->ArrayValues)) {
                        $arr = [
                            'key' => $this->getKeys(),
                            'value' => $this->CurrentDecodeState->ArrayValues
                        ];
                    }
                }
                $this->CurrentDecodeState = null;
                $this->setPreviousDecodeState();
                break;
            case '}':
                if (!empty($keyValue) && !empty($nullStr)) {
                    $nullStr = $this->checkNullStr($nullStr);
                    $this->CurrentDecodeState->ObjectValues[$keyValue] = $nullStr;
                }
                if ($index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => [
                            '_S_' => $this->CurrentDecodeState->_S_,
                            '_E_' => $this->CharCounter
                        ]
                    ];
                } else {
                    if (!empty($this->CurrentDecodeState->ObjectValues)) {
                        $arr = [
                            'key' => $this->getKeys(),
                            'value' => $this->CurrentDecodeState->ObjectValues
                        ];
                    }
                }
                $this->CurrentDecodeState = null;
                $this->setPreviousDecodeState();
                break;
        }
        if (
            $arr !== false &&
            !empty($arr) &&
            isset($arr['value']) &&
            $arr['value'] !== false &&
            count($arr['value']) > 0
        ) {
            return $arr;
        }
        return false;
    }

    /**
     * Check String present in Object without double quotes for null or integer
     *
     * @param string $nullStr String present in Object without double quotes
     * @return mixed
     */
    private function checkNullStr($nullStr)
    {
        $return = false;
        if ($nullStr === 'null') {
            $return = null;
        } elseif (is_numeric($nullStr)) {
            $return = (int)$nullStr;
        }
        if ($return === false) {
            $this->isBadString($nullStr);
        }
        return $return;
    }

    /**
     * Start of array
     *
     * @param null|string $key Used while creating simple array inside an associative array and $key is the key
     * @return void
     */
    private function startOfArray($key = null)
    {
        $this->pushCurrentDecodeState($key);
        $this->CurrentDecodeState = new DecodeState('Array', $key);
        $this->CurrentDecodeState->_S_ = $this->CharCounter;
    }

    /**
     * Start of object
     *
     * @param null|string $key Used while creating associative array inside an associative array and $key is the key
     * @return void-
     */
    private function startOfObject($key = null)
    {
        $this->pushCurrentDecodeState($key);
        $this->CurrentDecodeState = new DecodeState('Object', $key);
        $this->CurrentDecodeState->_S_ = $this->CharCounter;
    }

    /**
     * Push current object
     *
     * @return void
     */
    private function pushCurrentDecodeState($key)
    {
        if ($this->CurrentDecodeState) {
            if ($this->CurrentDecodeState->Mode === 'Object' && (is_null($key) || empty(trim($key)))) {
                $this->isBadString($key);
            }
            if ($this->CurrentDecodeState->Mode === 'Array' && (is_null($key) || empty(trim($key)))) {
                $this->isBadString($key);
            }
            array_push($this->DecodeState, $this->CurrentDecodeState);
        }
    }

    /**
     * Pop Previous object
     *
     * @return void
     */
    private function setPreviousDecodeState()
    {
        if (count($this->DecodeState) > 0) {
            $this->CurrentDecodeState = array_pop($this->DecodeState);
        } else {
            $this->CurrentDecodeState = null;
        }
    }

    /**
     * Increment ArrayKey counter for array of objects or arrays
     *
     * @return void
     */
    private function increment()
    {
        if (
            !is_null($this->CurrentDecodeState) &&
            $this->CurrentDecodeState->Mode === 'Array'
        ) {
            if (is_null($this->CurrentDecodeState->ArrayKey)) {
                $this->CurrentDecodeState->ArrayKey = 0;
            } else {
                $this->CurrentDecodeState->ArrayKey++;
            }
        }
    }

    /**
     * Returns extracted object values
     *
     * @return array
     */
    private function getObjectDecodeValues()
    {
        $arr = false;
        if (
            !is_null($this->CurrentDecodeState) &&
            $this->CurrentDecodeState->Mode === 'Object' &&
            count($this->CurrentDecodeState->ObjectValues) > 0
        ) {
            $arr = $this->CurrentDecodeState->ObjectValues;
            $this->CurrentDecodeState->ObjectValues = [];
        }
        return $arr;
    }

    /**
     * Check for a valid string(JSON)
     *
     * @return void
     */
    private function isBadString($str)
    {
        $str =  !is_null($str) ? trim($str) : $str;
        if (!empty($str)) {
            die("Invalid String: {$str}");
        }
    }

    /**
     * Generated Array
     *
     * @param boolean $index true for normal array / false for associative array
     * @return array
     */
    private function getKeys()
    {
        $keys = [];
        $return = &$keys;
        $objCount = count($this->DecodeState);
        if ($objCount > 0) {
            for ($i=0; $i<$objCount; $i++) {
                switch ($this->DecodeState[$i]->Mode) {
                    case 'Object':
                        if (!is_null($this->DecodeState[$i]->ObjectKey)) {
                            $keys[] = $this->DecodeState[$i]->ObjectKey;
                        }
                        break;
                    case 'Array':
                        if (!is_null($this->DecodeState[$i]->ObjectKey)) {
                            $keys[] = $this->DecodeState[$i]->ObjectKey;
                        }
                        if (!is_null($this->DecodeState[$i]->ArrayKey)) {
                            $keys[] = $this->DecodeState[$i]->ArrayKey;
                        }
                        break;
                }
            }
        }
        if ($this->CurrentDecodeState) {
            switch ($this->CurrentDecodeState->Mode) {
                case 'Object':
                    if (!is_null($this->CurrentDecodeState->ObjectKey)) {
                        $keys[] = $this->CurrentDecodeState->ObjectKey;
                    }
                    break;
                case 'Array':
                    if (!is_null($this->CurrentDecodeState->ObjectKey)) {
                        $keys[] = $this->CurrentDecodeState->ObjectKey;
                    }
                    break;
            }
        }
        return $return;
    }

    /**
     * Generated Object Array
     *
     * @return array
     */
    private function getObjectKeys()
    {
        $keys = [];
        $return = &$keys;
        $objCount = count($this->DecodeState);
        if ($objCount > 0) {
            for ($i=0; $i<$objCount; $i++) {
                switch ($this->DecodeState[$i]->Mode) {
                    case 'Object':
                        if (!is_null($this->DecodeState[$i]->ObjectKey)) {
                            $keys[$this->DecodeState[$i]->ObjectKey] = [];
                            $keys = &$keys[$this->DecodeState[$i]->ObjectKey];
                        }
                        break;
                    case 'Array':
                        if (!is_null($this->DecodeState[$i]->ObjectKey)) {
                            $keys[$this->DecodeState[$i]->ObjectKey] = [];
                            $keys = &$keys[$this->DecodeState[$i]->ObjectKey];
                        }
                        if (!is_null($this->DecodeState[$i]->ArrayKey)) {
                            $keys[$this->DecodeState[$i]->ArrayKey] = [];
                            $keys = &$keys[$this->DecodeState[$i]->ArrayKey];
                        }
                        break;
                }
            }
        }
        if ($this->CurrentDecodeState) {
            switch ($this->CurrentDecodeState->Mode) {
                case 'Object':
                    if (!is_null($this->CurrentDecodeState->ObjectKey)) {
                        $keys[$this->CurrentDecodeState->ObjectKey] = [];
                        $keys = &$keys[$this->CurrentDecodeState->ObjectKey];
                    }
                    break;
                case 'Array':
                    if (!is_null($this->CurrentDecodeState->ObjectKey)) {
                        $keys[$this->CurrentDecodeState->ObjectKey] = [];
                        $keys = &$keys[$this->CurrentDecodeState->ObjectKey];
                    }
                    break;
            }
        }
        return $return;
    }
}
