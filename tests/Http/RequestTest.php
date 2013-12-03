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

class RequestTest extends PHPUnit_Framework_TestCase
{
    protected $environment;
    protected $headers;
    protected $cookies;
    protected $request;

    protected function initializeRequest(array $serverData = array(), $body = 'abc=123&foo=bar')
    {
        $this->environment = \Slim\Environment::mock(array_merge(array(
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'REQUEST_METHOD'       => 'GET',
            'SCRIPT_NAME'          => '/foo/index.php',
            'REQUEST_URI'          => '/foo/bar?hello=world',
            'QUERY_STRING'         => 'hello=world',
            'SERVER_NAME'          => 'localhost',
            'SERVER_PORT'          => 80,
            'HTTP_HOST'            => 'localhost',
            'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'HTTP_ACCEPT_CHARSET'  => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'HTTP_USER_AGENT'      => 'Slim Framework',
            'REMOTE_ADDR'          => '127.0.0.1',
            'REQUEST_TIME'         => time()
        ), $serverData));
        $this->headers = \Slim\Http\Headers::createFromEnvironment($this->environment);
        $this->cookies = new \Slim\Collection(\Slim\Http\Cookies::extractFromHeaders($this->headers));
        $this->request = new \Slim\Http\Request($this->environment, $this->headers, $this->cookies, $body);
    }

    public function setUp()
    {
        $this->initializeRequest();
    }

    /**
     * Test gets HTTP method
     */
    public function testGetMethod()
    {
        $this->assertEquals('GET', $this->request->getMethod());
    }

