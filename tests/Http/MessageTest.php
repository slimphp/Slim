<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Http;

use Slim\Http\Headers;
use Slim\Tests\Mocks\MessageStub;

class MessageTest extends \PHPUnit_Framework_TestCase
{
    /*******************************************************************************
     * Protocol
     ******************************************************************************/

    /**
     * @covers Slim\Http\Message::getProtocolVersion
     */
    public function testGetProtocolVersion()
    {
        $message = new MessageStub();
        $message->protocolVersion = '1.0';

        $this->assertEquals('1.0', $message->getProtocolVersion());
    }

    /**
     * @covers Slim\Http\Message::withProtocolVersion
     */
    public function testWithProtocolVersion()
    {
        $message = new MessageStub();
        $clone = $message->withProtocolVersion('1.0');

        $this->assertEquals('1.0', $clone->protocolVersion);
    }

    /**
     * @covers Slim\Http\Message::withProtocolVersion
     * @expectedException \InvalidArgumentException
     */
    public function testWithProtocolVersionInvalidThrowsException()
    {
        $message = new MessageStub();
        $message->withProtocolVersion('3.0');
    }


    /**
     * Ensure that we support HTTP '2'
     *
     * @see https://http2.github.io/faq/#is-it-http20-or-http2
     * @covers Slim\Http\Message::withProtocolVersion
     */
    public function testWithProtocolVersionForHttp2()
    {
        $message = new MessageStub();
        $clone = $message->withProtocolVersion('2');

        $this->assertEquals('2', $clone->protocolVersion);
    }


    /*******************************************************************************
     * Headers
     ******************************************************************************/

    /**
     * @covers Slim\Http\Message::getHeaders
     */
    public function testGetHeaders()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $headers->add('X-Foo', 'two');
        $headers->add('X-Foo', 'three');

        $message = new MessageStub();
        $message->headers = $headers;

        $shouldBe = [
            'X-Foo' => [
                'one',
                'two',
                'three',
            ],
        ];

        $this->assertEquals($shouldBe, $message->getHeaders());
    }

    /**
     * @covers Slim\Http\Message::hasHeader
     */
    public function testHasHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');

        $message = new MessageStub();
        $message->headers = $headers;

        $this->assertTrue($message->hasHeader('X-Foo'));
        $this->assertFalse($message->hasHeader('X-Bar'));
    }

    /**
     * @covers Slim\Http\Message::getHeaderLine
     */
    public function testGetHeaderLine()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $headers->add('X-Foo', 'two');
        $headers->add('X-Foo', 'three');

        $message = new MessageStub();
        $message->headers = $headers;

        $this->assertEquals('one,two,three', $message->getHeaderLine('X-Foo'));
        $this->assertEquals('', $message->getHeaderLine('X-Bar'));
    }

    /**
     * @covers Slim\Http\Message::getHeader
     */
    public function testGetHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $headers->add('X-Foo', 'two');
        $headers->add('X-Foo', 'three');

        $message = new MessageStub();
        $message->headers = $headers;

        $this->assertEquals(['one', 'two', 'three'], $message->getHeader('X-Foo'));
        $this->assertEquals([], $message->getHeader('X-Bar'));
    }

    /**
     * @covers Slim\Http\Message::withHeader
     */
    public function testWithHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $message = new MessageStub();
        $message->headers = $headers;
        $clone = $message->withHeader('X-Foo', 'bar');

        $this->assertEquals('bar', $clone->getHeaderLine('X-Foo'));
    }

    /**
     * @covers Slim\Http\Message::withAddedHeader
     */
    public function testWithAddedHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $message = new MessageStub();
        $message->headers = $headers;
        $clone = $message->withAddedHeader('X-Foo', 'two');

        $this->assertEquals('one,two', $clone->getHeaderLine('X-Foo'));
    }

    /**
     * @covers Slim\Http\Message::withoutHeader
     */
    public function testWithoutHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $headers->add('X-Bar', 'two');
        $response = new MessageStub();
        $response->headers = $headers;
        $clone = $response->withoutHeader('X-Foo');
        $shouldBe = [
            'X-Bar' => ['two'],
        ];

        $this->assertEquals($shouldBe, $clone->getHeaders());
    }

    /*******************************************************************************
     * Body
     ******************************************************************************/

    /**
     * @covers Slim\Http\Message::getBody
     */
    public function testGetBody()
    {
        $body = $this->getBody();
        $message = new MessageStub();
        $message->body = $body;

        $this->assertSame($body, $message->getBody());
    }

    /**
     * @covers Slim\Http\Message::withBody
     */
    public function testWithBody()
    {
        $body = $this->getBody();
        $body2 = $this->getBody();
        $message = new MessageStub();
        $message->body = $body;
        $clone = $message->withBody($body2);

        $this->assertSame($body, $message->body);
        $this->assertSame($body2, $clone->body);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Slim\Http\Body
     */
    protected function getBody()
    {
        return $this->getMockBuilder('Slim\Http\Body')->disableOriginalConstructor()->getMock();
    }
}
