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

class ResponseTest extends PHPUnit_Framework_TestCase
{
    protected $response;
    protected $statusProperty;
    protected $headersProperty;
    protected $cookiesProperty;
    protected $bodyProperty;

    /*******************************************************************************
     * Setup
     ******************************************************************************/

    protected function createResponse(array $headerData = array(), array $cookieData = array(), $body = '', $status = 200)
    {
        $headers = new \Slim\Http\Headers();
        $headers->replace($headerData);

        $cookies = new \Slim\Http\Cookies();
        $cookies->replace($cookieData);

        return new \Slim\Http\Response($headers, $cookies, $body, $status);
    }

    public function setUp()
    {
        $this->response = $this->createResponse();

        $this->statusProperty = new \ReflectionProperty($this->response, 'status');
        $this->statusProperty->setAccessible(true);

        $this->headersProperty = new \ReflectionProperty($this->response, 'headers');
        $this->headersProperty->setAccessible(true);

        $this->cookiesProperty = new \ReflectionProperty($this->response, 'cookies');
        $this->cookiesProperty->setAccessible(true);

        $this->bodyProperty = new \ReflectionProperty($this->response, 'body');
        $this->bodyProperty->setAccessible(true);
    }

    /*******************************************************************************
     * Response Defaults
     ******************************************************************************/

    public function testDefaultStatus()
    {
        $this->assertAttributeEquals(200, 'status', $this->response);
    }

    public function testDefaultContentType()
    {
        $this->assertEquals('text/html', $this->response->getHeader('Content-Type'));
    }

    public function testDefaultBody()
    {
        $this->assertEquals('', (string)$this->bodyProperty->getValue($this->response));
    }

    /*******************************************************************************
     * Response Header
     ******************************************************************************/

    public function testGetStatus()
    {
        $this->statusProperty->setValue($this->response, 201);

        $this->assertEquals(201, $this->response->getStatus());
    }

    public function testSetStatus()
    {
        $this->response->setStatus(301);

        $this->assertAttributeEquals(301, 'status', $this->response);
    }

    public function testGetReasonPhrase()
    {
        $this->assertEquals('200 OK', $this->response->getReasonPhrase());
    }

    public function testGetHeaders()
    {
        $headers = array(
            'Content-Type' => 'application/json',
            'X-Foo' => 'Bar'
        );
        $this->headersProperty->getValue($this->response)->replace($headers);

        $this->assertSame($headers, $this->response->getHeaders());
    }

    public function testHasHeader()
    {
        $headers = array(
            'X-Foo' => 'Bar'
        );
        $this->headersProperty->getValue($this->response)->replace($headers);

        $this->assertTrue($this->response->hasHeader('X-Foo'));
    }

    public function testGetHeader()
    {
        $headers = array(
            'X-Foo' => 'Bar'
        );
        $this->headersProperty->getValue($this->response)->replace($headers);

        $this->assertEquals('Bar', $this->response->getHeader('X-Foo'));
    }

    public function testSetHeader()
    {
        $this->response->setHeader('X-Foo', 'Bar');

        $this->assertArrayHasKey('X-Foo', $this->headersProperty->getValue($this->response)->all());
    }

    public function testSetHeaders()
    {
        $this->response->setHeaders(array(
            'X-Foo' => 'Bar',
            'X-Test' => '123'
        ));

        $this->assertArrayHasKey('X-Foo', $this->headersProperty->getValue($this->response)->all());
        $this->assertArrayHasKey('X-Test', $this->headersProperty->getValue($this->response)->all());
    }

    public function testRemoveHeader()
    {
        $headers = array(
            'X-Foo' => 'Bar'
        );
        $this->headersProperty->getValue($this->response)->replace($headers);
        $this->response->removeHeader('X-Foo');

        $this->assertArrayNotHasKey('X-Foo', $this->headersProperty->getValue($this->response)->all());
    }

    public function testGetCookies()
    {
        $this->cookiesProperty->getValue($this->response)->replace(array('foo' => 'bar'));
        $cookies = $this->response->getCookies();

        $this->assertEquals('bar', $cookies['foo']['value']);
    }

    public function testSetCookies()
    {
        $this->response->setCookies(array('foo' => 'bar'));
        $cookies = $this->cookiesProperty->getValue($this->response);

        $this->assertEquals('bar', $cookies['foo']['value']);
    }

    public function testHasCookie()
    {
        $this->cookiesProperty->getValue($this->response)->replace(array('foo' => 'bar'));

        $this->assertTrue($this->response->hasCookie('foo'));
    }

    public function testGetCookie()
    {
        $this->cookiesProperty->getValue($this->response)->replace(array('foo' => 'bar'));
        $cookie = $this->response->getCookie('foo');

        $this->assertEquals('bar', $cookie['value']);
    }

    public function testSetCookie()
    {
        $this->response->setCookie('foo', 'bar');
        $cookies = $this->cookiesProperty->getValue($this->response);

        $this->assertEquals('bar', $cookies['foo']['value']);
    }

    public function testRemoveCookie()
    {
        $this->cookiesProperty->getValue($this->response)->replace(array('foo' => 'bar'));
        $this->response->removeCookie('foo');
        $cookie = $this->cookiesProperty->getValue($this->response)->get('foo');

        $this->assertEquals('', $cookie['value']);
        $this->assertTrue($cookie['expires'] < time());
    }

    /*public function testEncryptCookies()
    {

    }*/

    /*******************************************************************************
     * Response Body
     ******************************************************************************/

