<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Mocks;

use Exception;
use Psr\Http\Message\StreamInterface;

class SmallChunksStream implements StreamInterface
{
    const CHUNK_SIZE = 10;
    const SIZE = 40;

    /**
     * @var int
     */
    private $amountToRead;

    public function __construct()
    {
        $this->amountToRead = self::SIZE;
    }

    /**
     * @throws Exception
     */
    public function __toString()
    {
        throw new Exception('not implemented');
    }

    public function close()
    {
    }

    /**
     * @throws Exception
     */
    public function detach()
    {
        throw new Exception('not implemented');
    }

    public function eof()
    {
        return $this->amountToRead === 0;
    }

    /**
     * @throws Exception
     */
    public function getContents()
    {
        throw new Exception('not implemented');
    }

    /**
     * @param string $key
     *
     * @throws Exception
     */
    public function getMetadata($key = null)
    {
        throw new Exception('not implemented');
    }

    public function getSize()
    {
        return self::SIZE;
    }

    public function isReadable()
    {
        return true;
    }

    public function isSeekable()
    {
        return false;
    }

    public function isWritable()
    {
        return false;
    }

    public function read($length)
    {
        $size = min($this->amountToRead, self::CHUNK_SIZE, $length);
        $this->amountToRead -= $size;

        return str_repeat('.', min($length, $size));
    }

    /**
     * @throws Exception
     */
    public function rewind()
    {
        throw new Exception('not implemented');
    }

    /**
     * @throws Exception
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        throw new Exception('not implemented');
    }

    /**
     * @throws Exception
     */
    public function tell()
    {
        throw new Exception('not implemented');
    }

    public function write($string)
    {
        return strlen($string);
    }
}
