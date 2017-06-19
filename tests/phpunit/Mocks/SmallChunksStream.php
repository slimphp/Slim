<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Mocks;

use Psr\Http\Message\StreamInterface;

/**
 * A mock stream interface that yields small chunks when reading
 */
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

    public function __toString()
    {
        throw new \Exception('not implemented');
    }

    public function close()
    {
    }

    public function detach()
    {
        throw new \Exception('not implemented');
    }

    public function eof()
    {
        return $this->amountToRead === 0;
    }

    public function getContents()
    {
        throw new \Exception('not implemented');
    }

    public function getMetadata($key = null)
    {
        throw new \Exception('not implemented');
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

    public function rewind()
    {
        throw new \Exception('not implemented');
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        throw new \Exception('not implemented');
    }

    public function tell()
    {
        throw new \Exception('not implemented');
    }

    public function write($string)
    {
        return $string;
    }
}