    public function testGetBody()
    {
        $this->bodyProperty->getValue($this->response)->write('Foo');
        $body = $this->response->getBody();

        $this->assertInstanceOf('\Guzzle\Stream\StreamInterface', $body);
        $this->assertEquals('Foo', (string)$this->response->getBody());
    }

    public function testSetBody()
    {
        $newStream = new \Guzzle\Stream\Stream(fopen('php://temp', 'r+'));
        $this->response->setBody($newStream);

        $this->assertSame($newStream, $this->bodyProperty->getValue($this->response));
    }

    public function testWrite()
    {
        $this->bodyProperty->getValue($this->response)->write('Foo');
        $this->response->write('Bar');

        $this->assertEquals('FooBar', (string)$this->bodyProperty->getValue($this->response));
    }

    public function testWriteReplace()
    {
        $this->bodyProperty->getValue($this->response)->write('Foo');
        $this->response->write('Bar', true);

        $this->assertEquals('Bar', (string)$this->bodyProperty->getValue($this->response));
    }

    public function testGetSize()
    {
        $this->bodyProperty->getValue($this->response)->write('Foo');

        $this->assertEquals(3, $this->response->getSize());
    }

    /*******************************************************************************
     * Response Helpers
     ******************************************************************************/

    public function testFinalize()
    {
        $result = $this->response->finalize();

        $this->assertTrue(is_array($result));
        $this->assertCount(3, $result);
        $this->assertEquals(200, $result[0]);
        $this->assertInstanceOf('\Slim\Http\Headers', $result[1]);
        $this->assertEquals('', (string)$result[2]);
    }

    public function testFinalizeWithEmptyBody()
    {
        $this->statusProperty->setValue($this->response, 304);
        $this->headersProperty->getValue($this->response)->set('Content-Type', 'text/csv');
        $this->bodyProperty->getValue($this->response)->write('Foo');
        $result = $this->response->finalize();

        $this->assertFalse($result[1]->has('Content-Type'));
        $this->assertFalse($result[1]->has('Content-Length'));
        $this->assertEquals('', (string)$result[2]);
    }

    public function testRedirect()
    {
        $this->response->redirect('/foo');

        $this->assertEquals(302, $this->statusProperty->getValue($this->response));
        $this->assertEquals('/foo', $this->headersProperty->getValue($this->response)->get('Location'));
    }

    public function testIsEmptyWhenTrue()
    {
        $this->statusProperty->setValue($this->response, 201);

        $this->assertTrue($this->response->isEmpty());
    }

    public function testIsEmptyWhenFalse()
    {
        $this->statusProperty->setValue($this->response, 400);

        $this->assertFalse($this->response->isEmpty());
    }

    public function testIsInformationalWhenTrue()
    {
        $this->statusProperty->setValue($this->response, 100);

        $this->assertTrue($this->response->isInformational());
    }

    public function testIsInformationalWhenFalse()
    {
        $this->statusProperty->setValue($this->response, 200);

        $this->assertFalse($this->response->isInformational());
    }

    public function testIsOkWhenTrue()
    {
        $this->statusProperty->setValue($this->response, 200);

        $this->assertTrue($this->response->isOk());
    }

    public function testIsOkWhenFalse()
    {
        $this->statusProperty->setValue($this->response, 300);

        $this->assertFalse($this->response->isOk());
    }

    public function testIsSuccessfulWhenTrue()
    {
        $this->statusProperty->setValue($this->response, 201);

        $this->assertTrue($this->response->isSuccessful());
    }

    public function testIsSuccessfulWhenFalse()
    {
        $this->statusProperty->setValue($this->response, 301);

        $this->assertFalse($this->response->isSuccessful());
    }

    public function testIsRedirectWhenTrue()
    {
        $this->statusProperty->setValue($this->response, 303);

        $this->assertTrue($this->response->isRedirect());
    }

    public function testIsRedirectWhenFalse()
    {
        $this->statusProperty->setValue($this->response, 308);

        $this->assertFalse($this->response->isRedirect());
    }

    public function testIsRedirection()
    {
        $this->statusProperty->setValue($this->response, 308);

        $this->assertTrue($this->response->isRedirection());
    }

    public function testIsForbiddenWhenTrue()
    {
        $this->statusProperty->setValue($this->response, 403);

        $this->assertTrue($this->response->isForbidden());
    }

    public function testIsForbiddenWhenFalse()
    {
        $this->statusProperty->setValue($this->response, 404);

        $this->assertFalse($this->response->isForbidden());
    }

    public function testIsNotFoundWhenTrue()
    {
        $this->statusProperty->setValue($this->response, 404);

        $this->assertTrue($this->response->isNotFound());
    }

    public function testIsNotFoundWhenFalse()
    {
        $this->statusProperty->setValue($this->response, 403);

        $this->assertFalse($this->response->isNotFound());
    }

    public function testIsClientErrorWhenTrue()
    {
        $this->statusProperty->setValue($this->response, 404);

        $this->assertTrue($this->response->isClientError());
    }

    public function testIsClientErrorWhenFalse()
    {
        $this->statusProperty->setValue($this->response, 503);

        $this->assertFalse($this->response->isClientError());
    }

    public function testIsServerErrorWhenTrue()
    {
        $this->statusProperty->setValue($this->response, 503);

        $this->assertTrue($this->response->isServerError());
    }

    public function testIsServerErrorWhenFalse()
    {
        $this->statusProperty->setValue($this->response, 403);

        $this->assertFalse($this->response->isServerError());
    }
}
