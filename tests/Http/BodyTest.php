<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.5
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
class BodyTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $text = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
    /**
     * @var resource
     */
    protected $stream;

    protected function tearDown()
    {
        if (is_resource($this->stream) === true) {
            fclose($this->stream);
        }
    }

    /**
     * This method creates a new resource, and it seeds
     * the resource with lorem ipsum text. The returned
     * resource is readable, writable, and seekable.
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
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        $bodyStream = new \ReflectionProperty($body, 'stream');
        $bodyStream->setAccessible(true);

        $this->assertSame($this->stream, $bodyStream->getValue($body));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorInvalidStream()
    {
        $this->stream = 'foo';
        $body = new \Slim\Http\Body($this->stream);
    }

    public function testConstructorSetsMetadata()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);

        $bodyMetadata = new \ReflectionProperty($body, 'meta');
        $bodyMetadata->setAccessible(true);

        $this->assertTrue(is_array($bodyMetadata->getValue($body)));
    }

    public function testGetMetadata()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);

        $this->assertTrue(is_array($body->getMetadata()));
    }

    public function testGetMetadataKey()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);

        $this->assertEquals('php://temp', $body->getMetadata('uri'));
    }

    public function testGetMetadataKeyNotFound()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);

        $this->assertNull($body->getMetadata('foo'));
    }

    public function testIsAttachedTrue()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);

        $this->assertTrue($body->isAttached());
    }

    public function testIsAttachedFalse()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        $bodyStream = new \ReflectionProperty($body, 'stream');
        $bodyStream->setAccessible(true);
        $bodyStream->setValue($body, null);

        $this->assertFalse($body->isAttached());
    }

    public function testAttachStream()
    {
        $stream1 = $this->resourceFactory();
        $stream2 = $this->resourceFactory();

        $body = new \Slim\Http\Body($stream1);
        $bodyStream = new \ReflectionProperty($body, 'stream');
        $bodyStream->setAccessible(true);

        $this->assertSame($stream1, $bodyStream->getValue($body));
        $body->attach($stream2);
        $this->assertSame($stream2, $bodyStream->getValue($body));

        fclose($stream1);
        fclose($stream2);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAttachInvalidStream()
    {
        $body = new \Slim\Http\Body($this->resourceFactory());
        $body->attach('Foo');
    }

    public function testDetach()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);

        $bodyStream = new \ReflectionProperty($body, 'stream');
        $bodyStream->setAccessible(true);

        $bodyMetadata = new \ReflectionProperty($body, 'meta');
        $bodyMetadata->setAccessible(true);

        $bodyReadable = new \ReflectionProperty($body, 'readable');
        $bodyReadable->setAccessible(true);

        $bodyWritable = new \ReflectionProperty($body, 'writable');
        $bodyWritable->setAccessible(true);

        $bodySeekable = new \ReflectionProperty($body, 'seekable');
        $bodySeekable->setAccessible(true);

        $result = $body->detach();

        $this->assertSame($this->stream, $result);
        $this->assertNull($bodyStream->getValue($body));
        $this->assertNull($bodyMetadata->getValue($body));
        $this->assertNull($bodyReadable->getValue($body));
        $this->assertNull($bodyWritable->getValue($body));
        $this->assertNull($bodySeekable->getValue($body));
    }

    public function testToStringAttached()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);

        $this->assertEquals($this->text, (string)$body);
    }

    public function testToStringDetached()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        $bodyStream = new \ReflectionProperty($body, 'stream');
        $bodyStream->setAccessible(true);
        $bodyStream->setValue($body, null);

        $this->assertEquals('', (string)$body);
    }

    public function testClose()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        $body->close();

        $this->assertAttributeEquals(null, 'stream', $body);
        $this->assertFalse($body->isAttached());
    }

    public function testGetSizeAttached()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);

        $this->assertEquals(mb_strlen($this->text), $body->getSize());
    }

    public function testGetSizeDetached()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        $bodyStream = new \ReflectionProperty($body, 'stream');
        $bodyStream->setAccessible(true);
        $bodyStream->setValue($body, null);

        $this->assertNull($body->getSize());
    }

    public function testTellAttached()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        fseek($this->stream, 10);

        $this->assertEquals(10, $body->tell());
    }

    public function testTellDetachedThrowsRuntimeException()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        $bodyStream = new \ReflectionProperty($body, 'stream');
        $bodyStream->setAccessible(true);
        $bodyStream->setValue($body, null);

        $this->setExpectedException('\RuntimeException');
        $body->tell();
    }

    public function testEofAttachedFalse()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        fseek($this->stream, 10);

        $this->assertFalse($body->eof());
    }

    public function testEofAttachedTrue()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        while (feof($this->stream) === false) {
            fread($this->stream, 1024);
        }

        $this->assertTrue($body->eof());
    }

    public function testEofDetached()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        $bodyStream = new \ReflectionProperty($body, 'stream');
        $bodyStream->setAccessible(true);
        $bodyStream->setValue($body, null);

        $this->assertTrue($body->eof());
    }

    public function isReadableAttachedTrue()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);

        $this->assertTrue($body->isReadable());
    }

    public function isReadableAttachedFalse()
    {
        $stream = fopen('php://temp', 'w');
        $body = new \Slim\Http\Body($this->stream);

        $this->assertFalse($body->isReadable());
        fclose($stream);
    }

    public function testIsReadableDetached()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        $body->detach();

        $this->assertFalse($body->isReadable());
    }

    public function isWritableAttachedTrue()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);

        $this->assertTrue($body->isWritable());
    }

    public function isWritableAttachedFalse()
    {
        $stream = fopen('php://temp', 'r');
        $body = new \Slim\Http\Body($this->stream);

        $this->assertFalse($body->isWritable());
        fclose($stream);
    }

    public function testIsWritableDetached()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        $body->detach();

        $this->assertFalse($body->isWritable());
    }

    public function isSeekableAttachedTrue()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);

        $this->assertTrue($body->isSeekable());
    }

    // TODO: Is seekable is false when attached... how?

    public function testIsSeekableDetached()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        $body->detach();

        $this->assertFalse($body->isSeekable());
    }

    public function testSeekAttached()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        $body->seek(10);

        $this->assertEquals(10, ftell($this->stream));
    }

    public function testSeekDetachedThrowsRuntimeException()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        $body->detach();

        $this->setExpectedException('\RuntimeException');
        $body->seek(10);
    }

    public function testRewindAttached()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        fseek($this->stream, 10);
        $body->rewind();

        $this->assertEquals(0, ftell($this->stream));
    }

    public function testRewindDetachedThrowsRuntimeException()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        $body->detach();

        $this->setExpectedException('\RuntimeException');
        $body->rewind();
    }

    public function testReadAttached()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);

        $this->assertEquals(substr($this->text, 0, 10), $body->read(10));
    }

    public function testReadDetachedThrowsRuntimeException()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        $body->detach();

        $this->setExpectedException('\RuntimeException');
        $body->read(10);
    }

    public function testWriteAttached()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        while (feof($this->stream) === false) {
            fread($this->stream, 1024);
        }
        $body->write('foo');

        $this->assertEquals($this->text . 'foo', (string)$body);
    }

    public function testWriteDetachedThrowsRuntimeException()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        $body->detach();

        $this->setExpectedException('\RuntimeException');
        $body->write('foo');
    }

    public function testGetContentsAttached()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        fseek($this->stream, 10);

        $this->assertEquals(substr($this->text, 10), $body->getContents());
    }

    public function testGetContentsDetachedThrowsRuntimeException()
    {
        $this->stream = $this->resourceFactory();
        $body = new \Slim\Http\Body($this->stream);
        $body->detach();

        $this->setExpectedException('\RuntimeException');
        $body->getContents();
    }
}
