<?php
namespace HeavyObjects\Source;

use HeavyObjects\Source\DecodeEngine;

class Decode
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
     * Private DecodeEngine
     *
     * @var null|DecodeEngine
     */
    private $DecodeEngine = null;

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
        $this->DecodeEngine = new DecodeEngine();
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
        foreach ($this->DecodeEngine->process(true) as $keys => $val) {
            if (
                isset($val['_S_']) &&
                isset($val['_E_'])
            ) {
                $FileIndex = &$this->FileIndex;
                for ($i=0, $iCount = count($keys); $i < $iCount; $i++) {
                    if (is_numeric($keys[$i]) && !isset($FileIndex[$keys[$i]])) {
                        $FileIndex[$keys[$i]] = [];
                        if (!isset($FileIndex['_C_'])) {
                            $FileIndex['_C_'] = 0;
                        }
                        if (is_numeric($keys[$i])) {
                            $FileIndex['_C_']++;
                        }
                    }
                    $FileIndex = &$FileIndex[$keys[$i]];
                }
                $FileIndex['_S_'] = $val['_S_'];
                $FileIndex['_E_'] = $val['_E_'];
            }
        }
    }

    /**
     * Keys exist
     *
     * @param null|string $keys Keys exist (values seperated by colon)
     * @return boolean
     */
    public function isset($keys = null)
    {
        $return = true;
        $FileIndex = &$this->FileIndex;
        if (!is_null($keys) && strlen($keys) !== 0) {
            foreach (explode(':', $keys) as $key) {
                if (isset($FileIndex[$key])) {
                    $FileIndex = &$FileIndex[$key];
                } else {
                    $return = false;
                    break;
                }
            }
        }
        return $return;
    }

    /**
     * Key exist
     *
     * @param null|string $keys Key values seperated by colon
     * @return string
     */
    public function stringType($keys = null)
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
        $return = 'Object';
        if (
            (
                isset($FileIndex['_S_']) &&
                isset($FileIndex['_E_']) &&
                isset($FileIndex['_C_'])
            )
        ) {
            $return = 'Array';
        }
        return $return;
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
        if (
            !(
                isset($FileIndex['_S_']) &&
                isset($FileIndex['_E_']) &&
                isset($FileIndex['_C_'])
            )
        ) {
            return 0;
        }
        return $FileIndex['_C_'];
    }

    /**
     * Pass the keys and get whole content belonging to keys
     *
     * @param string $keys Key values seperated by colon
     * @return array
     */
    public function get($keys = '')
    {
        if (!$this->isset($keys)) {
            return false;
        }
        $valueArr = [];
        $this->load($keys);
        foreach ($this->DecodeEngine->process() as $keyArr => $valueArr) {
            break;
        }
        return $valueArr;
    }

    /**
     * Get complete for Kays
     *
     * @param string $keys Key values seperated by colon
     * @return array
     */
    public function getCompleteArray($keys = '')
    {
        if (!$this->isset($keys)) {
            return false;
        }
        $this->load($keys);
        return json_decode($this->DecodeEngine->getObjectString(), true);
    }

    /**
     * Start processing the string for the keys
     * Perform search inside keys like $file['data'][0]['data1']
     *
     * @param string $keys Key values seperated by colon.
     * @return void
     */
    public function load($keys)
    {
        if (empty($keys) && $keys != 0) {
            $this->DecodeEngine->_S_ = null;
            $this->DecodeEngine->_E_ = null;
            return;
        }
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
        if (
            isset($FileIndex['_S_']) &&
            isset($FileIndex['_E_'])
        ) {
            $this->DecodeEngine->_S_ = $FileIndex['_S_'];
            $this->DecodeEngine->_E_ = $FileIndex['_E_'];
        } else {
            throw new \Exception("Invalid keys '{$keys}'", 400);
        }
    }
}
