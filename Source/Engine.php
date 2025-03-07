<?php
namespace Source;

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
     * Object - start position
     *
     * @var null|integer
     */
    public $_S_ = null;

    /**
     * Object - end position
     *
     * @var null|integer
     */
    public $_E_ = null;

    /**
     * File end position
     *
     * @var null|integer
     */
    public $fileSize = null;

    /**
     * Decode constructor
     *
     * @param resource $Stream
     * @return void
     */
    public function __construct(&$Stream)
    {
        $this->Stream = &$Stream;
        $stat = fstat($this->Stream);
        $this->fileSize = $stat['size'];
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
        // Point to EOF
        fseek($this->Stream, $this->fileSize, SEEK_SET);

        // Write content
        $str = $this->jsonComma . json_encode($arr);
        fwrite($this->Stream, $str);

        $jsonLength = strlen($str);
        $this->fileSize += $jsonLength;

        // Return wrote content start and end positions
        if (empty($this->jsonComma)) {
            $this->jsonComma = ',';
            return [$this->fileSize - $jsonLength, $this->fileSize-1];
        } else {
            return [$this->fileSize - $jsonLength + 1, $this->fileSize-1];
        }
    }
}
