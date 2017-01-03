<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Http;

use ReflectionProperty;
use Slim\Http\RequestBody;

class RequestBodyTest extends \PHPUnit_Framework_TestCase
{
    /** @var string */
    // @codingStandardsIgnoreStart
    protected $text = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
    // @codingStandardsIgnoreEnd
    /** @var resource */
    protected $stream;
    /** @var RequestBody */
    protected $body;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->body = new RequestBody();
        $this->body->write($this->text);
        $this->body->rewind();
    }

    protected function tearDown()
    {
        if (is_resource($this->stream) === true) {
            fclose($this->stream);
        }
        $this->body = null;
    }

    /**
     * This method creates a new resource, and it seeds
     * the resource with lorem ipsum text. The returned
     * resource is readable, writable, and seekable.
     *
     * @param string $mode
     *
     * @return resource
     */
    public function resourceFactory($mode = 'r+')
    {
        $stream = fopen('php://temp', $mode);
        fwrite($stream, $this->text);
        rewind($stream);

        return $stream;
    }

    public function testConstructorAttachesStream()
    {
        $bodyStream = new ReflectionProperty($this->body, 'stream');
        $bodyStream->setAccessible(true);

        $this->assertInternalType('resource', $bodyStream->getValue($this->body));
    }

    public function testConstructorSetsMetadata()
    {
        $bodyMetadata = new ReflectionProperty($this->body, 'meta');
        $bodyMetadata->setAccessible(true);

        $this->assertTrue(is_array($bodyMetadata->getValue($this->body)));
    }

    public function testGetMetadata()
    {
        $this->assertTrue(is_array($this->body->getMetadata()));
    }

    public function testGetMetadataKey()
    {
        $this->assertEquals('php://temp', $this->body->getMetadata('uri'));
    }

    public function testGetMetadataKeyNotFound()
    {
        $this->assertNull($this->body->getMetadata('foo'));
    }

    public function testDetach()
    {
        $bodyStream = new ReflectionProperty($this->body, 'stream');
        $bodyStream->setAccessible(true);

        $bodyMetadata = new ReflectionProperty($this->body, 'meta');
        $bodyMetadata->setAccessible(true);

        $bodyReadable = new ReflectionProperty($this->body, 'readable');
        $bodyReadable->setAccessible(true);

        $bodyWritable = new ReflectionProperty($this->body, 'writable');
        $bodyWritable->setAccessible(true);

        $bodySeekable = new ReflectionProperty($this->body, 'seekable');
        $bodySeekable->setAccessible(true);

        $result = $this->body->detach();

        $this->assertInternalType('resource', $result);
        $this->assertNull($bodyStream->getValue($this->body));
        $this->assertNull($bodyMetadata->getValue($this->body));
        $this->assertNull($bodyReadable->getValue($this->body));
        $this->assertNull($bodyWritable->getValue($this->body));
        $this->assertNull($bodySeekable->getValue($this->body));
    }

    public function testToStringAttached()
    {
        $this->assertEquals($this->text, (string)$this->body);
    }

    public function testToStringAttachedRewindsFirst()
    {
        $this->assertEquals($this->text, (string)$this->body);
        $this->assertEquals($this->text, (string)$this->body);
        $this->assertEquals($this->text, (string)$this->body);
    }

    public function testToStringDetached()
    {
        $bodyStream = new ReflectionProperty($this->body, 'stream');
        $bodyStream->setAccessible(true);
        $bodyStream->setValue($this->body, null);

        $this->assertEquals('', (string)$this->body);
    }

    public function testClose()
    {
        $this->body->close();

        $this->assertAttributeEquals(null, 'stream', $this->body);
        $this->assertFalse($this->body->isReadable());
        $this->assertFalse($this->body->isWritable());
        $this->assertEquals('', (string)$this->body);

        $this->setExpectedException('RuntimeException');
        $this->body->tell();
    }

    public function testGetSizeAttached()
    {
        $this->assertEquals(mb_strlen($this->text), $this->body->getSize());
    }

    public function testGetSizeDetached()
    {
        $bodyStream = new ReflectionProperty($this->body, 'stream');
        $bodyStream->setAccessible(true);
        $bodyStream->setValue($this->body, null);

        $this->assertNull($this->body->getSize());
    }

    public function testTellAttached()
    {
        $this->body->seek(10);

        $this->assertEquals(10, $this->body->tell());
    }

    public function testTellDetachedThrowsRuntimeException()
    {
        $bodyStream = new ReflectionProperty($this->body, 'stream');
        $bodyStream->setAccessible(true);
        $bodyStream->setValue($this->body, null);

        $this->setExpectedException('\RuntimeException');
        $this->body->tell();
    }

    public function testEofAttachedFalse()
    {
        $this->body->seek(10);

        $this->assertFalse($this->body->eof());
    }

    public function testEofAttachedTrue()
    {
        while ($this->body->eof() === false) {
            $this->body->read(1024);
        }

        $this->assertTrue($this->body->eof());
    }

    public function testEofDetached()
    {
        $bodyStream = new ReflectionProperty($this->body, 'stream');
        $bodyStream->setAccessible(true);
        $bodyStream->setValue($this->body, null);

        $this->assertTrue($this->body->eof());
    }

    public function testIsReadableAttachedTrue()
    {
        $this->assertTrue($this->body->isReadable());
    }

    public function testIsReadableDetached()
    {
        $this->body->detach();

        $this->assertFalse($this->body->isReadable());
    }

    public function testIsWritableAttachedTrue()
    {
        $this->assertTrue($this->body->isWritable());
    }

    public function testIsWritableDetached()
    {
        $this->body->detach();

        $this->assertFalse($this->body->isWritable());
    }

    public function isSeekableAttachedTrue()
    {
        $this->assertTrue($this->body->isSeekable());
    }

    // TODO: Is seekable is false when attached... how?

    public function testIsSeekableDetached()
    {
        $this->body->detach();

        $this->assertFalse($this->body->isSeekable());
    }

    public function testSeekAttached()
    {
        $this->body->seek(10);

        $this->assertEquals(10, $this->body->tell());
    }

    public function testSeekDetachedThrowsRuntimeException()
    {
        $this->body->detach();

        $this->setExpectedException('\RuntimeException');
        $this->body->seek(10);
    }

    public function testRewindAttached()
    {
        $this->body->seek(10);
        $this->body->rewind();

        $this->assertEquals(0, $this->body->tell());
    }

    public function testRewindDetachedThrowsRuntimeException()
    {
        $this->body->detach();

        $this->setExpectedException('\RuntimeException');
        $this->body->rewind();
    }

    public function testReadAttached()
    {
        $this->assertEquals(substr($this->text, 0, 10), $this->body->read(10));
    }

    public function testReadDetachedThrowsRuntimeException()
    {
        $this->body->detach();

        $this->setExpectedException('\RuntimeException');
        $this->body->read(10);
    }

    public function testWriteAttached()
    {
        while ($this->body->eof() === false) {
            $this->body->read(1024);
        }
        $this->body->write('foo');

        $this->assertEquals($this->text . 'foo', (string)$this->body);
    }

    public function testWriteDetachedThrowsRuntimeException()
    {
        $this->body->detach();

        $this->setExpectedException('\RuntimeException');
        $this->body->write('foo');
    }

    public function testGetContentsAttached()
    {
        $this->body->seek(10);

        $this->assertEquals(substr($this->text, 10), $this->body->getContents());
    }

    public function testGetContentsDetachedThrowsRuntimeException()
    {
        $this->body->detach();

        $this->setExpectedException('\RuntimeException');
        $this->body->getContents();
    }
}
