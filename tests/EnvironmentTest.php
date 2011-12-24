<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@joshlockhart.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.5.0
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
 *
 */

set_include_path(dirname(__FILE__) . '/../' . PATH_SEPARATOR . get_include_path());

require_once 'Slim/Environment.php';

class EnvironmentTest extends PHPUnit_Framework_TestCase {

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
        $_SERVER['SERVER_NAME'] = 'slim';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['SCRIPT_NAME'] = '/foo/index.php';
        $_SERVER['REQUEST_URI'] = '/foo/index.php/bar/xyz';
        $_SERVER['PATH_INFO'] = '/bar/xyz';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = 'one=1&two=2&three=3';
        $_SERVER['HTTPS'] = '';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        unset($_SERVER['CONTENT_TYPE'], $_SERVER['CONTENT_LENGTH']);
    }

    /**
     * Test private constructor
     */
    public function testPrivateConstructor() {
        $this->setExpectedException('RuntimeException');
        $env = new Slim_Environment();
    }

    /**
     * Test mock object
     */
    public function testMockEnvironment() {
        $mock = array(
            'REQUEST_METHOD' => 'PUT',
            'SCRIPT_NAME' => '/foo',
            'PATH_INFO' => '/bar',
            'QUERY_STRING' => '',
            'SERVER_NAME' => 'test.com',
            'SERVER_PORT' => 8080,
            'HTTP_HOST' => 'test.com',
            'slim.url_scheme' => 'http',
            'slim.input' => 'foo',
            'slim.errors' => fopen('php://stderr', 'w')
        );
        Slim_Environment::mock($mock);
        $env = Slim_Environment::getInstance();
        $this->assertEquals($mock, $env);
    }

    /**
     * Test sets HTTP method
     */
    public function testSetsHttpMethod() {
        $env = Slim_Environment::getInstance(true);
        $this->assertEquals('GET', $env['REQUEST_METHOD']);
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite is disabled;
     * App installed in subdirectory;
     */
    public function testParsesPathsWithoutUrlRewriteInSubdirectory() {
        $env = Slim_Environment::getInstance(true);
        $this->assertEquals('/bar/xyz', $env['PATH_INFO']);
        $this->assertEquals('/foo/index.php', $env['SCRIPT_NAME']);
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite is disabled;
     * App installed in root directory;
     */
    public function testParsesPathsWithoutUrlRewriteInRootDirectory() {
        $_SERVER['REQUEST_URI'] = '/index.php/bar/xyz';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $env = Slim_Environment::getInstance(true);
        $this->assertEquals('/bar/xyz', $env['PATH_INFO']);
        $this->assertEquals('/index.php', $env['SCRIPT_NAME']);
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite disabled;
     * App installed in root directory;
     * Requested resource is "/";
     */
    public function testParsesPathsWithoutUrlRewriteInRootDirectoryForAppRootUri() {
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        unset($_SERVER['PATH_INFO']);
        $env = Slim_Environment::getInstance(true);
        $this->assertEquals('/', $env['PATH_INFO']);
        $this->assertEquals('/index.php', $env['SCRIPT_NAME']);
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite enabled;
     * App installed in subdirectory;
     */
    public function testParsesPathsWithUrlRewriteInSubdirectory() {
        $_SERVER['SCRIPT_NAME'] = '/foo/index.php';
        $_SERVER['REQUEST_URI'] = '/foo/bar/xyz';
        unset($_SERVER['PATH_INFO']);
        $env = Slim_Environment::getInstance(true);
        $this->assertEquals('/bar/xyz', $env['PATH_INFO']);
        $this->assertEquals('/foo', $env['SCRIPT_NAME']);
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite enabled;
     * App installed in root directory;
     */
    public function testParsesPathsWithUrlRewriteInRootDirectory() {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/bar/xyz';
        unset($_SERVER['PATH_INFO']);
        $env = Slim_Environment::getInstance(true);
        $this->assertEquals('/bar/xyz', $env['PATH_INFO']);
        $this->assertEquals('', $env['SCRIPT_NAME']);
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite enabled;
     * App installed in root directory;
     * Requested resource is "/"
     */
    public function testParsesPathsWithUrlRewriteInRootDirectoryForAppRootUri() {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        unset($_SERVER['PATH_INFO']);
        $env = Slim_Environment::getInstance(true);
        $this->assertEquals('/', $env['PATH_INFO']);
        $this->assertEquals('', $env['SCRIPT_NAME']);
    }

    /**
     * Test parses query string
     *
     * Pre-conditions:
     * $_SERVER['QUERY_STRING'] exists and is not empty;
     */
    public function testParsesQueryString() {
        $env = Slim_Environment::getInstance(true);
        $this->assertEquals('one=1&two=2&three=3', $env['QUERY_STRING']);
    }

    /**
     * Test removes query string from PATH_INFO when using URL Rewrite
     *
     * Pre-conditions:
     * $_SERVER['QUERY_STRING'] exists and is not empty;
     * URL Rewrite enabled;
     */
    public function testRemovesQueryStringFromPathInfo() {
        $_SERVER['SCRIPT_NAME'] = '/foo/index.php';
        $_SERVER['REQUEST_URI'] = '/foo/bar/xyz?one=1&two=2&three=3';
        unset($_SERVER['PATH_INFO']);
        $env = Slim_Environment::getInstance(true);
        $this->assertEquals('/bar/xyz', $env['PATH_INFO']);
    }

    /**
     * Test parses query string
     *
     * Pre-conditions:
     * $_SERVER['QUERY_STRING'] does not exist;
     */
    public function testParsesQueryStringThatDoesNotExist() {
        unset($_SERVER['QUERY_STRING']);
        $env = Slim_Environment::getInstance(true);
        $this->assertEquals('', $env['QUERY_STRING']);
    }

    /**
     * Test SERVER_NAME is not empty
     */
    public function testServerNameIsNotEmpty() {
        $env = Slim_Environment::getInstance(true);
        $this->assertFalse(empty($env['SERVER_NAME']));
    }

    /**
     * Test SERVER_PORT is not empty
     */
    public function testServerPortIsNotEmpty() {
        $env = Slim_Environment::getInstance(true);
        $this->assertFalse(empty($env['SERVER_PORT']));
    }

    /**
     * Test unsets HTTP_CONTENT_TYPE and HTTP_CONTENT_LENGTH
     *
     * Pre-conditions:
     * HTTP_CONTENT_TYPE is sent with HTTP request;
     * HTTP_CONTENT_LENGTH is sent with HTTP request;
     */
    public function testUnsetsContentTypeAndContentLength() {
        $_SERVER['HTTP_CONTENT_TYPE'] = 'text/csv';
        $_SERVER['HTTP_CONTENT_LENGTH'] = 150;
        $env = Slim_Environment::getInstance(true);
        $this->assertNotContains('HTTP_CONTENT_TYPE', $env);
        $this->assertNotContains('HTTP_CONTENT_LENGTH', $env);
    }

    /**
     * Test unsets CONTENT_TYPE and CONTENT_LENGTH if they are empty
     *
     * Pre-conditions:
     * CONTENT_TYPE and CONTENT_LENGTH are sent in client HTTP request;
     * CONTENT_TYPE and CONTENT_LENGTH are empty;
     */
    public function testUnsetsEmptyContentTypeAndContentLength() {
        $_SERVER['CONTENT_TYPE'] = '';
        $_SERVER['CONTENT_LENGTH'] = '';
        $env = Slim_Environment::getInstance(true);
        $this->assertNotContains('CONTENT_TYPE', $env);
        $this->assertNotContains('CONTENT_LENGTH', $env);
    }

    /**
     * Test sets special request headers if not empty
     *
     * Pre-conditions:
     * CONTENT_TYPE, CONTENT_LENGTH, X_REQUESTED_WITH are sent in client HTTP request;
     * CONTENT_TYPE, CONTENT_LENGTH, X_REQUESTED_WITH are not empty;
     */
    public function testSetsSpecialHeaders() {
        $_SERVER['CONTENT_TYPE'] = 'text/csv';
        $_SERVER['CONTENT_LENGTH'] = '100';
        $_SERVER['X_REQUESTED_WITH'] = 'XmlHttpRequest';
        $env = Slim_Environment::getInstance(true);
        $this->assertEquals('text/csv', $env['CONTENT_TYPE']);
        $this->assertEquals('100', $env['CONTENT_LENGTH']);
        $this->assertEquals('XmlHttpRequest', $env['X_REQUESTED_WITH']);
    }

    /**
     * Test detects HTTPS
     *
     * Pre-conditions:
     * $_SERVER['HTTPS'] is set to a non-empty value;
     */
    public function testHttps() {
        $_SERVER['HTTPS'] = 1;
        $env = Slim_Environment::getInstance(true);
        $this->assertEquals('https', $env['slim.url_scheme']);
    }

    /**
     * Test detects not HTTPS
     *
     * Pre-conditions:
     * $_SERVER['HTTPS'] is set to an empty value;
     */
    public function testNotHttps() {
        $_SERVER['HTTPS'] = '';
        $env = Slim_Environment::getInstance(true);
        $this->assertEquals('http', $env['slim.url_scheme']);
    }

    /**
     * Test detects not HTTPS on IIS
     *
     * Pre-conditions:
     * $_SERVER['HTTPS'] is set to "off";
     */
    public function testNotHttpsIIS() {
        $_SERVER['HTTPS'] = 'off';
        $env = Slim_Environment::getInstance(true);
        $this->assertEquals('http', $env['slim.url_scheme']);
    }

    /**
     * Test input is an empty string (and not false)
     *
     * Pre-conditions:
     * Input at php://input may only be read once; subsequent attempts
     * will return `false`; in which case, use an empty string.
     */
    public function testInputIsEmptyString() {
        $env = Slim_Environment::getInstance(true);
        $this->assertEquals('', $env['slim.input']);
    }

    /**
     * Test valid resource handle to php://stdErr
     */
    public function testErrorResource() {
        $env = Slim_Environment::getInstance(true);
        $this->assertTrue(is_resource($env['slim.errors']));
    }
}
?>