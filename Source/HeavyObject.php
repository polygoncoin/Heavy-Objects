<?php
namespace Source;

use Source\Engine;

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
        $this->Engine = new Engine($this->Stream);
    }

    /**
     * Check if key exist
     *
     * @param string|null $keys Key values seperated by colon
     * @return array|bool
     */
    public function isset($keys = null)
    {
        $data = false;
        $FileIndex = &$this->FileIndex;
        if (!is_null($keys) && strlen($keys) !== 0) {
            foreach (explode(':', $keys) as $key) {
                if ($key === '') continue;
                if ($data === false && isset($FileIndex[$key])) {
                    $FileIndex = &$FileIndex[$key];
                } else {
                    $data = $this->data($FileIndex);
                    if (isset($data[$key])) {
                        return true;
                    } else {
                        throw new \Exception('Invalid key');
                    }
                    break;
                }
            }
        }

        return true;
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
                if ($key === '') continue;
                if (isset($FileIndex[$key])) {
                    $FileIndex = &$FileIndex[$key];
                } else {
                    throw new \Exception('Invalid key');
                }
            }
        }

        if (isset($FileIndex['_C_'])) {
            return $FileIndex['_C_'];
        }
    }

    /**
     * Pass the keys and get whole content belonging to keys
     *
     * @param string $keys Key values seperated by colon
     * @return array
     */
    public function read($keys = null)
    {
        $data = false;
        $FileIndex = &$this->FileIndex;
        if (!is_null($keys) && strlen($keys) !== 0) {
            foreach (explode(':', $keys) as $key) {
                if ($key === '') continue;
                if ($data === false && isset($FileIndex[$key])) {
                    $FileIndex = &$FileIndex[$key];
                } else {
                    $data = $this->data($FileIndex);
                    if (isset($data[$key])) {
                        return $data[$key];
                    } else {
                        throw new \Exception('Invalid key');
                    }
                    break;
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
        if (is_array($FileIndex)) {
            if (
                isset($FileIndex['_S_']) &&
                isset($FileIndex['_E_'])
            ) {
                $this->Engine->_S_ = $FileIndex['_S_'];
                $this->Engine->_E_ = $FileIndex['_E_'];
                $return = json_decode($this->Engine->getObjectString(), true);
            }
            foreach ($FileIndex as $key => &$fIndex) {
                if (in_array($key, ['', '_S_','_E_','_C_'])) continue;
                if (
                    isset($fIndex['_S_']) &&
                    isset($fIndex['_E_'])
                ) {
                    $this->Engine->_S_ = $fIndex['_S_'];
                    $this->Engine->_E_ = $fIndex['_E_'];
                    $return[$key] = json_decode($this->Engine->getObjectString(), true);
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
        $_C_ = 0;
        foreach ($array as $key => &$value) {
            if ($key === '') continue;
            if (is_array($value)) {
                $keysArr = $keys;
                $keysArr[] = $key;
                $this->write($value, $keysArr);
            } else {
                $return[$key] = $value;
                if (ctype_digit((string)$key)) {
                    $_C_ = $key;
                }
            }
        }
        if (count($return) > 0) {
            try {
                if ($readArr = $this->read(implode(':', $keys))) {
                    if ($readArr === $return) return;
                }
            } catch (\Exception $e) {}
            list($_S_, $_E_) = $this->Engine->write($return);
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
            $FileIndex['_S_'] = (int)$_S_;
            $FileIndex['_E_'] = (int)$_E_;
            $FileIndex['_C_'] = (int)$_C_;
        }
    }
}