    /**
     * Test gets HTTP method with header override
     */
    public function testGetMethodWithHeaderOverride()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'HTTP_X_HTTP_METHOD_OVERRIDE' => 'PUT'
        ));
        $this->assertEquals('PUT', $this->request->getMethod());
    }

    /**
     * Test gets HTTP method with input override
     */
    public function testGetMethodWithInputOverride()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST'
        ), '_METHOD=PATCH');
        $this->assertEquals('PATCH', $this->request->getMethod());
    }

    /**
     * Test gets original HTTP method without override
     */
    public function testGetOriginalMethodWithoutOverride()
    {
        $this->assertEquals('GET', $this->request->getOriginalMethod());
    }

    /**
     * Test gets original HTTP method with override
     */
    public function testGetOriginalMethodWithOverride()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'HTTP_X_HTTP_METHOD_OVERRIDE' => 'PUT'
        ));
        $this->assertEquals('POST', $this->request->getOriginalMethod());
    }

    /**
     * Test method is GET
     */
    public function testIsGet()
    {
        $this->assertTrue($this->request->isGet());
    }

    /**
     * Test method is POST
     */
    public function testIsPost()
    {
        $this->initializeRequest(array('REQUEST_METHOD' => 'POST'));
        $this->assertTrue($this->request->isPost());
    }

    /**
     * Test method is PUT
     */
    public function testIsPut()
    {
        $this->initializeRequest(array('REQUEST_METHOD' => 'PUT'));
        $this->assertTrue($this->request->isPut());
    }

    /**
     * Test method is DELETE
     */
    public function testIsDelete()
    {
        $this->initializeRequest(array('REQUEST_METHOD' => 'DELETE'));
        $this->assertTrue($this->request->isDelete());
    }

    /**
     * Test method is HEAD
     */
    public function testIsHead()
    {
        $this->initializeRequest(array('REQUEST_METHOD' => 'HEAD'));
        $this->assertTrue($this->request->isHead());
    }

    /**
     * Test method is OPTIONS
     */
    public function testIsOptions()
    {
        $this->initializeRequest(array('REQUEST_METHOD' => 'OPTIONS'));
        $this->assertTrue($this->request->isOptions());
    }

    /**
     * Test method is PATCH
     */
    public function testIsPatch()
    {
        $this->initializeRequest(array('REQUEST_METHOD' => 'PATCH'));
        $this->assertTrue($this->request->isPatch());
    }

    /**
     * Test is ajax with header
     */
    public function testIsAjaxWithHeader()
    {
        $this->initializeRequest(array('HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'));
        $this->assertTrue($this->request->isAjax());
        $this->assertTrue($this->request->isXhr());
    }

    /**
     * Test is ajax with query parameter
     */
    public function testIsAjaxWithQueryParameter()
    {
        $this->initializeRequest(array(
            'REQUEST_URI' => '/foo/bar?isajax=1&hello=world',
            'QUERY_STRING' => 'isajax=1&hello=world'
        ));
        $this->assertTrue($this->request->isAjax());
        $this->assertTrue($this->request->isXhr());
    }

    /**
     * Test is ajax without header or query parameter
     */
    public function testIsAjaxWithoutHeaderOrQueryParameter()
    {
        $this->assertFalse($this->request->isAjax());
        $this->assertFalse($this->request->isXhr());
    }

    /**
     * Test params from query string and request body
     */
    public function testParamsFromQueryStringAndRequestBody()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => 15,
            'REQUEST_URI' => '/foo/bar?one=1&two=2&three=3',
            'QUERY_STRING' => 'one=1&two=2&three=3'
        ), 'foo=bar&abc=123');
        $this->assertEquals(5, count($this->request->params()));
        $this->assertEquals('1', $this->request->params('one'));
        $this->assertEquals('2', $this->request->params('two'));
        $this->assertEquals('3', $this->request->params('three'));
        $this->assertEquals('bar', $this->request->params('foo'));
    }

    /**
     * Test fetch GET params
     */
    public function testGet()
    {
        $this->initializeRequest(array(
            'REQUEST_URI' => '/foo/bar?one=1&two=2&three=3',
            'QUERY_STRING' => 'one=1&two=2&three=3'
        ));
        $this->assertEquals(3, count($this->request->get()));
        $this->assertEquals('1', $this->request->get('one'));
        $this->assertNull($this->request->get('foo'));
        $this->assertFalse($this->request->get('foo', false));
    }

    /**
     * Test fetch POST params
     */
    public function testPost()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => 15,
            'REQUEST_URI' => '/foo/bar?one=1&two=2&three=3',
            'QUERY_STRING' => 'one=1&two=2&three=3'
        ), 'foo=bar&abc=123');
        $this->assertEquals(2, count($this->request->post()));
        $this->assertEquals('bar', $this->request->post('foo'));
        $this->assertNull($this->request->post('xyz'));
        $this->assertFalse($this->request->post('xyz', false));
    }

    /**
     * Test fetch PUT params
     */
    public function testPut()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'PUT',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => 15,
            'REQUEST_URI' => '/foo/bar?one=1&two=2&three=3',
            'QUERY_STRING' => 'one=1&two=2&three=3'
        ), 'foo=bar&abc=123');
        $this->assertEquals(2, count($this->request->put()));
        $this->assertEquals('bar', $this->request->put('foo'));
        $this->assertNull($this->request->put('xyz'));
        $this->assertFalse($this->request->put('xyz', false));
    }

    /**
     * Test fetch PATCH params
     */
    public function testPatch()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'PATCH',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => 15,
            'REQUEST_URI' => '/foo/bar?one=1&two=2&three=3',
            'QUERY_STRING' => 'one=1&two=2&three=3'
        ), 'foo=bar&abc=123');
        $this->assertEquals(2, count($this->request->patch()));
        $this->assertEquals('bar', $this->request->patch('foo'));
        $this->assertNull($this->request->patch('xyz'));
        $this->assertFalse($this->request->patch('xyz', false));
    }

    /**
     * Test fetch DELETE params
     */
    public function testDelete()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'DELETE',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => 15,
            'REQUEST_URI' => '/foo/bar?one=1&two=2&three=3',
            'QUERY_STRING' => 'one=1&two=2&three=3'
        ), 'foo=bar&abc=123');
        $this->assertEquals(2, count($this->request->delete()));
        $this->assertEquals('bar', $this->request->delete('foo'));
        $this->assertNull($this->request->delete('xyz'));
        $this->assertFalse($this->request->delete('xyz', false));
    }

    /**
     * Test is form data with specified content type
     */
    public function testIsFormDataWithSpecifiedContentType()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'PUT',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded'
        ), '');
        $this->assertTrue($this->request->isFormData());
    }

    /**
     * Test is form data with unspecified content type
     */
    public function testIsFormDataWithUnspecifiedContentType()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST'
        ), '');
        $this->assertTrue($this->request->isFormData());
    }

    /**
     * Test is form data with method override and unspecified content type
     */
    public function testIsFormDataWithMethodOverrideAndUnspecifiedContentType()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'HTTP_X_HTTP_METHOD_OVERRIDE' => 'PUT'
        ), '');
        $this->assertTrue($this->request->isPut());
        $this->assertTrue($this->request->isFormData());
    }

    /**
     * Test is NOT form data
     */
    public function testIsNotFormDataWithSpecifiedContentType()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json'
        ), '');
        $this->assertFalse($this->request->isFormData());
    }

    /**
     * Test get body
     */
    public function testGetBody()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => 15
        ), 'foo=bar&abc=123');
        $this->assertEquals('foo=bar&abc=123', $this->request->getBody());
    }

    /**
     * Test get body when it does not exist
     */
    public function testGetBodyWhenNotExists()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => 0
        ), null);
        $this->assertEquals('', $this->request->getBody());
    }

    /**
     * Test get content type
     */
    public function testGetContentType()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json; charset=utf-8',
            'CONTENT_LENGTH' => 14
        ), '{"foo": "bar"}');
        $this->assertEquals('application/json; charset=utf-8', $this->request->getContentType());
    }

    /**
     * Test get content type when it is not specified in the request
     */
    public function testGetContentTypeWhenNotExists()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_LENGTH' => 0
        ), '');
        $this->assertNull($this->request->getContentType());
    }

    /**
     * Test get media type
     */
    public function testGetMediaType()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 14
        ), '{"foo": "bar"}');
        $this->assertEquals('application/json', $this->request->getMediaType());
    }

    /**
     * Test get media type when it has associated params
     */
    public function testGetMediaTypeWhenThereAreAdditionalParams()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json; charset=utf-8',
            'CONTENT_LENGTH' => 14
        ), '{"foo": "bar"}');
        $this->assertEquals('application/json', $this->request->getMediaType());
    }

    /**
     * Test get media type when it is not specified in the request
     */
    public function testGetMediaTypeWhenNotExists()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_LENGTH' => 0
        ), '');
        $this->assertNull($this->request->getMediaType());
    }

    /**
     * Test get media type params
     */
    public function testGetMediaTypeParams()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json; charset=utf-8',
            'CONTENT_LENGTH' => 14
        ), '{"foo": "bar"}');
        $params = $this->request->getMediaTypeParams();
        $this->assertEquals(1, count($params));
        $this->assertEquals('utf-8', $params['charset']);
    }

    /**
     * Test get media type params when none is specified in the content-type header
     */
    public function testGetMediaTypeParamsWhenNotExists()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 14
        ), '{"foo": "bar"}');
        $this->assertEquals(array(), $this->request->getMediaTypeParams());
    }

    /**
     * Test get content charset
     */
    public function testGetContentCharset()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json; charset=ISO-8859-4',
            'CONTENT_LENGTH' => 14
        ), '{"foo": "bar"}');
        $this->assertEquals('ISO-8859-4', $this->request->getContentCharset());
    }

    /**
     * Test get content charset when none is specified in the content-type header
     */
    public function testGetContentCharsetWhenNotExists()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 14
        ), '{"foo": "bar"}');
        $this->assertNull($this->request->getContentCharset());
    }

    /**
     * Test get content length
     */
    public function testGetContentLength()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 14
        ), '{"foo": "bar"}');
        $this->assertEquals(14, $this->request->getContentLength());
    }

    /**
     * Test get content length when none is specified in the request header
     */
    public function testGetContentLengthWhenNotExists()
    {
        $this->initializeRequest(array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json',
        ), '');
        $this->assertEquals(0, $this->request->getContentLength());
    }

    /**
     * Test get host from Host header
     */
    public function testGetHost()
    {
        $this->initializeRequest(array(
            'HTTP_HOST' => 'slimframework.com',
            'SERVER_NAME' => 'example.com'
        ));
        $this->assertEquals('slimframework.com', $this->request->getHost()); // Prefers HTTP_HOST if available
    }

    /**
     * Test get host from server name if Host header is not specified
     */
    public function testGetHostFromServerName()
    {
        $this->initializeRequest(array(
            'HTTP_HOST' => 'slimframework.com',
            'SERVER_NAME' => 'example.com'
        ));
        $this->headers->remove('HTTP_HOST');
        $this->assertEquals('example.com', $this->request->getHost());
    }

    /**
     * Test get host when the Host header also includes port number
     */
    public function testGetHostAndRemovePort()
    {
        $this->initializeRequest(array(
            'HTTP_HOST' => 'slimframework.com:80' // <-- Some web servers will include port in the Host header
        ));
        $this->assertEquals('slimframework.com', $this->request->getHost());
    }

    /**
     * Test get host with port
     */
    public function testGetHostWithPort()
    {
        $this->initializeRequest(array(
            'HTTP_HOST' => 'slimframework.com',
            'SERVER_PORT' => 8080
        ));
        $this->assertEquals('slimframework.com:8080', $this->request->getHostWithPort());
    }

    /**
     * Test get host with port doesn't duplicate port numbers
     */
    public function testGetHostWithPortDoesNotDuplicatePort()
    {
        $this->initializeRequest(array(
            'HTTP_HOST' => 'slimframework.com:8080',
            'SERVER_PORT' => 8080
        ));
        $this->assertEquals('slimframework.com:8080', $this->request->getHostWithPort());
    }

    /**
     * Test get port
     */
    public function testGetPort()
    {
        $this->initializeRequest(array(
            'HTTP_HOST' => 'slimframework.com:8080',
            'SERVER_PORT' => 8080
        ));
        $this->assertEquals(8080, $this->request->getPort());
    }

    /**
     * Test get scheme
     */
    public function testGetSchemeIfHttp()
    {
        $this->assertEquals('http', $this->request->getScheme());
    }

    /**
     * Test get scheme when $_SERVER['HTTPS'] is empty value
     */
    public function testGetSchemeIfHttpWithEmptyServerVariable()
    {
        $this->initializeRequest(array(
            'HTTPS' => ''
        ));
        $this->assertEquals('http', $this->request->getScheme());
    }

    /**
     * Test get scheme when $_SERVER['HTTPS'] is "off"
     */
    public function testGetSchemeIfHttpWithOffServerVariable()
    {
        $this->initializeRequest(array(
            'HTTPS' => 'off'
        ));
        $this->assertEquals('http', $this->request->getScheme());
    }

    /**
     * Test get scheme when $_SERVER['HTTPS'] is empty value
     */
    public function testGetSchemeIfHttps()
    {
        $this->initializeRequest(array(
            'HTTPS' => '1'
        ));
        $this->assertEquals('https', $this->request->getScheme());
    }

    /**
     * Test get URL
     */
    public function testGetUrl()
    {
        $this->initializeRequest(array(
            'HTTP_HOST' => 'slimframework.com',
            'HTTPS' => '' // <-- Empty
        ), '');
        $this->assertEquals('http://slimframework.com', $this->request->getUrl());
    }

    /**
     * Test get URL with HTTPS scheme
     */
    public function testGetUrlWithHttps()
    {
        $this->initializeRequest(array(
            'HTTP_HOST' => 'slimframework.com',
            'HTTPS' => '1',
            'SERVER_PORT' => 443
        ), '');
        $this->assertEquals('https://slimframework.com', $this->request->getUrl());
    }

    /**
     * Test get URL with custom port
     */
    public function testGetUrlWithCustomPort()
    {
        $this->initializeRequest(array(
            'HTTP_HOST' => 'slimframework.com',
            'SERVER_PORT' => 8080
        ), '');
        $this->assertEquals('http://slimframework.com:8080', $this->request->getUrl());
    }

    /**
     * Test get query string
     */
    public function testGetQueryString()
    {
        $this->initializeRequest(array(
            'REQUEST_URI' => '/foo/bar?one=1&two=2&three=3',
            'QUERY_STRING' => 'one=1&two=2&three=3'
        ), '');
        $this->assertEquals('one=1&two=2&three=3', $this->request->getQueryString());
    }

    /**
     * Test get query string when it is not specified in the request
     */
    public function testGetQueryStringWhenNotExists()
    {
        $this->initializeRequest(array(
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => ''
        ), '');
        $this->environment->remove('QUERY_STRING');
        $this->assertEquals('', $this->request->getQueryString());
    }

    /**
     * Test get protocol
     */
    public function testGetProtocol()
    {
        $this->assertEquals('HTTP/1.1', $this->request->getProtocol());
    }

    /**
     * Test parses REMOTE_ADDR
     */
    public function testIp()
    {
        $this->initializeRequest(array(
            'REMOTE_ADDR' => '127.0.0.1'
        ), '');
        $this->assertEquals('127.0.0.1', $this->request->getIp());
    }

    /**
     * Test parses REMOTE_ADDR via CLIENT_IP
     */
    public function testIpViaClientIpHeader()
    {
        $this->initializeRequest(array(
            'REMOTE_ADDR' => '127.0.0.1',
            'CLIENT_IP' => '127.0.0.2'
        ), '');
        $this->assertEquals('127.0.0.2', $this->request->getIp());
    }

    /**
     * Test parses REMOTE_ADDR via X-FORWARDED-FOR
     */
    public function testIpViaForwardedForHeader()
    {
        $this->initializeRequest(array(
            'REMOTE_ADDR' => '127.0.0.1',
            'CLIENT_IP' => '127.0.0.2',
            'HTTP_X_FORWARDED_FOR' => '127.0.0.3'
        ));
        $this->assertEquals('127.0.0.3', $this->request->getIp());
    }

    /**
     * Test get referer
     */
    public function testGetReferrer()
    {
        $this->initializeRequest(array(
            'HTTP_REFERER' => 'http://foo.com'
        ));
        $this->assertEquals('http://foo.com', $this->request->getReferrer());
        $this->assertEquals('http://foo.com', $this->request->getReferer());
    }

    /**
     * Test get referer when the header is not in the request
     */
    public function testGetReferrerWhenNotExists()
    {
        $this->assertNull($this->request->getReferrer());
        $this->assertNull($this->request->getReferer());
    }

    /**
     * Test get user agent string
     */
    public function testGetUserAgent()
    {
        $this->initializeRequest(array(
            'HTTP_USER_AGENT' => 'ua-string'
        ));
        $this->assertEquals('ua-string', $this->request->getUserAgent());
    }

    /**
     * Test get user agent string when it is not in the request
     */
    public function testGetUserAgentWhenNotExists()
    {
        $this->initializeRequest(array(
            'HTTP_USER_AGENT' => 'ua-string'
        ));
        $this->headers->remove('HTTP_USER_AGENT');
        $this->assertNull($this->request->getUserAgent());
    }

    /**
     * Test parses paths without rewrite, in root directory
     */
    public function testParsesPathsWithoutUrlRewriteInRootDirectory()
    {
        $this->initializeRequest(array(
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/index.php/bar?abc=123',
            'QUERY_STRING' => 'abc=123'
        ));
        $this->assertEquals('/index.php', $this->request->getScriptName());
        $this->assertEquals('/bar', $this->request->getPathInfo());
        $this->assertEquals('/index.php/bar', $this->request->getPath());
    }

    /**
     * Test parses paths without rewrite, in subdirectory
     */
    public function testParsesPathsWithoutUrlRewriteInSubdirectory()
    {
        $this->initializeRequest(array(
            'SCRIPT_NAME' => '/foo/index.php',
            'REQUEST_URI' => '/foo/index.php/bar?abc=123',
            'QUERY_STRING' => 'abc=123'
        ));
        $this->assertEquals('/foo/index.php', $this->request->getScriptName());
        $this->assertEquals('/bar', $this->request->getPathInfo());
        $this->assertEquals('/foo/index.php/bar', $this->request->getPath());
    }

    /**
     * Test parses paths with rewrite, in root directory
     */
    public function testParsesPathsWithUrlRewriteInRootDirectory()
    {
        $this->initializeRequest(array(
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/bar?abc=123',
            'QUERY_STRING' => 'abc=123'
        ));
        $this->assertEquals('', $this->request->getScriptName());
        $this->assertEquals('/bar', $this->request->getPathInfo());
        $this->assertEquals('/bar', $this->request->getPath());
    }

    /**
     * Test parses paths with rewrite, in root directory, with base URL
     */
    public function testParsesPathsWithUrlRewriteInRootDirectoryWithBaseURL()
    {
        $this->initializeRequest(array(
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/?abc=123',
            'QUERY_STRING' => 'abc=123'
        ));
        $this->assertEquals('', $this->request->getScriptName());
        $this->assertEquals('/', $this->request->getPathInfo());
        $this->assertEquals('/', $this->request->getPath());
    }

    /**
     * Test parses paths with rewrite, in subdirectory
     */
    public function testParsesPathsWithUrlRewriteInSubdirectory()
    {
        $this->initializeRequest(array(
            'SCRIPT_NAME' => '/foo/index.php',
            'REQUEST_URI' => '/foo/bar?abc=123',
            'QUERY_STRING' => 'abc=123'
        ));
        $this->assertEquals('/foo', $this->request->getScriptName());
        $this->assertEquals('/bar', $this->request->getPathInfo());
        $this->assertEquals('/foo/bar', $this->request->getPath());
    }

    /**
     * Test parses path info and retains URL encoded characters (e.g. #)
     */
    public function testParsesPathInfoAndRetainsUrlEncodedCharacters()
    {
        $this->initializeRequest(array(
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/%23bar?abc=123', //<-- URL-encoded "#bar"
            'QUERY_STRING' => 'abc=123'
        ));
        $this->assertEquals('/foo/%23bar', $this->request->getPathInfo());
    }
}
