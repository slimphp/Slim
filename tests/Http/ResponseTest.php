<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Tests\Http;

use Slim\Http\Response;
use Slim\Http\Headers;
use Slim\Http\Cookies;
use Slim\Http\Body;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    /*******************************************************************************
     * Create
     ******************************************************************************/

    public function testConstructoWithDefaultArgs()
    {
        $response = new Response();

        $this->assertAttributeEquals(200, 'status', $response);
        $this->assertAttributeInstanceOf('\Slim\Http\Headers', 'headers', $response);
        $this->assertAttributeInstanceOf('\Psr\Http\Message\StreamInterface', 'body', $response);
    }

    public function testConstructorWithCustomArgs()
    {
        $headers = new Headers();
        $body = new Body(fopen('php://temp', 'r+'));
        $response = new Response(404, $headers, $body);

        $this->assertAttributeEquals(404, 'status', $response);
        $this->assertAttributeSame($headers, 'headers', $response);
        $this->assertAttributeSame($body, 'body', $response);
    }

    public function testDeepCopyClone()
    {
        $headers = new Headers();
        $body = new Body(fopen('php://temp', 'r+'));
        $response = new Response(404, $headers, $body);
        $clone = clone $response;

        $this->assertAttributeEquals('1.1', 'protocolVersion', $clone);
        $this->assertAttributeEquals(404, 'status', $clone);
        $this->assertAttributeNotSame($headers, 'headers', $clone);
        $this->assertAttributeNotSame($body, 'body', $clone);
    }

    public function testDisableSetter()
    {
        $response = new Response();
        $response->foo = 'bar';

        $this->assertFalse(property_exists($response, 'foo'));
    }

    /*******************************************************************************
     * Protocol
     ******************************************************************************/

    public function testGetProtocolVersion()
    {
        $response = new Response();
        $responseProto = new \ReflectionProperty($response, 'protocolVersion');
        $responseProto->setAccessible(true);
        $responseProto->setValue($response, '1.0');

        $this->assertEquals('1.0', $response->getProtocolVersion());
    }

    public function testWithProtocolVersion()
    {
        $response = new Response();
        $clone = $response->withProtocolVersion('1.0');

        $this->assertAttributeEquals('1.0', 'protocolVersion', $clone);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithProtocolVersionInvalid()
    {
        $response = new Response();
        $clone = $response->withProtocolVersion('3.0');
    }

    /*******************************************************************************
     * Status
     ******************************************************************************/

    public function testGetStatusCode()
    {
        $response = new Response();
        $responseStatus = new \ReflectionProperty($response, 'status');
        $responseStatus->setAccessible(true);
        $responseStatus->setValue($response, '404');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testWithStatus()
    {
        $response = new Response();
        $clone = $response->withStatus(302);

        $this->assertAttributeEquals(302, 'status', $clone);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithStatusInvalid()
    {
        $response = new Response();
        $clone = $response->withStatus(800);
    }

    public function testGetReasonPhrase()
    {
        $response = new Response();
        $responseStatus = new \ReflectionProperty($response, 'status');
        $responseStatus->setAccessible(true);
        $responseStatus->setValue($response, '404');

        $this->assertEquals('Not Found', $response->getReasonPhrase());
    }

    /*******************************************************************************
     * Headers
     ******************************************************************************/

    public function testGetHeaders()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $headers->add('X-Foo', 'two');
        $headers->add('X-Foo', 'three');
        $response = new Response(200, $headers);
        $shouldBe = [
            'X-Foo' => [
                'one',
                'two',
                'three',
            ],
        ];
        $this->assertEquals($shouldBe, $response->getHeaders());
    }

    public function testHasHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $response = new Response(200, $headers);

        $this->assertTrue($response->hasHeader('X-Foo'));
        $this->assertFalse($response->hasHeader('X-Bar'));
    }

    public function testGetHeaderLine()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $headers->add('X-Foo', 'two');
        $headers->add('X-Foo', 'three');
        $response = new Response(200, $headers);

        $this->assertEquals('one,two,three', $response->getHeaderLine('X-Foo'));
        $this->assertEquals('', $response->getHeaderLine('X-Bar'));
    }

    public function testGetHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $headers->add('X-Foo', 'two');
        $headers->add('X-Foo', 'three');
        $response = new Response(200, $headers);

        $this->assertEquals(['one', 'two', 'three'], $response->getHeader('X-Foo'));
        $this->assertEquals([], $response->getHeader('X-Bar'));
    }

    public function testWithHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $response = new Response(200, $headers);
        $clone = $response->withHeader('X-Foo', 'bar');

        $this->assertEquals('bar', $clone->getHeaderLine('X-Foo'));
    }

    public function testWithAddedHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $response = new Response(200, $headers);
        $clone = $response->withAddedHeader('X-Foo', 'two');

        $this->assertEquals('one,two', $clone->getHeaderLine('X-Foo'));
    }

    public function testWithoutHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $headers->add('X-Bar', 'two');
        $response = new Response(200, $headers);
        $clone = $response->withoutHeader('X-Foo');
        $shouldBe = [
            'X-Bar' => ['two'],
        ];

        $this->assertEquals($shouldBe, $clone->getHeaders());
    }

    /*******************************************************************************
     * Body
     ******************************************************************************/

    public function testGetBody()
    {
        $headers = new Headers();
        $body = new Body(fopen('php://temp', 'r+'));
        $response = new Response(404, $headers, $body);

        $this->assertSame($body, $response->getBody());
    }

    public function testWithBody()
    {
        $headers = new Headers();
        $body = new Body(fopen('php://temp', 'r+'));
        $body2 = new Body(fopen('php://temp', 'r+'));
        $response = new Response(404, $headers, $body);
        $clone = $response->withBody($body2);

        $this->assertAttributeSame($body2, 'body', $clone);
    }

    /*******************************************************************************
     * Behaviors
     ******************************************************************************/

    public function testIsEmpty()
    {
        $response = new Response();
        $prop = new \ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 201);

        $this->assertTrue($response->isEmpty());
    }

    public function testIsInformational()
    {
        $response = new Response();
        $prop = new \ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 100);

        $this->assertTrue($response->isInformational());
    }

    public function testIsOk()
    {
        $response = new Response();
        $prop = new \ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 200);

        $this->assertTrue($response->isOk());
    }

    public function testIsSuccessful()
    {
        $response = new Response();
        $prop = new \ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 201);

        $this->assertTrue($response->isSuccessful());
    }

    public function testIsRedirect()
    {
        $response = new Response();
        $prop = new \ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 302);

        $this->assertTrue($response->isRedirect());
    }

    public function testIsRedirection()
    {
        $response = new Response();
        $prop = new \ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 308);

        $this->assertTrue($response->isRedirection());
    }

    public function testIsForbidden()
    {
        $response = new Response();
        $prop = new \ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 403);

        $this->assertTrue($response->isForbidden());
    }

    public function testIsNotFound()
    {
        $response = new Response();
        $prop = new \ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 404);

        $this->assertTrue($response->isNotFound());
    }

    public function testIsClientError()
    {
        $response = new Response();
        $prop = new \ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 400);

        $this->assertTrue($response->isClientError());
    }

    public function testIsServerError()
    {
        $response = new Response();
        $prop = new \ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 503);

        $this->assertTrue($response->isServerError());
    }

    public function testToString()
    {
        $output = <<<END
HTTP/1.1 404 Not Found
X-Foo: Bar

Where am I?
END;
        $this->expectOutputString($output);
        $response = new Response();
        $response = $response->withStatus(404)->withHeader('X-Foo', 'Bar')->write('Where am I?');

        echo $response;
    }
}
