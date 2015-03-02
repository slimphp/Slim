<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */

use \Slim\Http\Response;
use \Slim\Http\Headers;
use \Slim\Http\Cookies;
use \Slim\Http\Body;

class ResponseTest extends PHPUnit_Framework_TestCase
{
    /*******************************************************************************
     * Create
     ******************************************************************************/

    public function testConstructoWithDefaultArgs()
    {
        $response = new Response();

        $this->assertAttributeEquals(200, 'status', $response);
        $this->assertAttributeInstanceOf('\Slim\Http\Headers', 'headers', $response);
        $this->assertAttributeInstanceOf('\Slim\Http\Cookies', 'cookies', $response);
        $this->assertAttributeInstanceOf('\Psr\Http\Message\StreamableInterface', 'body', $response);
    }

    public function testConstructorWithCustomArgs()
    {
        $headers = new Headers();
        $cookies = new Cookies();
        $body = new Body(fopen('php://temp', 'r+'));
        $response = new Response(404, $headers, $cookies, $body);

        $this->assertAttributeEquals(404, 'status', $response);
        $this->assertAttributeSame($headers, 'headers', $response);
        $this->assertAttributeSame($cookies, 'cookies', $response);
        $this->assertAttributeSame($body, 'body', $response);
    }

    public function testDeepCopyClone()
    {
        $headers = new Headers();
        $cookies = new Cookies();
        $body = new Body(fopen('php://temp', 'r+'));
        $response = new Response(404, $headers, $cookies, $body);
        $clone = clone $response;

        $this->assertAttributeEquals('1.1', 'protocolVersion', $clone);
        $this->assertAttributeEquals(404, 'status', $clone);
        $this->assertAttributeNotSame($headers, 'headers', $clone);
        $this->assertAttributeNotSame($cookies, 'cookies', $clone);
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
                'three'
            ]
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

    public function testGetHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $headers->add('X-Foo', 'two');
        $headers->add('X-Foo', 'three');
        $response = new Response(200, $headers);

