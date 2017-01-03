<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Http;

use Slim\Http\Stream;

class StreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var resource pipe stream file handle
     */
    private $pipeFh;

    /**
     * @var Stream
     */
    private $pipeStream;

    public function tearDown()
    {
        if ($this->pipeFh != null) {
            stream_get_contents($this->pipeFh); // prevent broken pipe error message
        }
    }

    /**
     * @covers Slim\Http\Stream::isPipe
     */
    public function testIsPipe()
    {
        $this->openPipeStream();

        $this->assertTrue($this->pipeStream->isPipe());

        $this->pipeStream->detach();
        $this->assertFalse($this->pipeStream->isPipe());

        $fhFile = fopen(__FILE__, 'r');
        $fileStream = new Stream($fhFile);
        $this->assertFalse($fileStream->isPipe());
    }

    /**
     * @covers Slim\Http\Stream::isReadable
     */
    public function testIsPipeReadable()
    {
        $this->openPipeStream();

        $this->assertTrue($this->pipeStream->isReadable());
    }

    /**
     * @covers Slim\Http\Stream::isSeekable
     */
    public function testPipeIsNotSeekable()
    {
        $this->openPipeStream();

        $this->assertFalse($this->pipeStream->isSeekable());
    }

    /**
     * @covers Slim\Http\Stream::seek
     * @expectedException \RuntimeException
     */
    public function testCannotSeekPipe()
    {
        $this->openPipeStream();

        $this->pipeStream->seek(0);
    }

    /**
     * @covers Slim\Http\Stream::tell
     * @expectedException \RuntimeException
     */
    public function testCannotTellPipe()
    {
        $this->openPipeStream();

        $this->pipeStream->tell();
    }

    /**
     * @covers Slim\Http\Stream::rewind
     * @expectedException \RuntimeException
     */
    public function testCannotRewindPipe()
    {
        $this->openPipeStream();

        $this->pipeStream->rewind();
    }

    /**
     * @covers Slim\Http\Stream::getSize
     */
    public function testPipeGetSizeYieldsNull()
    {
        $this->openPipeStream();

        $this->assertNull($this->pipeStream->getSize());
    }

    /**
     * @covers Slim\Http\Stream::close
     */
    public function testClosePipe()
    {
        $this->openPipeStream();

        stream_get_contents($this->pipeFh); // prevent broken pipe error message
        $this->pipeStream->close();
        $this->pipeFh = null;

        $this->assertFalse($this->pipeStream->isPipe());
    }

    /**
     * @covers Slim\Http\Stream::__toString
     */
    public function testPipeToString()
    {
        $this->openPipeStream();

        $this->assertSame('', (string) $this->pipeStream);
    }

    /**
     * @covers Slim\Http\Stream::getContents
     */

    public function testPipeGetContents()
    {
        $this->openPipeStream();

        $contents = trim($this->pipeStream->getContents());
        $this->assertSame('12', $contents);
    }

    /**
     * Opens the pipe stream
     *
     * @see StreamTest::pipeStream
     */
    private function openPipeStream()
    {
        $this->pipeFh = popen('echo 12', 'r');
        $this->pipeStream = new Stream($this->pipeFh);
    }
}
