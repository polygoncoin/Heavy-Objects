<?php
namespace HeavyObjects\Source;

use HeavyObjects\Source\Engine;

class HeavyObject
{
    /**
     * Stream
     *
     * @var null|resource
     */
    private $Stream = null;

    /**
     * Index
     * Contains start and end positions for requested indexes
     *
     * @var null|array
     */
    public $FileIndex = null;

    /**
     * Allowed File length
     *
     * @var integer
     */
    private $MaxFileLength = 4 * 1024 * 1024 * 1024; // 4 GB

    /**
     * Private Engine
     *
     * @var null|Engine
     */
    private $Engine = null;

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
        // Init Decode Engine
        $this->Engine = new Engine($this->Stream);
        $this->indexString();
    }

    /**
     * Index file content
     *
     * @return void
     */
    public function indexString()
    {
        $this->FileIndex = null;
        foreach ($this->Engine->process(true) as $keys => $val) {
            if (
                isset($val['_S_']) &&
                isset($val['_E_'])
            ) {
                $FileIndex = &$this->FileIndex;
                for ($i=0, $iCount = count($keys); $i < $iCount; $i++) {
                    if (!isset($FileIndex[$keys[$i]])) {
                        $FileIndex[$keys[$i]] = [
                            '_C_' => 0
                        ];
                    }
                    if (ctype_digit((string)$keys[$i])) {
                        $FileIndex['_C_']++;
                    }
                    $FileIndex = &$FileIndex[$keys[$i]];
                }
                $FileIndex['_S_'] = $val['_S_'];
                $FileIndex['_E_'] = $val['_E_'];
            }
        }
    }

    /**
     * Store array details.
     *
     * @param array        $array
     * @param string|array $keys Key values seperated by colon
     * @return integer
     */
    public function write(array $array, string|array $keys = '')
    {
        $return = [];
        $keys = is_array($keys) ? $keys : (strlen($keys) === 0 ? [] : explode(':', $keys));
        foreach ($array as $key => &$value) {
            if ($key === '') continue;
            if (is_array($value)) {
                $keysArr = $keys;
                $keysArr[] = $key;
                $this->write($value, $keysArr);
            } else {
                $return[$key] = $value;
            }
        }
        if (count($return) > 0) {
            if ($readArr = $this->read(implode(':', $keys))) {
                if ($readArr === $return) return;
            }
            $write = [implode(':', $keys) => $return];
            list($_S_, $_E_) = $this->Engine->encode($write);
            // Update index.
            $FileIndex = &$this->FileIndex;
            for ($i=0, $iCount = count($keys); $i < $iCount; $i++) {
                if ($keys[$i] === '') {
                    continue;
                }
                if (!isset($FileIndex[$keys[$i]])) {
                    $FileIndex[$keys[$i]] = [
                        '_C_' => 0
                    ];
                    if (ctype_digit((string)$keys[$i])) {
                        $FileIndex['_C_']++;
                    }
                }
                $FileIndex = &$FileIndex[$keys[$i]];
            }
            $FileIndex['_S_'] = $_S_;
            $FileIndex['_E_'] = $_E_;
        }
    }

    /**
     * Count of array element
     *
     * @param null|string $keys Key values seperated by colon
     * @return integer
     */
    public function count($keys = null)
    {
        $FileIndex = &$this->FileIndex;
        if (!is_null($keys) && strlen($keys) !== 0) {
            foreach (explode(':', $keys) as $key) {
                if (isset($FileIndex[$key])) {
                    $FileIndex = &$FileIndex[$key];
                } else {
                    die("Invalid key {$key}");
                }
            }
        }
        if (isset($FileIndex['_S_']) && isset($FileIndex['_E_'])) {
            return count($this->get($keys));
        }

        return count($FileIndex) - 1; // minus 1 for _C_
    }

    /**
     * Pass the keys and get whole content belonging to keys
     *
     * @param string $keys Key values seperated by colon
     * @return array
     */
    public function read($keys = null)
    {
        $FileIndex = &$this->FileIndex;
        if (!is_null($keys) && strlen($keys) !== 0) {
            foreach (explode(':', $keys) as $key) {
                if ($key === '') continue;
                if (isset($FileIndex[$key])) {
                    $FileIndex = &$FileIndex[$key];
                } else {
                    return false;
                }
            }
        }

        return $this->data($FileIndex);
    }

    /**
     * Return data for indexes
     *
     * @param array $FileIndex
     * @return array
     */
    public function data(&$FileIndex)
    {
        $return = [];
        if (
            isset($FileIndex['_S_']) &&
            isset($FileIndex['_E_'])
        ) {
            $this->Engine->_S_ = $FileIndex['_S_'];
            $this->Engine->_E_ = $FileIndex['_E_'];
            $json = json_decode($this->Engine->getObjectString(), true);
            $return = $json[key($json)];
        } else {
            foreach ($FileIndex as $key => &$fIndex) {
                if ($key === '') continue;
                if (
                    isset($fIndex['_S_']) &&
                    isset($fIndex['_E_'])
                ) {
                    $this->Engine->_S_ = $fIndex['_S_'];
                    $this->Engine->_E_ = $fIndex['_E_'];
                    $json = json_decode($this->Engine->getObjectString(), true);
                    $return[$key] = $json[key($json)];
                } else {
                    if (is_array($fIndex)) {
                        foreach ($this->data($fIndex) as $k => $v) {
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
}