        $this->assertEquals('one,two,three', $response->getHeader('X-Foo'));
        $this->assertEquals('', $response->getHeader('X-Bar'));
    }

    public function testGetHeaderLines()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $headers->add('X-Foo', 'two');
        $headers->add('X-Foo', 'three');
        $response = new Response(200, $headers);

        $this->assertEquals(['one', 'two', 'three'], $response->getHeaderLines('X-Foo'));
        $this->assertEquals([], $response->getHeaderLines('X-Bar'));
    }

    public function testWithHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $response = new Response(200, $headers);
        $clone = $response->withHeader('X-Foo', 'bar');

        $this->assertEquals('bar', $clone->getHeader('X-Foo'));
    }

    public function testWithAddedHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $response = new Response(200, $headers);
        $clone = $response->withAddedHeader('X-Foo', 'two');

        $this->assertEquals('one,two', $clone->getHeader('X-Foo'));
    }

    public function testWithoutHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $headers->add('X-Bar', 'two');
        $response = new Response(200, $headers);
        $clone = $response->withoutHeader('X-Foo');
        $shouldBe = [
            'X-Bar' => ['two']
        ];

        $this->assertEquals($shouldBe, $clone->getHeaders());
    }

    /*******************************************************************************
     * Cookies
     ******************************************************************************/

    public function testGetCookies()
    {
        $cookies = new Cookies();
        $cookies->setDefaults([
            'expires' => '20 minutes',
            'path' => '/',
            'domain' => 'example.com',
            'secure' => false,
            'httponly' => false
        ]);
        $cookies->set('foo', 'bar');
        $response = new Response(200, null, $cookies);
        $shouldBe = [
            'foo' => [
                'value' => 'bar',
                'expires' => '20 minutes',
                'path' => '/',
                'domain' => 'example.com',
                'secure' => false,
                'httponly' => false
            ]
        ];

        $this->assertEquals($shouldBe, $response->getCookies());
    }

    public function testHasCookie()
    {
        $cookies = new Cookies();
        $cookies->setDefaults([
            'expires' => '20 minutes',
            'path' => '/',
            'domain' => 'example.com',
            'secure' => false,
            'httponly' => false
        ]);
        $cookies->set('foo', 'bar');
        $response = new Response(200, null, $cookies);

        $this->assertTrue($response->hasCookie('foo'));
        $this->assertFalse($response->hasCookie('bar'));
    }

    public function testGetCookie()
    {
        $expiresAt = time();
        $expresAtString = gmdate('D, d-M-Y H:i:s e', $expiresAt);
        $cookies = new Cookies();
        $cookies->setDefaults([
            'expires' => $expiresAt,
            'path' => '/',
            'domain' => 'example.com',
            'secure' => true,
            'httponly' => true
        ]);
        $cookies->set('foo', 'bar');
        $response = new Response(200, null, $cookies);

        $this->assertEquals('foo=bar; domain=example.com; path=/; expires=' . $expresAtString . '; secure; HttpOnly', $response->getCookie('foo'));
    }

    public function testGetCookieProperties()
    {
        $expiresAt = time();
        $cookies = new Cookies();
        $cookies->setDefaults([
            'expires' => $expiresAt,
            'path' => '/',
            'domain' => 'example.com',
            'secure' => true,
            'httponly' => true
        ]);
        $cookies->set('foo', 'bar');
        $response = new Response(200, null, $cookies);
        $props = $response->getCookieProperties('foo');
        $props2 = $response->getCookieProperties('bar');

        $this->assertEquals($expiresAt, $props['expires']);
        $this->assertEquals('/', $props['path']);
        $this->assertEquals('example.com', $props['domain']);
        $this->assertTrue($props['secure']);
        $this->assertTrue($props['httponly']);
        $this->assertNull($props2);
    }

    public function testWithCookie()
    {
        $cookies = new Cookies();
        $cookies->setDefaults([
            'expires' => '2 days',
            'path' => '/',
            'domain' => 'example.com',
            'secure' => true,
            'httponly' => true
        ]);
        $cookies->set('foo', 'bar');
        $response = new Response(200, null, $cookies);
        $clone = $response->withCookie('foo', 'xyz');

        $this->assertEquals('xyz', $clone->getCookieProperties('foo')['value']);
    }

    public function testWithoutCookie()
    {
        $cookies = new Cookies();
        $cookies->setDefaults([
            'expires' => '2 days',
            'path' => '/',
            'domain' => 'example.com',
            'secure' => true,
            'httponly' => true
        ]);
        $cookies->set('foo', 'bar');
        $response = new Response(200, null, $cookies);
        $now = time();
        $clone = $response->withoutCookie('foo');

        $this->assertEquals('', $clone->getCookieProperties('foo')['value']);
        $this->assertLessThan($now, $clone->getCookieProperties('foo')['expires']);
    }

    // public function testWithEncryptedCookies()
    // {
    //     $cookies = new Cookies();
    //     $cookies->setDefaults([
    //         'expires' => '2 days',
    //         'path' => '/',
    //         'domain' => 'example.com',
    //         'secure' => true,
    //         'httponly' => true
    //     ]);
    //     $cookies->set('foo', 'bar');
    //     $response = new Response(200, null, $cookies);
    //     $crypt = new Crypt('sekritsdfsadt7u5', MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
    //     $clone = $response->withEncryptedCookies($crypt);
