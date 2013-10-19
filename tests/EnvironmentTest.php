<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.3
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

class EnvironmentTest extends PHPUnit_Framework_TestCase
{
    /**
     * Default server settings assume the Slim app is installed
     * in a subdirectory `foo/` directly beneath the public document
     * root directory; URL rewrite is disabled; requested app
     * resource is GET `/bar/xyz` with three query params.
     *
     * These only provide a common baseline for the following
     * tests; tests are free to override these values.
     */
    public function setUp()
    {
        $_SERVER['DOCUMENT_ROOT'] = '/var/www';
        $_SERVER['SCRIPT_FILENAME'] = '/var/www/foo/index.php';
        $_SERVER['REQUEST_URI'] = '/foo/index.php/bar/xyz';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['SERVER_NAME'] = 'slim';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = 'one=1&two=2&three=3';
        $_SERVER['HTTPS'] = '';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        unset($_SERVER['CONTENT_TYPE'], $_SERVER['CONTENT_LENGTH']);
    }

    /**
     * Test mock environment
     *
     * This should return the custom values where specified
     * and the default values otherwise.
     */
    public function testMockEnvironment()
    {
        $env = \Slim\Environment::mock(array(
            'REQUEST_METHOD' => 'PUT'
        ));
        $this->assertInstanceOf('\Slim\Environment', $env);
        $this->assertEquals('PUT', $env->get('REQUEST_METHOD'));
        $this->assertEquals(80, $env->get('SERVER_PORT'));
        $this->assertNull($env->get('foo'));
    }

