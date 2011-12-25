<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.5.2
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

set_include_path(dirname(__FILE__) . '/../' . PATH_SEPARATOR . get_include_path());

require_once 'Slim/Environment.php';
require_once 'Slim/Http/Util.php';
require_once 'Slim/Http/Request.php';

class RequestTest extends PHPUnit_Framework_TestCase {
    /**
     * Default server settings assume the Slim app is installed
     * in a subdirectory `foo/` directly beneath the public document
     * root directory; URL rewrite is disabled; requested app
     * resource is GET `/bar/xyz` with three query params.
     *
     * These only provide a common baseline for the following
     * tests; tests are free to override these values.
     */
    public function setUp() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w')
        ));
    }

    /**
     * Test sets HTTP method
     */
    public function testGetMethod() {
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('GET', $req->getMethod());
    }

    /**
     * Test HTTP GET method detection
     */
    public function testIsGet() {
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertTrue($req->isGet());
        $this->assertFalse($req->isPost());
        $this->assertFalse($req->isPut());
        $this->assertFalse($req->isDelete());
        $this->assertFalse($req->isOptions());
        $this->assertFalse($req->isHead());
    }

    /**
     * Test HTTP POST method detection
     */
    public function testIsPost() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w')
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertFalse($req->isGet());
        $this->assertTrue($req->isPost());
        $this->assertFalse($req->isPut());
        $this->assertFalse($req->isDelete());
        $this->assertFalse($req->isOptions());
        $this->assertFalse($req->isHead());
    }

    /**
     * Test HTTP PUT method detection
     */
    public function testIsPut() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'PUT',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w')
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertFalse($req->isGet());
        $this->assertFalse($req->isPost());
        $this->assertTrue($req->isPut());
        $this->assertFalse($req->isDelete());
        $this->assertFalse($req->isOptions());
        $this->assertFalse($req->isHead());
    }

    /**
     * Test HTTP DELETE method detection
     */
    public function testIsDelete() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'DELETE',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w')
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertFalse($req->isGet());
        $this->assertFalse($req->isPost());
        $this->assertFalse($req->isPut());
        $this->assertTrue($req->isDelete());
        $this->assertFalse($req->isOptions());
        $this->assertFalse($req->isHead());
    }

    /**
     * Test HTTP OPTIONS method detection
     */
    public function testIsOptions() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'OPTIONS',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w')
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertFalse($req->isGet());
        $this->assertFalse($req->isPost());
        $this->assertFalse($req->isPut());
        $this->assertFalse($req->isDelete());
        $this->assertTrue($req->isOptions());
        $this->assertFalse($req->isHead());
    }

    /**
     * Test HTTP HEAD method detection
     */
    public function testIsHead() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'HEAD',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w')
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertFalse($req->isGet());
        $this->assertFalse($req->isPost());
        $this->assertFalse($req->isPut());
        $this->assertFalse($req->isDelete());
        $this->assertFalse($req->isOptions());
        $this->assertTrue($req->isHead());
    }

    /**
     * Test AJAX method detection w/ header
     */
    public function testIsAjaxWithHeader() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'X_REQUESTED_WITH' => 'XMLHttpRequest'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertTrue($req->isAjax());
        $this->assertTrue($req->isXhr());
    }

    /**
     * Test AJAX method detection w/ query parameter
     */
    public function testIsAjaxWithQueryParameter() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'HEAD',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3&isajax=1',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'X_REQUESTED_WITH' => 'XMLHttpRequest'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertTrue($req->isAjax());
        $this->assertTrue($req->isXhr());
    }

    /**
     * Test params from query string
     */
    public function testParamsFromQueryString() {
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals(3, count($req->params()));
        $this->assertEquals('1', $req->params('one'));
        $this->assertNull($req->params('foo'));
    }

    /**
     * Test params from request body
     */
    public function testParamsFromRequestBody() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => 'foo=bar&abc=123',
            'slim.errors' => fopen('php://stderr', 'w'),
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => 15
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals(5, count($req->params())); //Union of GET and POST
        $this->assertEquals('bar', $req->params('foo'));
    }

    /**
     * Test fetch GET params
     */
    public function testGet() {
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals(3, count($req->get()));
        $this->assertEquals('1', $req->get('one'));
        $this->assertNull($req->get('foo'));
    }

    /**
     * Test fetch GET params without multibyte
     */
    public function testGetWithoutMultibyte() {
        $env = Slim_Environment::getInstance();
        $env['slim.tests.ignore_multibyte'] = true;
        $req = new Slim_Http_Request($env);
        $this->assertEquals(3, count($req->get()));
        $this->assertEquals('1', $req->get('one'));
        $this->assertNull($req->get('foo'));
    }

    /**
     * Test fetch POST params
     */
    public function testPost() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => 'foo=bar&abc=123',
            'slim.errors' => fopen('php://stderr', 'w'),
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => 15
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals(2, count($req->post()));
        $this->assertEquals('bar', $req->post('foo'));
        $this->assertNull($req->post('xyz'));
    }

    /**
     * Test fetch POST params without multibyte
     */
    public function testPostWithoutMultibyte() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => 'foo=bar&abc=123',
            'slim.errors' => fopen('php://stderr', 'w'),
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => 15,
            'slim.tests.ignore_multibyte' => true
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals(2, count($req->post()));
        $this->assertEquals('bar', $req->post('foo'));
        $this->assertNull($req->post('xyz'));
    }

    /**
     * Test fetch POST without slim.input
     */
    public function testPostWithoutInput() {
        $this->setExpectedException('RuntimeException');
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.errors' => fopen('php://stderr', 'w'),
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => 15
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $req->post('foo');
    }

    /**
     * Test fetch PUT params
     */
    public function testPut() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'PUT',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => 'foo=bar&abc=123',
            'slim.errors' => fopen('php://stderr', 'w'),
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => 15
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals(2, count($req->put()));
        $this->assertEquals('bar', $req->put('foo'));
        $this->assertNull($req->put('xyz'));
    }

    /**
     * Test fetch COOKIE params
     */
    public function testCookies() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'HTTP_COOKIE' => 'foo=bar; abc=123'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals(2, count($req->cookies()));
        $this->assertEquals('bar', $req->cookies('foo'));
        $this->assertNull($req->cookies('xyz'));
    }

    /**
     * Test is form data
     */
    public function testIsFormData() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'PUT',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertTrue($req->isFormData());
    }

    /**
     * Test is not form data
     */
    public function testIsNotFormData() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'PUT',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'CONTENT_TYPE' => 'application/json'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertFalse($req->isFormData());
    }

    /**
     * Test headers
     */
    public function testHeaders() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'PUT',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'HTTP_ACCEPT_ENCODING' => 'gzip'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $headers = $req->headers();
        $this->assertTrue(is_array($headers));
        $this->assertArrayHasKey('HTTP_ACCEPT_ENCODING', $headers);
        $this->assertFalse(isset($headers['CONTENT_TYPE']));
        $this->assertEquals('gzip', $req->headers('HTTP_ACCEPT_ENCODING'));
        $this->assertEquals('gzip', $req->headers('HTTP-ACCEPT-ENCODING'));
        $this->assertEquals('gzip', $req->headers('http_accept_encoding'));
        $this->assertEquals('gzip', $req->headers('http-accept-encoding'));
        $this->assertEquals('gzip', $req->headers('ACCEPT_ENCODING'));
        $this->assertEquals('gzip', $req->headers('ACCEPT-ENCODING'));
        $this->assertEquals('gzip', $req->headers('accept_encoding'));
        $this->assertEquals('gzip', $req->headers('accept-encoding'));
        $this->assertNull($req->headers('foo'));
    }

    /**
     * Test get body
     */
    public function testGetBodyWhenExists() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'PUT',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => 'foo=bar&abc=123',
            'slim.errors' => fopen('php://stderr', 'w'),
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => 15
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('foo=bar&abc=123', $req->getBody());
    }

    /**
     * Test get body
     */
    public function testGetBodyWhenNotExists() {
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('', $req->getBody());
    }

    /**
     * Test get content type
     */
    public function testGetContentTypeWhenExists() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'PUT',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'CONTENT_TYPE' => 'application/json; charset=ISO-8859-4'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('application/json; charset=ISO-8859-4', $req->getContentType());
    }

    /**
     * Test get content type
     */
    public function testGetContentTypeWhenNotExists() {
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertNull($req->getContentType());
    }

    /**
     * Test get media type
     */
    public function testGetMediaTypeWhenExists() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'PUT',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'CONTENT_TYPE' => 'application/json; charset=ISO-8859-4'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('application/json', $req->getMediaType());
    }

    /**
     * Test get media type
     */
    public function testGetMediaTypeWhenNotExists() {
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertNull($req->getMediaType());
    }

    /**
     * Test get media type params
     */
    public function testGetMediaTypeParams() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'PUT',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'CONTENT_TYPE' => 'application/json; charset=ISO-8859-4'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $params = $req->getMediaTypeParams();
        $this->assertEquals(1, count($params));
        $this->assertArrayHasKey('charset', $params);
        $this->assertEquals('ISO-8859-4', $params['charset']);
    }

    /**
     * Test get media type params
     */
    public function testGetMediaTypeParamsWhenNotExists() {
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $params = $req->getMediaTypeParams();
        $this->assertTrue(is_array($params));
        $this->assertEquals(0, count($params));
    }

    /**
     * Test get content charset
     */
    public function testGetContentCharset() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'PUT',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'CONTENT_TYPE' => 'application/json; charset=ISO-8859-4'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('ISO-8859-4', $req->getContentCharset());
    }

    /**
     * Test get content charset
     */
    public function testGetContentCharsetWhenNotExists() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'PUT',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'CONTENT_TYPE' => 'application/json'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertNull($req->getContentCharset());
    }

    /**
     * Test get content length
     */
    public function testGetContentLength() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'PUT',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => 'foo=bar&abc=123',
            'slim.errors' => fopen('php://stderr', 'w'),
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => 15
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals(15, $req->getContentLength());
    }

    /**
     * Test get content length
     */
    public function testGetContentLengthWhenNotExists() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'PUT',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w')
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals(0, $req->getContentLength());
    }

    /**
     * Test get host
     */
    public function testGetHost() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'HTTP_HOST' => 'slimframework.com'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('slimframework.com', $req->getHost()); //Uses HTTP_HOST if available
    }

    /**
     * Test get host
     */
    public function testGetHostWhenNotExists() {
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('slim', $req->getHost()); //Uses SERVER_NAME as backup
    }

    /**
     * Test get host with port
     */
    public function testGetHostWithPort() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'HTTP_HOST' => 'slimframework.com'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('slimframework.com:80', $req->getHostWithPort());
    }

    /**
     * Test get port
     */
    public function testGetPort() {
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertTrue(is_integer($req->getPort()));
        $this->assertEquals(80, $req->getPort());
    }

    /**
     * Test get scheme
     */
    public function testGetSchemeIfHttp() {
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('http', $req->getScheme());
    }

    /**
     * Test get scheme
     */
    public function testGetSchemeIfHttps() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'https',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w')
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('https', $req->getScheme());
    }

    /**
     * Test get [script name, root uri, path, path info, resource uri] in subdirectory without htaccess
     */
    public function testAppPathsInSubdirectoryWithoutHtaccess() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w')
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('/foo/index.php', $req->getScriptName());
        $this->assertEquals('/foo/index.php', $req->getRootUri());
        $this->assertEquals('/foo/index.php/bar/xyz', $req->getPath());
        $this->assertEquals('/bar/xyz', $req->getPathInfo());
        $this->assertEquals('/bar/xyz', $req->getResourceUri());
    }

    /**
     * Test get [script name, root uri, path, path info, resource uri] in subdirectory with htaccess
     */
    public function testAppPathsInSubdirectoryWithHtaccess() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w')
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('/foo', $req->getScriptName());
        $this->assertEquals('/foo', $req->getRootUri());
        $this->assertEquals('/foo/bar/xyz', $req->getPath());
        $this->assertEquals('/bar/xyz', $req->getPathInfo());
        $this->assertEquals('/bar/xyz', $req->getResourceUri());
    }

    /**
     * Test get [script name, root uri, path, path info, resource uri] in root directory without htaccess
     */
    public function testAppPathsInRootDirectoryWithoutHtaccess() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w')
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('/index.php', $req->getScriptName());
        $this->assertEquals('/index.php', $req->getRootUri());
        $this->assertEquals('/index.php/bar/xyz', $req->getPath());
        $this->assertEquals('/bar/xyz', $req->getPathInfo());
        $this->assertEquals('/bar/xyz', $req->getResourceUri());
    }

    /**
     * Test get [script name, root uri, path, path info, resource uri] in root directory with htaccess
     */
    public function testAppPathsInRootDirectoryWithHtaccess() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w')
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('', $req->getScriptName());
        $this->assertEquals('', $req->getRootUri());
        $this->assertEquals('/bar/xyz', $req->getPath());
        $this->assertEquals('/bar/xyz', $req->getPathInfo());
        $this->assertEquals('/bar/xyz', $req->getResourceUri());
    }

    /**
     * Test get URL
     */
    public function testGetUrl() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'HTTP_HOST' => 'slimframework.com'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('http://slimframework.com', $req->getUrl());
    }

    /**
     * Test get URL
     */
    public function testGetUrlWithCustomPort() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 8080,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'HTTP_HOST' => 'slimframework.com'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('http://slimframework.com:8080', $req->getUrl());
    }

    /**
     * Test get URL
     */
    public function testGetUrlWithHttps() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 443,
            'slim.url_scheme' => 'https',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'HTTP_HOST' => 'slimframework.com'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('https://slimframework.com', $req->getUrl());
    }

    /**
     * Test get IP
     */
    public function testGetIp() {
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('127.0.0.1', $req->getIp());
    }

    /**
     * Test get refererer
     */
    public function testGetReferrer() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'HTTP_REFERER' => 'http://slimframework.com/point/of/origin'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('http://slimframework.com/point/of/origin', $req->getReferrer());
        $this->assertEquals('http://slimframework.com/point/of/origin', $req->getReferer());
    }

    /**
     * Test get refererer
     */
    public function testGetReferrerWhenNotExists() {
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertNull($req->getReferrer());
        $this->assertNull($req->getReferer());
    }

    /**
     * Test get refererer
     */
    public function testGetUserAgent() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar/xyz', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'HTTP_USER_AGENT' => 'user-agent-string'
        ));
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertEquals('user-agent-string', $req->getUserAgent());
    }

    /**
     * Test get refererer
     */
    public function testGetUserAgentWhenNotExists() {
        $env = Slim_Environment::getInstance();
        $req = new Slim_Http_Request($env);
        $this->assertNull($req->getUserAgent());
    }
}