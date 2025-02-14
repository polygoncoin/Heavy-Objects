<?php
namespace HeavyObjects\Source;

class Engine
{
    /**
     * Stream
     *
     * @var null|resource
     */
    private $Stream = null;

    /**
     * Comma
     *
     * @var string
     */
    private $jsonComma = '';

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
     * Write content
     *
     * @param array $arr
     * @return void
     */
    public function write($arr)
    {
        $str = json_encode($arr);
        $jsonLength = strlen($str);

        // Point to EOF
        $stat = fstat($this->Stream);
        fseek($this->Stream, $stat['size'], SEEK_SET);

        // Write content
        fwrite($this->Stream, $this->jsonComma . $str);

        // JSON Comma
        $this->jsonComma = ',';

        // Return wrote content start and end positions
        $stat = fstat($this->Stream);
        return [$stat['size']-$jsonLength, $stat['size']-1];
    }
}
