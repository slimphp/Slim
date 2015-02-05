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
    protected $environment;
    protected $request;
    protected $response;
    protected $protocolVersionProperty;
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
        $this->environment = new \Slim\Environment();
        $this->environment->mock();

        $this->request = new \Slim\Http\Request(
            $this->environment,
            new \Slim\Http\Headers(),
            new \Slim\Http\Cookies()
        );

        $this->response = $this->createResponse();

        $this->protocolVersionProperty = new \ReflectionProperty($this->response, 'protocolVersion');
        $this->protocolVersionProperty->setAccessible(true);

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
        $this->assertTrue(is_resource($this->bodyProperty->getValue($this->response)));
    }

    /*******************************************************************************
     * Response Header
     ******************************************************************************/

    public function testGetProtocolVersion()
    {
        $this->protocolVersionProperty->setValue($this->response, 'HTTP/1.0');

        $this->assertEquals('HTTP/1.0', $this->response->getProtocolVersion());
    }

    public function testSetProtocolVersion()
    {
        $this->response->setProtocolVersion('HTTP/1.0');

        $this->assertAttributeEquals('HTTP/1.0', 'protocolVersion', $this->response);
    }

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

        $this->assertSame(array(
            'Content-Type' => array('application/json'),
            'X-Foo' => array('Bar')
        ), $this->response->getHeaders());
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

    public function testAddHeader()
    {
        $this->response->setHeader('X-Foo', 'Bar');
        $this->response->addHeader('X-Foo', 'Foo');
        $this->assertArrayHasKey('X-Foo', $this->headersProperty->getValue($this->response)->all());
        $this->assertEquals('Bar, Foo', $this->headersProperty->getValue($this->response)->get('X-Foo'));
    }

    public function testAddHeaders()
    {
        $this->response->setHeader('X-Foo', 'Bar');
        $this->response->addHeaders(array('X-Foo' => array('Foo', '123')));
        $this->assertArrayHasKey('X-Foo', $this->headersProperty->getValue($this->response)->all());
        $this->assertEquals('Bar, Foo, 123', $this->headersProperty->getValue($this->response)->get('X-Foo'));
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
        fwrite($this->bodyProperty->getValue($this->response), 'Foo');
        $body = $this->response->getBody();

        $this->assertTrue(is_resource($body));
        $this->assertEquals('Foo', stream_get_contents($body, -1, 0));
    }

    public function testSetBody()
    {
        $newStream = fopen('php://temp', 'r+');
        $this->response->setBody($newStream);

        $this->assertSame($newStream, $this->bodyProperty->getValue($this->response));
    }

    public function testWrite()
    {
        fwrite($this->bodyProperty->getValue($this->response), 'Foo');
        $this->response->write('Bar');

        $this->assertEquals('FooBar', stream_get_contents($this->bodyProperty->getValue($this->response), -1, 0));
    }

    public function testWriteReplace()
    {
        fwrite($this->bodyProperty->getValue($this->response), 'Foo');
        $this->response->write('Bar', true);

        $this->assertEquals('Bar', stream_get_contents($this->bodyProperty->getValue($this->response), -1, 0));
    }

    public function testGetSize()
    {
        $this->response->write('Foo');

        $this->assertEquals(3, $this->response->getSize());
    }

    /*******************************************************************************
     * Response Helpers
     ******************************************************************************/

    // public function testStreamingAFile()
    // {
    //     $this->expectOutputString(file_get_contents(dirname(__DIR__) . "/composer.json"));

    //     $app = $this->createApp();
    //     $app->get('/bar', function() use ($app) {
    //         $app->sendFile(dirname(__DIR__) . "/composer.json");
    //     });
    //     $app->run();
    // }

    // public function testStreamingAFileWithContentType()
    // {
    //     $this->expectOutputString(file_get_contents(dirname(__DIR__) . "/composer.json"));

    //     $app = $this->createApp();
    //     $app->get('/bar', function() use ($app) {
    //         $app->sendFile(dirname(__DIR__) . "/composer.json", 'application/json');
    //     });
    //     $app->run();
    //     $this->assertEquals('application/json', $app['response']->getHeader('Content-Type'));
    // }

    // public function testStreamingAProc()
    // {
    //     $this->expectOutputString("FooBar\n");

    //     $app = $this->createApp();
    //     $app->get('/bar', function() use ($app) {
    //         $app->sendProcess("echo 'FooBar'");
    //     });
    //     $app->run();
    // }

    /*******************************************************************************
     * Response Helpers
     ******************************************************************************/

    public function testFinalize()
    {
        $this->response->finalize($this->request);

        $this->assertEquals(200, $this->statusProperty->getValue($this->response));
        $this->assertEquals('', stream_get_contents($this->bodyProperty->getValue($this->response), -1, 0));
    }

    public function testFinalizeWithEmptyBody()
    {
        $this->statusProperty->setValue($this->response, 304);
        $this->headersProperty->getValue($this->response)->set('Content-Type', 'text/csv');
        fwrite($this->bodyProperty->getValue($this->response), 'Foo');
        $this->response->finalize($this->request);

        $this->assertFalse($this->headersProperty->getValue($this->response)->has('Content-Type'));
        $this->assertFalse($this->headersProperty->getValue($this->response)->has('Content-Length'));
        $this->assertEquals('', stream_get_contents($this->bodyProperty->getValue($this->response), -1, 0));
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

    public function testResponseSend()
    {
        $this->response->write('Foo');
        ob_start();
        $this->response->send();
        $output = ob_get_clean();

        $this->assertTrue(headers_sent());
        $this->assertEquals('Foo', $output);
    }

    public function testWriteJsonAsArray()
    {
        $data = [
            'people' => [
                (object)[
                    'name' => 'Josh'
                ],
                (object)[
                    'name' => 'Fred'
                ]
            ]
        ];
        $this->response->writeJson($data);
        ob_start();
        $this->response->send();
        $output = ob_get_clean();

        $this->assertTrue(strpos($this->response->getHeader('Content-Type'), 'application/json') === 0);
        $this->assertEquals('{"people":[{"name":"Josh"},{"name":"Fred"}]}', $output);
    }

    public function testWriteJsonAsString()
    {
        $data = '{"people":[{"name":"Josh"},{"name":"Fred"}]}';
        $this->response->writeJson($data);
        ob_start();
        $this->response->send();
        $output = ob_get_clean();

        $this->assertTrue(strpos($this->response->getHeader('Content-Type'), 'application/json') === 0);
        $this->assertEquals('{"people":[{"name":"Josh"},{"name":"Fred"}]}', $output);
    }

    public function testWriteXmlAsArray()
    {
        $data = [
            'people' => [
                'josh' => [
                    'name' => 'Josh',
                    'handle' => 'codeguy'
                ],
                'cal' => [
                    'name' => 'Cal',
                    'handle' => 'calevans'
                ]
            ]
        ];
        $this->response->writeXml($data);
        ob_start();
        $this->response->send();
        $output = ob_get_clean();

        $shouldBe = <<<EOL
<?xml version="1.0" encoding="UTF-8"?>
<response><people><josh name="Josh" handle="codeguy"/><cal name="Cal" handle="calevans"/></people></response>

EOL;

        $this->assertTrue(strpos($this->response->getHeader('Content-Type'), 'application/xml') === 0);
        $this->assertEquals($shouldBe, $output);
    }

    public function testWriteXmlAsString()
    {
        $data = '<people><josh name="Josh" handle="codeguy"/><cal name="Cal" handle="calevans"/></people>';
        $this->response->writeXml($data);
        ob_start();
        $this->response->send();
        $output = ob_get_clean();

        $shouldBe = <<<EOL
<?xml version="1.0" encoding="UTF-8"?>
<people><josh name="Josh" handle="codeguy"/><cal name="Cal" handle="calevans"/></people>
EOL;
        $this->assertTrue(strpos($this->response->getHeader('Content-Type'), 'application/xml') === 0);
        $this->assertEquals($shouldBe, $output);
    }
}
