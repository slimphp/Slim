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
use \Slim\Http\Uri;
use \Slim\Http\Headers;
use \Slim\Collection;
use \Slim\Http\Body;
use \Slim\Http\Request;

class RequestTest extends PHPUnit_Framework_TestCase
{
    public function requestFactory()
    {
        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = new Collection([
            'user' => 'john',
            'id' => '123'
        ]);
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $body);

        return $request;
    }

    /*******************************************************************************
     * Protocol
     ******************************************************************************/

    public function testGetProtocol()
    {
        $this->assertEquals('1.1', $this->requestFactory()->getProtocolVersion());
    }

    public function testWithProtocol()
    {
        $clone = $this->requestFactory()->withProtocolVersion('1.0');

        $this->assertAttributeEquals('1.0', 'protocolVersion', $clone);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithProtocolInvalid()
    {
        $clone = $this->requestFactory()->withProtocolVersion('foo');
    }

    /*******************************************************************************
     * Method
     ******************************************************************************/

    public function testGetMethod()
    {
        $this->assertEquals('GET', $this->requestFactory()->getMethod());
    }

    public function testWithMethod()
    {
        $clone = $this->requestFactory()->withMethod('PuT');

        $this->assertAttributeEquals('PUT', 'method', $clone);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithMethodInvalid()
    {
        $this->requestFactory()->withMethod('FOO');
    }

    public function testMethodOverrideHeader()
    {
        $headers = new Headers([
            'X-Http-Method-Override' => ['PUT']
        ]);
        $request = $this->requestFactory();
        $headersProp = new \ReflectionProperty($request, 'headers');
        $headersProp->setAccessible(true);
        $headersProp->setValue($request, $headers);

        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals('GET', $request->getOriginalMethod());
    }

    /*******************************************************************************
     * URI
     ******************************************************************************/

    public function testGetRequestTarget()
    {
        $this->assertEquals('/foo/bar?abc=123', $this->requestFactory()->getRequestTarget());
    }

    public function testWithRequestTarget()
    {
        $clone = $this->requestFactory()->withRequestTarget('/test?user=1');

        $this->assertAttributeEquals('/test?user=1', 'requestTarget', $clone);
    }

    public function testGetUri()
    {
        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = new Collection();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $body);

        $this->assertSame($uri, $request->getUri());
    }

    public function testWithUri()
    {
        // Uris
        $uri1 = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $uri2 = Uri::createFromString('https://example2.com:443/test?xyz=123');

        // Request
        $headers = new Headers();
        $cookies = new Collection();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri1, $headers, $cookies, $body);
        $clone = $request->withUri($uri2);

        $this->assertAttributeSame($uri2, 'uri', $clone);
    }

    /*******************************************************************************
     * Headers
     ******************************************************************************/

    public function testGetHeaders()
    {
        $headers = new Headers([
            'X-Foo' => ['one', 'two', 'three']
        ]);
        $request = $this->requestFactory();
        $headersProp = new \ReflectionProperty($request, 'headers');
        $headersProp->setAccessible(true);
        $headersProp->setValue($request, $headers);

        $shouldBe = [
            'X-Foo' => ['one', 'two', 'three']
        ];
        $this->assertEquals($shouldBe, $request->getHeaders());
    }

    public function testHasHeader()
    {
        $headers = new Headers(['X-Foo' => ['one']]);
        $request = $this->requestFactory();
        $headersProp = new \ReflectionProperty($request, 'headers');
        $headersProp->setAccessible(true);
        $headersProp->setValue($request, $headers);

        $this->assertTrue($request->hasHeader('X-Foo'));
        $this->assertFalse($request->hasHeader('X-Bar'));
    }

    public function testGetHeader()
    {
        $headers = new Headers([
            'X-Foo' => ['one', 'two', 'three']
        ]);
        $request = $this->requestFactory();
        $headersProp = new \ReflectionProperty($request, 'headers');
        $headersProp->setAccessible(true);
        $headersProp->setValue($request, $headers);

        $this->assertEquals('one,two,three', $request->getHeader('X-Foo'));
        $this->assertEquals('', $request->getHeader('X-Bar'));
    }

    public function testGetHeaderLines()
    {
        $headers = new Headers([
            'X-Foo' => ['one', 'two', 'three']
        ]);
        $request = $this->requestFactory();
        $headersProp = new \ReflectionProperty($request, 'headers');
        $headersProp->setAccessible(true);
        $headersProp->setValue($request, $headers);

        $this->assertEquals(['one', 'two', 'three'], $request->getHeaderLines('X-Foo'));
        $this->assertEquals([], $request->getHeaderLines('X-Bar'));
    }

    public function testWithHeader()
    {
        $request = $this->requestFactory();
        $clone = $request->withHeader('X-Foo', 'bar');

        $this->assertEquals('bar', $clone->getHeader('X-Foo'));
    }

    public function testWithAddedHeader()
    {
        $headers = new Headers([
            'X-Foo' => ['one']
        ]);
        $request = $this->requestFactory();
        $headersProp = new \ReflectionProperty($request, 'headers');
        $headersProp->setAccessible(true);
        $headersProp->setValue($request, $headers);
        $clone = $request->withAddedHeader('X-Foo', 'two');

        $this->assertEquals('one,two', $clone->getHeader('X-Foo'));
    }

    public function testWithoutHeader()
    {
        $headers = new Headers([
            'X-Foo' => ['one'],
            'X-Bar' => ['two']
        ]);
        $request = $this->requestFactory();
        $headersProp = new \ReflectionProperty($request, 'headers');
        $headersProp->setAccessible(true);
        $headersProp->setValue($request, $headers);
        $clone = $request->withoutHeader('X-Foo');
        $shouldBe = [
            'X-Bar' => ['two']
        ];

        $this->assertEquals($shouldBe, $clone->getHeaders());
    }

    public function testGetContentType()
    {
        $headers = new Headers([
            'Content-Type' => ['application/json;charset=utf8']
        ]);
        $request = $this->requestFactory();
        $headersProp = new \ReflectionProperty($request, 'headers');
        $headersProp->setAccessible(true);
        $headersProp->setValue($request, $headers);

        $this->assertEquals('application/json;charset=utf8', $request->getContentType());
    }

    public function testGetContentTypeEmpty()
    {
        $request = $this->requestFactory();

        $this->assertNull($request->getContentType());
    }

    public function testGetMediaType()
    {
        $headers = new Headers([
            'Content-Type' => ['application/json;charset=utf8']
        ]);
        $request = $this->requestFactory();
        $headersProp = new \ReflectionProperty($request, 'headers');
        $headersProp->setAccessible(true);
        $headersProp->setValue($request, $headers);

        $this->assertEquals('application/json', $request->getMediaType());
    }

    public function testGetMediaTypeEmpty()
    {
        $request = $this->requestFactory();

        $this->assertNull($request->getContentType());
    }

    public function testGetMediaTypeParams()
    {
        $headers = new Headers([
            'Content-Type' => ['application/json;charset=utf8;foo=bar']
        ]);
        $request = $this->requestFactory();
        $headersProp = new \ReflectionProperty($request, 'headers');
        $headersProp->setAccessible(true);
        $headersProp->setValue($request, $headers);

        $this->assertEquals(['charset' => 'utf8', 'foo' => 'bar'], $request->getMediaTypeParams());
    }

    public function testGetMediaTypeParamsEmpty()
    {
        $headers = new Headers([
            'Content-Type' => ['application/json']
        ]);
        $request = $this->requestFactory();
        $headersProp = new \ReflectionProperty($request, 'headers');
        $headersProp->setAccessible(true);
        $headersProp->setValue($request, $headers);

        $this->assertEquals([], $request->getMediaTypeParams());
    }

    public function testGetMediaTypeParamsWithoutHeader()
    {
        $request = $this->requestFactory();

        $this->assertEquals([], $request->getMediaTypeParams());
    }

    public function testGetContentCharset()
    {
        $headers = new Headers([
            'Content-Type' => ['application/json;charset=utf8']
        ]);
        $request = $this->requestFactory();
        $headersProp = new \ReflectionProperty($request, 'headers');
        $headersProp->setAccessible(true);
        $headersProp->setValue($request, $headers);

        $this->assertEquals('utf8', $request->getContentCharset());
    }

    public function testGetContentCharsetEmpty()
    {
        $headers = new Headers([
            'Content-Type' => ['application/json']
        ]);
        $request = $this->requestFactory();
        $headersProp = new \ReflectionProperty($request, 'headers');
        $headersProp->setAccessible(true);
        $headersProp->setValue($request, $headers);

        $this->assertNull($request->getContentCharset());
    }

    public function testGetContentCharsetWithoutHeader()
    {
        $request = $this->requestFactory();

        $this->assertNull($request->getContentCharset());
    }

    public function testGetContentLength()
    {
        $headers = new Headers([
            'Content-Length' => '150' // <-- Note we define as a string
        ]);
        $request = $this->requestFactory();
        $headersProp = new \ReflectionProperty($request, 'headers');
        $headersProp->setAccessible(true);
        $headersProp->setValue($request, $headers);

        $this->assertEquals(150, $request->getContentLength());
    }

    public function testGetContentLengthWithoutHeader()
    {
        $request = $this->requestFactory();

        $this->assertNull($request->getContentLength());
    }

    /*******************************************************************************
     * Cookies
     ******************************************************************************/

    public function testGetCookieParams()
    {
        $shouldBe = [
            'user' => 'john',
            'id' => '123'
        ];

        $this->assertEquals($shouldBe, $this->requestFactory()->getCookieParams());
    }

    public function testWithCookieParams()
    {
        $request = $this->requestFactory();
        $clone = $request->withCookieParams(['type' => 'framework']);

        $this->assertEquals(['type' => 'framework'], $clone->getCookieParams());
    }

    /*******************************************************************************
     * Query Params
     ******************************************************************************/

    public function testGetQueryParams()
    {
        $this->assertEquals(['abc' => '123'], $this->requestFactory()->getQueryParams());
    }

    public function testWithQueryParams()
    {
        $request = $this->requestFactory();
        $clone = $request->withQueryParams(['foo' => 'bar']);
        $cloneUri = $clone->getUri();

        $this->assertEquals('abc=123', $cloneUri->getQuery()); // <-- Unchanged
        $this->assertAttributeEquals(['foo' => 'bar'], 'queryParams', $clone); // <-- Changed
    }

    /*******************************************************************************
     * Server Params
     ******************************************************************************/

    /*******************************************************************************
     * File Params
     ******************************************************************************/

    /*******************************************************************************
     * Attributes
     ******************************************************************************/

    public function testGetAttributes()
    {
        $request = $this->requestFactory();
        $attrProp = new \ReflectionProperty($request, 'attributes');
        $attrProp->setAccessible(true);
        $attrProp->setValue($request, new \Slim\Collection(['foo' => 'bar']));

        $this->assertEquals(['foo' => 'bar'], $request->getAttributes());
    }

    public function testGetAttribute()
    {
        $request = $this->requestFactory();
        $attrProp = new \ReflectionProperty($request, 'attributes');
        $attrProp->setAccessible(true);
        $attrProp->setValue($request, new \Slim\Collection(['foo' => 'bar']));

        $this->assertEquals('bar', $request->getAttribute('foo'));
        $this->assertNull($request->getAttribute('bar'));
        $this->assertEquals(2, $request->getAttribute('bar', 2));
    }

    public function testWithAttribute()
    {
        $request = $this->requestFactory();
        $attrProp = new \ReflectionProperty($request, 'attributes');
        $attrProp->setAccessible(true);
        $attrProp->setValue($request, new \Slim\Collection(['foo' => 'bar']));
        $clone = $request->withAttribute('test', '123');

        $this->assertEquals('123', $clone->getAttribute('test'));
    }

    public function testWithAttributes()
    {
        $request = $this->requestFactory();
        $attrProp = new \ReflectionProperty($request, 'attributes');
        $attrProp->setAccessible(true);
        $attrProp->setValue($request, new \Slim\Collection(['foo' => 'bar']));
        $clone = $request->withAttributes(['test' => '123']);

        $this->assertNull($clone->getAttribute('foo'));
        $this->assertEquals('123', $clone->getAttribute('test'));
    }

    public function testWithoutAttribute()
    {
        $request = $this->requestFactory();
        $attrProp = new \ReflectionProperty($request, 'attributes');
        $attrProp->setAccessible(true);
        $attrProp->setValue($request, new \Slim\Collection(['foo' => 'bar']));
        $clone = $request->withoutAttribute('foo');

        $this->assertNull($clone->getAttribute('foo'));
    }

    /*******************************************************************************
     * Body
     ******************************************************************************/

    public function testGetBody()
    {
        $bodyNew = new Body(fopen('php://temp', 'r+'));
        $request = $this->requestFactory();
        $bodyProp = new \ReflectionProperty($request, 'body');
        $bodyProp->setAccessible(true);
        $bodyProp->setValue($request, $bodyNew);

        $this->assertSame($bodyNew, $request->getBody());
    }

    public function testWithBody()
    {
        $bodyNew = new Body(fopen('php://temp', 'r+'));
        $request = $this->requestFactory()->withBody($bodyNew);

        $this->assertAttributeSame($bodyNew, 'body', $request);
    }

    public function testGetParsedBodyForm()
    {
        $method = 'GET';
        $uri = new Uri('https', '', '', 'example.com', 443, '/foo/bar', 'abc=123');
        $headers = new Headers();
        $headers->set('Content-Type', 'application/x-www-form-urlencoded;charset=utf8');
        $cookies = new Collection();
        $body = new Body(fopen('php://temp', 'r+'));
        $body->write('foo=bar');
        $request = new Request($method, $uri, $headers, $cookies, $body);
        $this->assertEquals((object)['foo' => 'bar'], $request->getParsedBody());
    }

    public function testGetParsedBodyJson()
    {
        $method = 'GET';
        $uri = new Uri('https', '', '', 'example.com', 443, '/foo/bar', 'abc=123');
        $headers = new Headers();
        $headers->set('Content-Type', 'application/json;charset=utf8');
        $cookies = new Collection();
        $body = new Body(fopen('php://temp', 'r+'));
        $body->write('{"foo":"bar"}');
        $request = new Request($method, $uri, $headers, $cookies, $body);

        $this->assertEquals((object)['foo' => 'bar'], $request->getParsedBody());
    }

    public function testGetParsedBodyXml()
    {
        $method = 'GET';
        $uri = new Uri('https', '', '', 'example.com', 443, '/foo/bar', 'abc=123');
        $headers = new Headers();
        $headers->set('Content-Type', 'application/xml;charset=utf8');
        $cookies = new Collection();
        $body = new Body(fopen('php://temp', 'r+'));
        $body->write('<person><name>Josh</name></person>');
        $request = new Request($method, $uri, $headers, $cookies, $body);

        $this->assertEquals('Josh', $request->getParsedBody()->name);
    }

    public function testWithParsedBody()
    {
        $clone = $this->requestFactory()->withParsedBody(['xyz' => '123']);

        $this->assertAttributeEquals(['xyz' => '123'], 'bodyParsed', $clone);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithParsedBodyInvalid()
    {
        $this->requestFactory()->withParsedBody(2);
    }
}
