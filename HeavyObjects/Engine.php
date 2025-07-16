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
 * @link      https://github.com/polygoncoin/Heavy-Objects
 * @since     Class available since Release 1.0.0
 */
namespace HeavyObjects;

/**
 * Heavy Object Engine
 * php version 7
 *
 * @category  HeavyObject
 * @package   HeavyObjects
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Heavy-Objects
 * @since     Class available since Release 1.0.0
 */
class Engine
{
    /**
     * Stream
     *
     * @var null|resource
     */
    private $_stream = null;

    /**
     * Comma
     *
     * @var string
     */
    private $_jsonComma = '';

    /**
     * Object - Start index
     *
     * @var null|integer
     */
    public $startIndex = null;

    /**
     * Object - End index
     *
     * @var null|integer
     */
    public $endIndex = null;

    /**
     * File end position
     *
     * @var null|integer
     */
    public $fileSize = null;

    /**
     * Decode constructor
     *
     * @param resource $stream File stream
     */
    public function __construct(&$stream)
    {
        $this->_stream = &$stream;
        $stat = fstat(stream: $this->_stream);
        $this->fileSize = $stat['size'];
    }

    /**
     * Get Object string
     *
     * @return bool|string
     */
    public function getObjectString(): bool|string
    {
        $offset = $this->startIndex !== null ? $this->startIndex : 0;
        $length = $this->endIndex - $offset + 1;

        return stream_get_contents(
            stream: $this->_stream, 
            length: $length, 
            offset: $offset
        );
    }

    /**
     * Write content
     *
     * @param array $arr Array data
     * 
     * @return array
     */
    public function write($arr): array
    {
        // Point to EOF
        fseek(stream: $this->_stream, offset: $this->fileSize, whence: SEEK_SET);

        // Write content
        $str = $this->_jsonComma . json_encode(value: $arr);
        fwrite(stream: $this->_stream, data: $str);

        $jsonLength = strlen(string: $str);
        $this->fileSize += $jsonLength;

        // Return wrote content start and end positions
        if (empty($this->_jsonComma)) {
            $this->_jsonComma = ',';
            return [$this->fileSize - $jsonLength, $this->fileSize-1];
        } else {
            return [$this->fileSize - $jsonLength + 1, $this->fileSize-1];
        }
    }
}