    /**
     * Test sets HTTP method
     */
    public function testSetsHttpMethod()
    {
        $env = new \Slim\Environment();
        $this->assertEquals('GET', $env->get('REQUEST_METHOD'));
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite is disabled;
     * App installed in subdirectory;
     *
     * @dataProvider documentRootDataProvider
     */
    public function testParsesPathsWithoutUrlRewriteInSubdirectory($documentRoot)
    {
        $_SERVER['DOCUMENT_ROOT'] = $documentRoot;
        $env = new \Slim\Environment();
        $this->assertEquals('/bar/xyz', $env->get('PATH_INFO'));
        $this->assertEquals('/foo/index.php', $env->get('SCRIPT_NAME'));
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite is disabled;
     * App installed in root directory;
     *
     * @dataProvider documentRootDataProvider
     */
    public function testParsesPathsWithoutUrlRewriteInRootDirectory($documentRoot)
    {
        $_SERVER['DOCUMENT_ROOT'] = $documentRoot;
        $_SERVER['SCRIPT_FILENAME'] = '/var/www/index.php';
        $_SERVER['REQUEST_URI'] = '/index.php/bar/xyz';
        $env = new \Slim\Environment();
        $this->assertEquals('/bar/xyz', $env->get('PATH_INFO'));
        $this->assertEquals('/index.php', $env->get('SCRIPT_NAME'));
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite disabled;
     * App installed in root directory;
     * Requested resource is "/";
     *
     * @dataProvider documentRootDataProvider
     */
    public function testParsesPathsWithoutUrlRewriteInRootDirectoryForAppRootUri($documentRoot)
    {
        $_SERVER['DOCUMENT_ROOT'] = $documentRoot;
        $_SERVER['SCRIPT_FILENAME'] = '/var/www/index.php';
        $_SERVER['REQUEST_URI'] = '/index.php';
        unset($_SERVER['PATH_INFO']);
        $env = new \Slim\Environment();
        $this->assertEquals('/', $env->get('PATH_INFO'));
        $this->assertEquals('/index.php', $env->get('SCRIPT_NAME'));
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite enabled;
     * App installed in subdirectory;
     *
     * @dataProvider documentRootDataProvider
     */
    public function testParsesPathsWithUrlRewriteInSubdirectory($documentRoot)
    {
        $_SERVER['DOCUMENT_ROOT'] = $documentRoot;
        $_SERVER['SCRIPT_NAME'] = '/foo/index.php';
        $_SERVER['REQUEST_URI'] = '/foo/bar/xyz';
        unset($_SERVER['PATH_INFO']);
        $env = new \Slim\Environment();
        $this->assertEquals('/bar/xyz', $env->get('PATH_INFO'));
        $this->assertEquals('/foo', $env->get('SCRIPT_NAME'));
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite enabled;
     * App installed in root directory;
     *
     * @dataProvider documentRootDataProvider
     */
    public function testParsesPathsWithUrlRewriteInRootDirectory($documentRoot)
    {
        $_SERVER['DOCUMENT_ROOT'] = $documentRoot;
        $_SERVER['SCRIPT_FILENAME'] = '/var/www/index.php';
        $_SERVER['REQUEST_URI'] = '/bar/xyz';
        unset($_SERVER['PATH_INFO']);
        $env = new \Slim\Environment();
        $this->assertEquals('/bar/xyz', $env->get('PATH_INFO'));
        $this->assertEquals('', $env->get('SCRIPT_NAME'));
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite enabled;
     * App installed in root directory;
     * Requested resource is "/"
     *
     * @dataProvider documentRootDataProvider
     */
    public function testParsesPathsWithUrlRewriteInRootDirectoryForAppRootUri($documentRoot)
    {
        $_SERVER['DOCUMENT_ROOT'] = $documentRoot;
        $_SERVER['SCRIPT_FILENAME'] = '/var/www/index.php';
        $_SERVER['REQUEST_URI'] = '/';
        unset($_SERVER['PATH_INFO']);
        $env = new \Slim\Environment();
        $this->assertEquals('/', $env->get('PATH_INFO'));
        $this->assertEquals('', $env->get('SCRIPT_NAME'));
    }

    /**
     * Test parses query string
     *
     * Pre-conditions:
     * $_SERVER['QUERY_STRING'] exists and is not empty;
     */
    public function testParsesQueryString()
    {
        $env = new \Slim\Environment();
        $this->assertEquals('one=1&two=2&three=3', $env->get('QUERY_STRING'));
    }

    /**
     * Test removes query string from PATH_INFO when using URL Rewrite
     *
     * Pre-conditions:
     * $_SERVER['QUERY_STRING'] exists and is not empty;
     * URL Rewrite enabled;
     */
    public function testRemovesQueryStringFromPathInfo()
    {
        $_SERVER['SCRIPT_NAME'] = '/foo/index.php';
        $_SERVER['REQUEST_URI'] = '/foo/bar/xyz?one=1&two=2&three=3';
        unset($_SERVER['PATH_INFO']);
        $env = new \Slim\Environment();
        $this->assertEquals('/bar/xyz', $env->get('PATH_INFO'));
    }

    /**
     * Test environment's PATH_INFO retains URL encoded characters (e.g. #)
     *
     * In earlier version, \Slim\Environment would use PATH_INFO instead
     * of REQUEST_URI to determine the root URI and resource URI.
     * Unfortunately, the server would URL decode the PATH_INFO string
     * before it was handed to PHP. This prevented certain URL-encoded
     * characters like the octothorpe from being delivered correctly to
     * the Slim application environment. This test ensures the
     * REQUEST_URI is used instead and parsed as expected.
     */
    public function testPathInfoRetainsUrlEncodedCharacters()
    {
        $_SERVER['SCRIPT_FILENAME'] = '/var/www/index.php';
        $_SERVER['REQUEST_URI'] = '/foo/%23bar'; //<-- URL-encoded "#bar"
        $env = new \Slim\Environment();
        $this->assertEquals('/foo/%23bar', $env->get('PATH_INFO'));
    }

    /**
     * Test parses query string
     *
     * Pre-conditions:
     * $_SERVER['QUERY_STRING'] does not exist;
     */
    public function testParsesQueryStringThatDoesNotExist()
    {
        unset($_SERVER['QUERY_STRING']);
        $env = new \Slim\Environment();
        $this->assertEquals('', $env->get('QUERY_STRING'));
    }

    /**
     * Test SERVER_NAME is not empty
     */
    public function testServerNameIsNotEmpty()
    {
        $env = new \Slim\Environment();
        $serverName = $env->get('SERVER_NAME');
        $this->assertTrue(!is_null($serverName));
    }

    /**
     * Test SERVER_PORT is not empty
     */
    public function testServerPortIsNotEmpty()
    {
        $env = new \Slim\Environment();
        $serverPort = $env->get('SERVER_PORT');
        $this->assertTrue(!is_null($serverPort));
    }

    /**
     * Test unsets HTTP_CONTENT_TYPE and HTTP_CONTENT_LENGTH
     *
     * Pre-conditions:
     * HTTP_CONTENT_TYPE is sent with HTTP request;
     * HTTP_CONTENT_LENGTH is sent with HTTP request;
     */
    public function testUnsetsContentTypeAndContentLength()
    {
        $_SERVER['HTTP_CONTENT_TYPE'] = 'text/csv';
        $_SERVER['HTTP_CONTENT_LENGTH'] = 150;
        $env = new \Slim\Environment();
        $this->assertNull($env->get('HTTP_CONTENT_TYPE'));
        $this->assertNull($env->get('HTTP_CONTENT_LENGTH'));
    }

    /**
     * Test sets special request headers if not empty
     *
     * Pre-conditions:
     * CONTENT_TYPE, CONTENT_LENGTH, X_REQUESTED_WITH are sent in client HTTP request;
     * CONTENT_TYPE, CONTENT_LENGTH, X_REQUESTED_WITH are not empty;
     */
    public function testSetsSpecialHeaders()
    {
        $_SERVER['CONTENT_TYPE'] = 'text/csv';
        $_SERVER['CONTENT_LENGTH'] = '100';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XmlHttpRequest';
        $env = new \Slim\Environment();
        $this->assertEquals('text/csv', $env->get('CONTENT_TYPE'));
        $this->assertEquals('100', $env->get('CONTENT_LENGTH'));
        $this->assertEquals('XmlHttpRequest', $env->get('HTTP_X_REQUESTED_WITH'));
    }

    /**
     * Tests X-HTTP-Method-Override is allowed through unmolested.
     *
     * Pre-conditions:
     * X_HTTP_METHOD_OVERRIDE is sent in client HTTP request;
     * X_HTTP_METHOD_OVERRIDE is not empty;
     */
    public function testSetsHttpMethodOverrideHeader() {
        $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'DELETE';
        $env = new \Slim\Environment();
        $this->assertEquals('DELETE', $env->get('HTTP_X_HTTP_METHOD_OVERRIDE'));
    }

    /**
     * Test detects HTTPS
     *
     * Pre-conditions:
     * $_SERVER['HTTPS'] is set to a non-empty value;
     */
    public function testHttps()
    {
        $_SERVER['HTTPS'] = 1;
        $env = new \Slim\Environment();
        $this->assertEquals('https', $env->get('slim.url_scheme'));
    }

    /**
     * Test detects not HTTPS
     *
     * Pre-conditions:
     * $_SERVER['HTTPS'] is set to an empty value;
     */
    public function testNotHttps()
    {
        $_SERVER['HTTPS'] = '';
        $env = new \Slim\Environment();
        $this->assertEquals('http', $env->get('slim.url_scheme'));
    }

    /**
     * Test detects not HTTPS on IIS
     *
     * Pre-conditions:
     * $_SERVER['HTTPS'] is set to "off";
     */
    public function testNotHttpsIIS()
    {
        $_SERVER['HTTPS'] = 'off';
        $env = new \Slim\Environment();
        $this->assertEquals('http', $env->get('slim.url_scheme'));
    }

    /**
     * Test input is an empty string (and not false)
     *
     * Pre-conditions:
     * Input at php://input may only be read once; subsequent attempts
     * will return `false`; in which case, use an empty string.
     */
    public function testInputIsEmptyString()
    {
        $env = new \Slim\Environment();
        $this->assertEquals('', $env->get('slim.input'));
    }

    /**
     * Provides properly and improperly configured Apache DocumentRoot
     *
     * Per Apache docs, "DocumentRoot should be specified without a trailing slash"
     * @link http://httpd.apache.org/docs/2.2/mod/core.html#documentroot
     */
    public function documentRootDataProvider()
    {
        return array(
            array('/var/www'),
            array('/var/www/'),
        );
    }
}
