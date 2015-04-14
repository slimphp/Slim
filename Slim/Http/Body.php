<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Http;

/**
 * Body
 *
 * This class represents an HTTP message body and encapsulates a
 * streamable resource according to the PSR-7 standard.
 *
 * @link https://github.com/php-fig/http-message/blob/master/src/StreamInterface.php
 */
class Body implements \Psr\Http\Message\StreamInterface
{
    /**
     * Resource modes
     *
     * @var  array
     * @link http://php.net/manual/function.fopen.php
     */
    protected static $modes = [
        'readable' => ['r', 'r+', 'w+', 'a+', 'x+', 'c+'],
        'writable' => ['r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'],
    ];

    /**
     * The underlying stream resource
     *
     * @var resource
     */
    protected $stream;

    /**
     * Stream metadata
     *
     * @var array
     */
    protected $meta;

    /**
     * Is this stream readable?
     *
     * @var bool
     */
    protected $readable;

    /**
     * Is this stream writable?
     *
     * @var bool
     */
    protected $writable;

    /**
     * Is this stream seekable?
     *
     * @var bool
     */
    protected $seekable;

    /**
     * The size of the stream if known
     *
     * @var null|int
     */
    protected $size;

    /**
     * Create a new HTTP message body
     *
     * @param  resource                  $stream A PHP resource handle
     * @throws \InvalidArgumentException If argument is not a resource
     */
    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('\Slim\Http\Body::__construct() argument must be a valid PHP resource');
        }
        $this->attach($stream);
    }

    /**
     * Set HTTP message metadata
     *
     * @param  resource                  $stream
     * @throws \InvalidArgumentException If argument is not a resource
     */
    protected function setMetadata($stream)
    {
        // Fetch metadata
        $this->meta = stream_get_meta_data($stream);

        // Is readable?
        $this->readable = false;
        foreach (self::$modes['readable'] as $mode) {
            if (strpos($this->meta['mode'], $mode) === 0) {
                $this->readable = true;
                break;
            }
        }

        // Is writable?
        $this->writable = false;
        foreach (self::$modes['writable'] as $mode) {
            if (strpos($this->meta['mode'], $mode) === 0) {
                $this->writable = true;
                break;
            }
        }

        // Is seekable?
        $this->seekable = $this->meta['seekable'];
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @param  string           $key The metadata property name
     * @return array|null|mixed Returns array if key not provided;
     *                          Returns mixed if valid key provided;
     *                          Returns null if invalid key provided;
     * @link   http://php.net/manual/function.stream-get-meta-data.php
     */
    public function getMetadata($key = null)
    {
        if (is_null($key) === true) {
            return $this->meta;
        }

        return isset($this->meta[$key]) ? $this->meta[$key] : null;
    }

    /**
     * Is a resource attached to this HTTP message body?
     *
     * @return bool
     */
    public function isAttached()
    {
        return is_resource($this->stream);
    }

    /**
     * Attach new resource to this HTTP message body
     *
     * @param  resource                  $newStream A PHP resource handle
     * @throws \InvalidArgumentException If argument is not a valid PHP resource
     */
    public function attach($newStream)
    {
        if (is_resource($newStream) === false) {
            throw new \InvalidArgumentException('\Slim\Http\Body::attach() argument must be a valid PHP resource');
        }

        if ($this->isAttached() === true) {
            $this->detach();
        }

        $this->stream = $newStream;
        $this->setMetadata($newStream);
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $oldResource = $this->stream;
        $this->stream = null;
        $this->meta = null;
        $this->readable = null;
        $this->writable = null;
        $this->seekable = null;
        $this->size = null;

        return $oldResource;
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->isAttached() ? stream_get_contents($this->stream, -1, 0) : '';
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        if ($this->isAttached() === true) {
            fclose($this->stream);
        }

        $this->detach();
    }

    /**
     * Get the size of the stream if known
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if (!$this->size && $this->isAttached() === true) {
            $stats = fstat($this->stream);
            $this->size = isset($stats['size']) ? $stats['size'] : null;
        }

        return $this->size;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int|bool Position of the file pointer or false on error.
     */
    public function tell()
    {
        return $this->isAttached() ? ftell($this->stream) : false;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        return $this->isAttached() ? feof($this->stream) : true;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        return is_null($this->readable) ? false : $this->readable;
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        return is_null($this->writable) ? false : $this->writable;
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        return is_null($this->seekable) ? false : $this->seekable;
    }

    /**
     * Seek to a position in the stream.
     *
     * @link  http://www.php.net/manual/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *                    based on the seek offset. Valid values are identical
     *                    to the built-in PHP $whence values for `fseek()`.
     *                    - SEEK_SET: Set position equal to offset bytes;
     *                    - SEEK_CUR: Set position to current location plus offset;
     *                    - SEEK_END: Set position to end-of-stream plus offset.
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        return $this->isAttached() && $this->isSeekable() ? fseek($this->stream, $offset, $whence) : false;
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will return FALSE, indicating
     * failure; otherwise, it will perform a seek(0), and return the status of
     * that operation.
     *
     * @see    seek()
     * @link   http://www.php.net/manual/function.fseek.php
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function rewind()
    {
        return $this->isAttached() && $this->isSeekable() ? rewind($this->stream) : false;
    }

    /**
     * Read data from the stream.
     *
     * @param int           $length Read up to $length bytes from the object and return
     *                              them. Fewer than $length bytes may be returned if underlying
     *                              stream call returns fewer bytes.
     * @return string|false         Returns the data read from the stream, false if
     *                              unable to read or if an error occurs.
     */
    public function read($length)
    {
        return $this->isAttached() && $this->isReadable() ? fread($this->stream, $length) : false;
    }

    /**
     * Write data to the stream.
     *
     * @param  string   $string The string that is to be written.
     * @return int|bool         Returns the number of bytes written to the stream on
     *                          success or FALSE on failure.
     */
    public function write($string)
    {
        return $this->isAttached() && $this->isWritable() ? fwrite($this->stream, $string) : false;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     */
    public function getContents()
    {
        return $this->isAttached() && $this->isReadable() ? stream_get_contents($this->stream) : '';
    }
}
