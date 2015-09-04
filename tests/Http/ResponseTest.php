<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Http;

use ReflectionProperty;
use Slim\Http\Body;
use Slim\Http\Headers;
use Slim\Http\Response;

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
     * Status
     ******************************************************************************/

    public function testGetStatusCode()
    {
        $response = new Response();
        $responseStatus = new ReflectionProperty($response, 'status');
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
    public function testWithStatusInvalidStatusCodeThrowsException()
    {
        $response = new Response();
        $response->withStatus(800);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithStatusInvalidReasonPhraseThrowsException()
    {
        $response = new Response();
        $response->withStatus(200, null);
    }

    public function testGetReasonPhrase()
    {
        $response = new Response();
        $responseStatus = new ReflectionProperty($response, 'status');
        $responseStatus->setAccessible(true);
        $responseStatus->setValue($response, '404');

        $this->assertEquals('Not Found', $response->getReasonPhrase());
    }

    public function testGetCustomReasonPhrase()
    {
        $response = new Response();
        $clone = $response->withStatus(200, 'Custom Phrase');

        $this->assertEquals('Custom Phrase', $clone->getReasonPhrase());
    }

    /**
     * @covers Slim\Http\Response::withRedirect
     */
    public function testWithRedirect()
    {
        $response = new Response(200);
        $clone = $response->withRedirect('/foo', 301);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('Location'));

        $this->assertSame(301, $clone->getStatusCode());
        $this->assertTrue($clone->hasHeader('Location'));
        $this->assertEquals('/foo', $clone->getHeaderLine('Location'));
    }

    /*******************************************************************************
     * Behaviors
     ******************************************************************************/

    public function testIsEmpty()
    {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 204);

        $this->assertTrue($response->isEmpty());
    }

    public function testIsInformational()
    {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 100);

        $this->assertTrue($response->isInformational());
    }

    public function testIsOk()
    {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 200);

        $this->assertTrue($response->isOk());
    }

    public function testIsSuccessful()
    {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 201);

        $this->assertTrue($response->isSuccessful());
    }

    public function testIsRedirect()
    {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 302);

        $this->assertTrue($response->isRedirect());
    }

    public function testIsRedirection()
    {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 308);

        $this->assertTrue($response->isRedirection());
    }

    public function testIsForbidden()
    {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 403);

        $this->assertTrue($response->isForbidden());
    }

    public function testIsNotFound()
    {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 404);

        $this->assertTrue($response->isNotFound());
    }

    public function testIsClientError()
    {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 400);

        $this->assertTrue($response->isClientError());
    }

    public function testIsServerError()
    {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setAccessible(true);
        $prop->setValue($response, 503);

        $this->assertTrue($response->isServerError());
    }

    public function testToString()
    {
        $output = 'HTTP/1.1 404 Not Found' . PHP_EOL .
                  'X-Foo: Bar' . PHP_EOL . PHP_EOL .
                  'Where am I?';
        $this->expectOutputString($output);
        $response = new Response();
        $response = $response->withStatus(404)->withHeader('X-Foo', 'Bar')->write('Where am I?');

        echo $response;
    }

    public function testWithJson()
    {
        $data = ['foo' => 'bar1&bar2'];

        $response = new Response();
        $response = $response->withJson($data, 201);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('application/json;charset=utf-8', $response->getHeaderLine('Content-Type'));

        $body = $response->getBody();
        $body->rewind();
        $dataJson = $body->getContents(); //json_decode($body->getContents(), true);

        $this->assertEquals('{"foo":"bar1&bar2"}', $dataJson);
        $this->assertEquals($data['foo'], json_decode($dataJson, true)['foo']);

        // Test encoding option
        $response = $response->withJson($data, 200, JSON_HEX_AMP);

        $body = $response->getBody();
        $body->rewind();
        $dataJson = $body->getContents();

        $this->assertEquals('{"foo":"bar1\u0026bar2"}', $dataJson);
        $this->assertEquals($data['foo'], json_decode($dataJson, true)['foo']);
    }
}