//
    //     $this->assertNotEquals('bar', $clone->getCookieProperties('foo')['value']);
    // }

    /*******************************************************************************
     * Body
     ******************************************************************************/

    public function testGetBody()
    {
        $headers = new Headers();
        $cookies = new Cookies();
        $body = new Body(fopen('php://temp', 'r+'));
        $response = new Response(404, $headers, $cookies, $body);

        $this->assertSame($body, $response->getBody());
    }

    public function testWithBody()
    {
        $headers = new Headers();
        $cookies = new Cookies();
        $body = new Body(fopen('php://temp', 'r+'));
        $body2 = new Body(fopen('php://temp', 'r+'));
        $response = new Response(404, $headers, $cookies, $body);
        $clone = $response->withBody($body2);

        $this->assertAttributeSame($body2, 'body', $clone);
    }

    public function testSendBody()
    {
        $this->expectOutputString('Hello world');

        $headers = new Headers();
        $cookies = new Cookies();
        $body = new Body(fopen('php://temp', 'r+'));
        $body->write('Hello world');
        $response = new Response(404, $headers, $cookies, $body);
        $response->sendBody();
    }

    public function testSendBody204()
    {
        $this->expectOutputString('');

        $headers = new Headers();
        $cookies = new Cookies();
        $body = new Body(fopen('php://temp', 'r+'));
        $body->write('Hello world');
        $response = new Response(204, $headers, $cookies, $body);
        $response->sendBody();
    }

    /*******************************************************************************
     * HTTP caching
     ******************************************************************************/

    public function testWithExpiresAsTimestamp()
    {
        $expiresAt = time() + 86400;
        $expiresAtString = gmdate('D, d M Y H:i:s T', $expiresAt);
        $headers = new Headers();
        $response = new Response(200, $headers);
        $clone = $response->withExpires($expiresAt);

        $this->assertEquals($expiresAtString, $clone->getHeader('Expires'));
    }

    public function testWithExpiresAsString()
    {
        $expiresAt = 'next Tuesday';
        $expiresAtString = gmdate('D, d M Y H:i:s T', strtotime($expiresAt));
        $headers = new Headers();
        $response = new Response(200, $headers);
        $clone = $response->withExpires($expiresAt);

        $this->assertEquals($expiresAtString, $clone->getHeader('Expires'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithExpiresAsStringInvalid()
    {
        $response = new Response();
        $clone = $response->withExpires('foo');
    }

    public function testWithLastModifiedAsTimestamp()
    {
        $lastModifiedAt = time() + 86400;
        $lastModifiedAtString = gmdate('D, d M Y H:i:s T', $lastModifiedAt);
        $headers = new Headers();
        $response = new Response(200, $headers);
        $clone = $response->withLastModified($lastModifiedAt);

        $this->assertEquals($lastModifiedAtString, $clone->getHeader('Last-Modified'));
    }

    public function testWithLastModifiedAsString()
    {
        $lastModifiedAt = 'next Tuesday';
        $lastModifiedAtString = gmdate('D, d M Y H:i:s T', strtotime($lastModifiedAt));
        $headers = new Headers();
        $response = new Response(200, $headers);
        $clone = $response->withLastModified($lastModifiedAt);

        $this->assertEquals($lastModifiedAtString, $clone->getHeader('Last-Modified'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithLastModifiedAsStringInvalid()
    {
        $response = new Response();
        $clone = $response->withLastModified('foo');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWithLastModifiedCallbackProperty()
    {
        $lastModifiedAt = 'next Tuesday';
        $lastModifiedAtString = gmdate('D, d M Y H:i:s T', strtotime($lastModifiedAt));
        $headers = new Headers();
        $response = new Response(200, $headers);
        $response->onLastModified(function ($res, $time) {
            throw new \RuntimeException();
        });
        $clone = $response->withLastModified($lastModifiedAt);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWithLastModifiedCallbackArgument()
    {
        $lastModifiedAt = 'next Tuesday';
        $lastModifiedAtString = gmdate('D, d M Y H:i:s T', strtotime($lastModifiedAt));
        $headers = new Headers();
        $response = new Response(200, $headers);
        $clone = $response->withLastModified($lastModifiedAt, function ($res, $time) {
            throw new \RuntimeException();
        });
    }

    public function testWithEtag()
    {
        $headers = new Headers();
        $response = new Response(200, $headers);
        $clone = $response->withEtag('abc', 'weak');

        $this->assertEquals('W/"abc"', $clone->getHeader('Etag'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWithEtagCallbackProperty()
    {
        $headers = new Headers();
        $response = new Response(200, $headers);
        $response->onEtag(function ($res, $value) {
            throw new \RuntimeException();
        });
        $clone = $response->withEtag('abc', 'weak');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWithEtagCallbackArgument()
    {
        $headers = new Headers();
        $response = new Response(200, $headers);
        $clone = $response->withEtag('abc', 'weak', function ($res, $value) {
            throw new \RuntimeException();
        });
    }

    /*******************************************************************************
     * Behaviors
     ******************************************************************************/

    public function testWithRedirect()
    {
        $response = new Response();
        $response = $response->withRedirect('/foo', 301);

        $this->assertAttributeEquals(301, 'status', $response);
        $this->assertEquals('/foo', $response->getHeader('Location'));
    }

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

    /*******************************************************************************
     * Finalize
     ******************************************************************************/

    public function testFinalizeSerializesCookies()
    {
        $cookies = new Cookies();
        $cookies->setDefaults([
            'expires' => '2 days',
            'path' => '/',
            'domain' => 'example.com',
            'secure' => true,
            'httponly' => true
        ]);
        $cookies->set('test', 'foo');
        $response = new Response(200, null, $cookies);
        $response = $response->finalize();

        $this->assertArrayHasKey('Set-Cookie', $response->getHeaders());
    }

    public function testFinalizeRemovesHeaders()
    {
        $response = new Response(204);
        $response = $response->withHeader('Content-Type', 'text/plain');
        $response->write('Hello world');
        $response = $response->finalize();
        $headers = $response->getHeaders();

        $this->assertArrayNotHasKey('Content-Type', $headers);
        $this->assertArrayNotHasKey('Content-Length', $headers);
    }

    public function testFinalizeAddsHeaders()
    {
        $response = new Response(200);
        $response = $response->withHeader('Content-Type', 'text/plain');
        $response->write('Hello world');
        $response = $response->finalize();
        $headers = $response->getHeaders();

        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Content-Length', $headers);
    }
}
