<?php
namespace HeavyObject;

use HeavyObject\DecodeObject;

class DecodeEngine
{
    /**
     * Private File Handle
     *
     * @var null|resource
     */
    private $FileHandle = null;

    /**
     * Private Array of DecodeObject
     *
     * @var DecodeObject[]
     */
    private $DecodeObject = [];

    /**
     * Private Current DecodeObject
     *
     * @var null|DecodeObject
     */
    private $CurrentDecodeObject = null;

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
     * @return void
     */
    public function __construct(&$FileHandle)
    {
        $this->FileHandle = &$FileHandle;
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
        fseek($this->FileHandle, $this->CharCounter, SEEK_SET);
        
        for(;
            (
                ($char = fgetc($this->FileHandle)) !== false && 
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
                            $arr = $this->handleOpenClose($char, $keyValue, $nullStr, $index);
                            if ($arr !== false) {
                                yield $arr['key'] => $arr['value'];
                            }
                            $keyValue = $valueValue = '';
                            $varMode = 'keyValue';
                            break;
                    
                        // Check for null values
                        case $char === ',' && !is_null($nullStr):
                            $nullStr = $this->checkNullStr($nullStr);
                            switch ($this->CurrentDecodeObject->Mode) {
                                case 'Array':
                                    $this->CurrentDecodeObject->ArrayValues[] = $nullStr;
                                    break;
                                case 'Assoc':
                                    if (!empty($keyValue)) {
                                        $this->CurrentDecodeObject->AssocValues[$keyValue] = $nullStr;
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
                                    $this->CurrentDecodeObject->AssocValues[$keyValue] = $valueValue;
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
        $this->DecodeObject = [];
        $this->CurrentDecodeObject = null;
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

        return stream_get_contents($this->FileHandle, $length, $offset);
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
    private function handleOpenClose($char, $keyValue, $nullStr, $index)
    {
        $arr = false;
        switch ($char) {
            case '[':
                if (!$index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => $this->getObjectValues()
                    ];
                }
                $this->increment();
                $this->startArray($keyValue);
                break;
            case '{':
                if (!$index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => $this->getObjectValues()
                    ];
                }
                $this->increment();
                $this->startObject($keyValue);
                break;
            case ']':
                if (!empty($keyValue)) {
                    $this->CurrentDecodeObject->ArrayValues[] = $keyValue;
                    if (is_null($this->CurrentDecodeObject->ArrayKey)) {
                        $this->CurrentDecodeObject->ArrayKey = 0;
                    } else {
                        $this->CurrentDecodeObject->ArrayKey++;
                    }
                }
                if ($index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => [
                            '_S_' => $this->CurrentDecodeObject->_S_,
                            '_E_' => $this->CharCounter
                        ]
                    ];
                } else {
                    if (!empty($this->CurrentDecodeObject->ArrayValues)) {
                        $arr = [
                            'key' => $this->getKeys(),
                            'value' => $this->CurrentDecodeObject->ArrayValues
                        ];    
                    }
                }
                $this->CurrentDecodeObject = null;
                $this->popPreviousObject();
                break;
            case '}':
                if (!empty($keyValue) && !empty($nullStr)) {
                    $nullStr = $this->checkNullStr($nullStr);
                    $this->CurrentDecodeObject->AssocValues[$keyValue] = $nullStr;
                }
                if ($index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => [
                            '_S_' => $this->CurrentDecodeObject->_S_,
                            '_E_' => $this->CharCounter
                        ]
                    ];
                } else {
                    if (!empty($this->CurrentDecodeObject->AssocValues)) {
                        $arr = [
                            'key' => $this->getKeys(),
                            'value' => $this->CurrentDecodeObject->AssocValues
                        ];    
                    }
                }
                $this->CurrentDecodeObject = null;
                $this->popPreviousObject();
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
     * @param null|string $key Used while creating simple array inside an objectiative array and $key is the key
     * @return void
     */
    private function startArray($key = null)
    {
        $this->pushCurrentDecodeObject($key);
        $this->CurrentDecodeObject = new DecodeObject('Array', $key);
        $this->CurrentDecodeObject->_S_ = $this->CharCounter;
    }

    /**
     * Start of object
     *
     * @param null|string $key Used while creating objectiative array inside an objectiative array and $key is the key
     * @return void-
     */
    private function startObject($key = null)
    {
        $this->pushCurrentDecodeObject($key);
        $this->CurrentDecodeObject = new DecodeObject('Assoc', $key);
        $this->CurrentDecodeObject->_S_ = $this->CharCounter;
    }

    /**
     * Push current object
     *
     * @return void
     */
    private function pushCurrentDecodeObject($key)
    {
        if ($this->CurrentDecodeObject) {
            if ($this->CurrentDecodeObject->Mode === 'Assoc' && (is_null($key) || empty(trim($key)))) {
                $this->isBadString($key);
            }
            if ($this->CurrentDecodeObject->Mode === 'Array' && (is_null($key) || empty(trim($key)))) {
                $this->isBadString($key);
            }
            array_push($this->DecodeObject, $this->CurrentDecodeObject);
        }
    }

    /**
     * Pop Previous object
     *
     * @return void
     */
    private function popPreviousObject()
    {
        if (count($this->DecodeObject) > 0) {
            $this->CurrentDecodeObject = array_pop($this->DecodeObject);
        } else {
            $this->CurrentDecodeObject = null;
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
            !is_null($this->CurrentDecodeObject) &&
            $this->CurrentDecodeObject->Mode === 'Array'
        ) {
            if (is_null($this->CurrentDecodeObject->ArrayKey)) {
                $this->CurrentDecodeObject->ArrayKey = 0;
            } else {
                $this->CurrentDecodeObject->ArrayKey++;
            }
        }
    }

    /**
     * Returns extracted object values
     *
     * @return array
     */
    private function getObjectValues()
    {
        $arr = false;
        if (
            !is_null($this->CurrentDecodeObject) && 
            $this->CurrentDecodeObject->Mode === 'Assoc' && 
            count($this->CurrentDecodeObject->AssocValues) > 0
        ) {
            $arr = $this->CurrentDecodeObject->AssocValues;
            $this->CurrentDecodeObject->AssocValues = [];
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
        $objCount = count($this->DecodeObject);
        if ($objCount > 0) {
            for ($i=0; $i<$objCount; $i++) {
                switch ($this->DecodeObject[$i]->Mode) {
                    case 'Assoc':
                        if (!is_null($this->DecodeObject[$i]->AssocKey)) {
                            $keys[] = $this->DecodeObject[$i]->AssocKey;
                        }
                        break;
                    case 'Array':
                        if (!is_null($this->DecodeObject[$i]->AssocKey)) {
                            $keys[] = $this->DecodeObject[$i]->AssocKey;
                        }
                        if (!is_null($this->DecodeObject[$i]->ArrayKey)) {
                            $keys[] = $this->DecodeObject[$i]->ArrayKey;
                        }
                        break;
                }
            }
        }
        if ($this->CurrentDecodeObject) {
            switch ($this->CurrentDecodeObject->Mode) {
                case 'Assoc':
                    if (!is_null($this->CurrentDecodeObject->AssocKey)) {
                        $keys[] = $this->CurrentDecodeObject->AssocKey;
                    }
                    break;
                case 'Array':
                    if (!is_null($this->CurrentDecodeObject->AssocKey)) {
                        $keys[] = $this->CurrentDecodeObject->AssocKey;
                    }
                    break;
            }
        }
        return $return;
    }

    /**
     * Generated Assoc Array
     * 
     * @return array
     */
    private function getAssocKeys()
    {
        $keys = [];
        $return = &$keys;
        $objCount = count($this->DecodeObject);
        if ($objCount > 0) {
            for ($i=0; $i<$objCount; $i++) {
                switch ($this->DecodeObject[$i]->Mode) {
                    case 'Assoc':
                        if (!is_null($this->DecodeObject[$i]->AssocKey)) {
                            $keys[$this->DecodeObject[$i]->AssocKey] = [];
                            $keys = &$keys[$this->DecodeObject[$i]->AssocKey];
                        }
                        break;
                    case 'Array':
                        if (!is_null($this->DecodeObject[$i]->AssocKey)) {
                            $keys[$this->DecodeObject[$i]->AssocKey] = [];
                            $keys = &$keys[$this->DecodeObject[$i]->AssocKey];
                        }
                        if (!is_null($this->DecodeObject[$i]->ArrayKey)) {
                            $keys[$this->DecodeObject[$i]->ArrayKey] = [];
                            $keys = &$keys[$this->DecodeObject[$i]->ArrayKey];
                        }
                        break;
                }
            }
        }
        if ($this->CurrentDecodeObject) {
            switch ($this->CurrentDecodeObject->Mode) {
                case 'Assoc':
                    if (!is_null($this->CurrentDecodeObject->AssocKey)) {
                        $keys[$this->CurrentDecodeObject->AssocKey] = [];
                        $keys = &$keys[$this->CurrentDecodeObject->AssocKey];
                    }
                    break;
                case 'Array':
                    if (!is_null($this->CurrentDecodeObject->AssocKey)) {
                        $keys[$this->CurrentDecodeObject->AssocKey] = [];
                        $keys = &$keys[$this->CurrentDecodeObject->AssocKey];
                    }
                    break;
            }
        }
        return $return;
    }
}
