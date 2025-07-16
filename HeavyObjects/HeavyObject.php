<?php
/**
 * Heavy Objects
 * php version 7
 *
 * @category  HeavyObjects
 * @package   HeavyObjects
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Microservices
 * @since     Class available since Release 1.0.0
 */
namespace HeavyObjects;

use HeavyObjects\Engine;

/**
 * Heavy Object
 * php version 7
 *
 * @category  HeavyObject
 * @package   HeavyObjects
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Microservices
 * @since     Class available since Release 1.0.0
 */
class HeavyObject
{
    /**
     * Stream
     *
     * @var null|resource
     */
    public $stream = null;

    /**
     * Index
     * Contains start and end positions for requested indexes
     *
     * @var null|array
     */
    public $fileIndex = null;

    /**
     * Private Engine
     *
     * @var null|Engine
     */
    private $_engine = null;

    /**
     * Decode constructor
     *
     * @param resource $stream File Stream
     */
    public function __construct(&$stream)
    {
        $this->stream = &$stream;
        $this->_engine = new Engine(stream: $this->stream);
    }

    /**
     * Check if key exist
     *
     * @param string|null $keys Key values separated by colon
     *
     * @return bool
     */
    public function isset($keys = null): bool
    {
        $data = false;
        $fileIndex = &$this->fileIndex;
        if (!is_null(value: $keys) && strlen(string: $keys) !== 0) {
            $keysArr = explode(separator: ':', string: $keys);
            for ($i = 0, $iCount = count(value: $keysArr); $i < $iCount; $i++) {
                $key = $keysArr[$i];
                if ($key === '') {
                    continue;
                }
                if ($data === false && isset($fileIndex[$key])) {
                    $fileIndex = &$fileIndex[$key];
                } else {
                    $data = $this->data(fileIndex: $fileIndex);
                    if (isset($data[$key])) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        }

        return !empty($fileIndex) ? true : false;
    }

    /**
     * Count of array element
     *
     * @param null|string $keys Key values separated by colon
     * 
     * @return mixed
     * @throws \Exception
     */
    public function count($keys = null): mixed
    {
        $fileIndex = &$this->fileIndex;
        if (!is_null(value: $keys) && strlen(string: $keys) !== 0) {
            foreach (explode(separator: ':', string: $keys) as $key) {
                if ($key === '') {
                    continue;
                }
                if (isset($fileIndex[$key])) {
                    $fileIndex = &$fileIndex[$key];
                } else {
                    throw new \Exception(message: 'Invalid key');
                }
            }
        }

        if (isset($fileIndex['_C_'])) {
            return $fileIndex['_C_'];
        }

        return 0;
    }

    /**
     * Pass the keys and get whole content belonging to keys
     *
     * @param string $keys Key values separated by colon
     * 
     * @return mixed
     * @throws \Exception
     */
    public function read($keys = null): mixed
    {
        $data = false;
        $fileIndex = &$this->fileIndex;
        if (!is_null(value: $keys) && strlen(string: $keys) !== 0) {
            foreach (explode(separator: ':', string: $keys) as $key) {
                if ($key === '') {
                    continue;
                }
                if ($data === false && isset($fileIndex[$key])) {
                    $fileIndex = &$fileIndex[$key];
                } else {
                    $data = $this->data(fileIndex: $fileIndex);
                    if (isset($data[$key])) {
                        return $data[$key];
                    } else {
                        throw new \Exception(message: 'Invalid key');
                    }
                }
            }
        }

        return $this->data(fileIndex: $fileIndex);
    }

    /**
     * Return data for indexes
     *
     * @param array $fileIndex File index
     *
     * @return mixed
     */
    public function data(&$fileIndex): mixed
    {
        $return = [];
        if (is_array(value: $fileIndex)) {
            if (isset($fileIndex['_S_']) 
                && isset($fileIndex['_E_'])
            ) {
                $this->_engine->_S_ = $fileIndex['_S_'];
                $this->_engine->_E_ = $fileIndex['_E_'];
                $return = json_decode(
                    json: $this->_engine->getObjectString(), 
                    associative: true
                );
            }
            foreach ($fileIndex as $key => &$fIndex) {
                if (in_array(needle: $key, haystack: ['', '_S_','_E_','_C_'])) {
                    continue;
                }
                if (isset($fIndex['_S_']) 
                    && isset($fIndex['_E_'])
                ) {
                    $this->_engine->_S_ = $fIndex['_S_'];
                    $this->_engine->_E_ = $fIndex['_E_'];
                    $return[$key] = json_decode(
                        json: $this->_engine->getObjectString(), 
                        associative: true
                    );
                } else {
                    if (is_array(value: $fIndex)) {
                        foreach ($this->data(fileIndex: $fIndex) as $k => $v) {
                            if ($k !== '') {
                                $return[$key][$k] = $v;
                            }
                        }
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Store array details.
     *
     * @param array        $array Data
     * @param string|array $keys  Key values separated by colon
     * 
     * @return void
     */
    public function write(array $array, string|array $keys = ''): void
    {
        $return = [];
        $keys = is_array(value: $keys) ?
            $keys : (
                strlen(string: $keys) === 0 ?
                [] : explode(
                    separator: ':', 
                    string: $keys
                )
        );
        $countObjects = 0;
        foreach ($array as $key => &$value) {
            if ($key === '') {
                continue;
            }
            if (is_array(value: $value)) {
                $keysArr = $keys;
                $keysArr[] = $key;
                $this->write(array: $value, keys: $keysArr);
            } else {
                $return[$key] = $value;
                if (ctype_digit(text: (string)$key)) {
                    $countObjects = $key;
                }
            }
        }
        if (count(value: $return) > 0) {
            if ($readArr = $this->read(
                keys: implode(
                    separator: ':', 
                    array: $keys
                )
            )
            ) {
                if ($readArr === $return) {
                    return;
                }
            }
            [$startIndex, $endIndex] = $this->_engine->write(arr: $return);
            // Update index.
            $fileIndex = &$this->fileIndex;
            for ($i=0, $iCount = count(value: $keys); $i < $iCount; $i++) {
                if ($keys[$i] === '') {
                    continue;
                }
                if (!isset($fileIndex[$keys[$i]])) {
                    $fileIndex[$keys[$i]] = [
                        '_C_' => 0
                    ];
                    if (ctype_digit(text: (string)$keys[$i])) {
                        $fileIndex['_C_']++;
                    }
                }
                $fileIndex = &$fileIndex[$keys[$i]];
            }
            $fileIndex['_S_'] = (int)$startIndex;
            $fileIndex['_E_'] = (int)$endIndex;
            $fileIndex['_C_'] = (int)$countObjects;
        }
    }

    /**
     * Move array from source to dest
     *
     * @param string $srcKey  Source Key values separated by colon
     * @param string $destKey Source Key values separated by colon
     * 
     * @return bool
     */
    public function move($srcKey, $destKey): bool
    {
        if ($this->isset(keys: $srcKey)) {
            $destKeys = explode(separator: ':', string: $destKey);
            $destFileIndex = &$this->fileIndex;
            for ($i=0, $iCount = count(value: $destKeys); $i < $iCount; $i++) {
                if ($destKeys[$i] === '') {
                    continue;
                }
                if ($destKeys[$i] === '_C_') {
                    $destKeys[$i] = $destFileIndex['_C_'];
                }
                if (!isset($destFileIndex[$destKeys[$i]])) {
                    $destFileIndex[$destKeys[$i]] = [
                        '_C_' => 0
                    ];
                    if (ctype_digit(text: (string)$destKeys[$i])) {
                        $destFileIndex['_C_']++;

                    }
                }
                $destFileIndex = &$destFileIndex[$destKeys[$i]];
            }
            if (ctype_digit(text: (string)$destKeys[--$i]) 
                && $i !== ($iCount-1)
            ) {
                $destFileIndex['_C_']++;
            }
            if (isset($destFileIndex['_C_'])) {
                $countObjects = $destFileIndex['_C_'];
            }

            $srcKeys = explode(separator: ':', string: $srcKey);
            $srcIndex = '';
            for ($i=0, $iCount = count(value: $srcKeys); $i < $iCount; $i++) {
                if ($srcKeys[$i] === '') {
                    continue;
                }
                $srcIndex .= '[\''.$srcKeys[$i].'\']';
            }
            eval('$destFileIndex = $this->fileIndex'.$srcIndex.';');
            if (isset($countObjects)) {
                $destFileIndex['_C_'] = $countObjects;
            }
            eval('unset($this->fileIndex'.$srcIndex.');');

            return true;
        }

        return false;
    }

    /**
     * Copy array from source to dest
     *
     * @param string $srcKey  Source Key values separated by colon
     * @param string $destKey Source Key values separated by colon
     * 
     * @return bool
     */
    public function copy($srcKey, $destKey): bool
    {
        if ($this->isset(keys: $srcKey)) {
            $destKeys = explode(separator: ':', string: $destKey);
            $destFileIndex = &$this->fileIndex;
            for ($i=0, $iCount = count(value: $destKeys); $i < $iCount; $i++) {
                if ($destKeys[$i] === '') {
                    continue;
                }
                if ($destKeys[$i] === '_C_') {
                    $destKeys[$i] = $destFileIndex['_C_'];
                }
                if (!isset($destFileIndex[$destKeys[$i]])) {
                    $destFileIndex[$destKeys[$i]] = [
                        '_C_' => 0
                    ];
                    if (ctype_digit(text: (string)$destKeys[$i])) {
                        $destFileIndex['_C_']++;

                    }
                }
                $destFileIndex = &$destFileIndex[$destKeys[$i]];
            }
            if (ctype_digit(text: (string)$destKeys[--$i]) && $i !== ($iCount-1)) {
                $destFileIndex['_C_']++;
            }
            if (isset($destFileIndex['_C_'])) {
                $countObjects = $destFileIndex['_C_'];
            }

            $srcKeys = explode(separator: ':', string: $srcKey);
            $srcFileIndex = &$this->fileIndex;
            for ($i=0, $iCount = count(value: $srcKeys); $i < $iCount; $i++) {
                if ($srcKeys[$i] === '') {
                    continue;
                }
                $srcFileIndex = &$srcFileIndex[$srcKeys[$i]];
            }
            $destFileIndex = $srcFileIndex;
            if (isset($countObjects)) {
                $destFileIndex['_C_'] = $countObjects;
            }

            return true;
        }

        return false;
    }
}
