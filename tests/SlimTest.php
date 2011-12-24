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
 */

set_include_path(dirname(__FILE__) . '/../' . PATH_SEPARATOR . get_include_path());

require_once 'Slim/Slim.php';
require_once 'Slim/Middleware/Flash.php';

//Register non-Slim autoloader
function customAutoLoader( $class ) {
    $file = rtrim(dirname(__FILE__), '/') . '/' . $class . '.php';
    if ( file_exists($file) ) {
        require $file;
    } else {
        return;
    }
}
spl_autoload_register('customAutoLoader');

//Mock custom view
class CustomView extends Slim_View {
    function render($template) { echo "Custom view"; }
}

//Mock middleware
class CustomMiddleware {
    protected $app;
    public function __construct( $app, $settings = array() ) {
        $this->app = $app;
    }
    public function call( &$env ) {
        $env['slim.test'] = 'Hello';
        list($status, $header, $body) = $this->app->call($env);
        $header['X-Slim-Test'] = 'Hello';
        $body = $body . 'Hello';
        return array($status, $header, $body);
    }
}

class SlimTest extends PHPUnit_Extensions_OutputTestCase {

    public function setUp() {
        //Remove environment mode if set
        unset($_ENV['SLIM_MODE']);

        //Prepare default environment variables
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w')
        ));

        //Reset session
        if ( session_id() ) {
            session_unset();
            session_destroy();
            $_SESSION = array();
        }
        session_start();
    }

    /************************************************
     * INSTANTIATION
     ************************************************/

    /**
     * Test default instance properties
     */
    public function testDefaultInstanceProperties() {
        $s = new Slim();
        $this->assertInstanceOf('Slim_Http_Request', $s->request());
        $this->assertInstanceOf('Slim_Http_Response', $s->response());
        $this->assertInstanceOf('Slim_Router', $s->router());
        $this->assertInstanceOf('Slim_View', $s->view());
        $this->assertTrue(is_array($s->environment()));
    }

    /**
     * Test get default instance
     */
    public function testGetDefaultInstance() {
        $s = new Slim();
        $s->setName('default'); //We must do this manually since a default app is already set in prev tests
        $this->assertEquals('default', $s->getName());
        $this->assertInstanceOf('Slim', Slim::getInstance());
        $this->assertSame($s, Slim::getInstance());
    }

    /**
     * Test get named instance
     */
    public function testGetNamedInstance() {
        $s = new Slim();
        $s->setName('foo');
        $this->assertSame($s, Slim::getInstance('foo'));
    }

    /**
     * Test Slim autoloader ignores non-Slim classes
     *
     * Pre-conditions:
     * Instantiate a non-Slim class;
     *
     * Post-conditions:
     * Slim autoloader returns without requiring a class file;
     */
    public function testSlimAutoloaderIgnoresNonSlimClass() {
        $foo = new Foo();
    }

    /************************************************
     * SETTINGS
     ************************************************/

    /**
     * Test get setting that exists
     */
    public function testGetSettingThatExists() {
        $s = new Slim();
        $this->assertEquals('./templates', $s->config('templates.path'));
    }

    /**
     * Test get setting that does not exist
     */
    public function testGetSettingThatDoesNotExist() {
        $s = new Slim();
        $this->assertNull($s->config('foo'));
    }

    /**
     * Test set setting
     */
    public function testSetSetting() {
        $s = new Slim();
        $this->assertEquals('./templates', $s->config('templates.path'));
        $s->config('templates.path', './tmpl');
        $this->assertEquals('./tmpl', $s->config('templates.path'));
    }

    /**
     * Test batch set settings
     */
    public function testBatchSetSettings() {
        $s = new Slim();
        $this->assertEquals('./templates', $s->config('templates.path'));
        $this->assertTrue($s->config('debug'));
        $s->config(array(
            'templates.path' => './tmpl',
            'debug' => false
        ));
        $this->assertEquals('./tmpl', $s->config('templates.path'));
        $this->assertFalse($s->config('debug'));
    }

    /************************************************
     * MODES
     ************************************************/

    /**
     * Test default mode
     */
    public function testGetDefaultMode() {
        $s = new Slim();
        $this->assertEquals('development', $s->getMode());
    }

    /**
     * Test custom mode from environment
     */
    public function testGetModeFromEnvironment() {
        $_ENV['SLIM_MODE'] = 'production';
        $s = new Slim();
        $this->assertEquals('production', $s->getMode());
    }

    /**
     * Test custom mode from app settings
     */
    public function testGetModeFromSettings() {
        $s = new Slim(array(
            'mode' => 'test'
        ));
        $this->assertEquals('test', $s->getMode());
    }

    /**
     * Test mode configuration
     */
    public function testModeConfiguration() {
        $flag = 0;
        $configureTest = function () use (&$flag) {
            $flag = 'test';
        };
        $configureProduction = function () use (&$flag) {
            $flag = 'production';
        };
        $s = new Slim(array('mode' => 'test'));
        $s->configureMode('test', $configureTest);
        $s->configureMode('production', $configureProduction);
        $this->assertEquals('test', $flag);
    }

    /**
     * Test mode configuration when mode does not match
     */
    public function testModeConfigurationWhenModeDoesNotMatch() {
        $flag = 0;
        $configureTest = function () use (&$flag) {
            $flag = 'test';
        };
        $s = new Slim(array('mode' => 'production'));
        $s->configureMode('test', $configureTest);
        $this->assertEquals(0, $flag);
    }

    /**
     * Test mode configuration when not callable
     */
    public function testModeConfigurationWhenNotCallable() {
        $flag = 0;
        $s = new Slim(array('mode' => 'production'));
        $s->configureMode('production', 'foo');
        $this->assertEquals(0, $flag);
    }

    /************************************************
     * ROUTING
     ************************************************/

    /**
     * Test GENERIC route
     */
    public function testGenericRoute() {
        $s = new Slim();
        $callable = function () { echo "foo"; };
        $route = $s->map('/bar', $callable);
        $this->assertInstanceOf('Slim_Route', $route);
        $this->assertEmpty($route->getHttpMethods());
    }

    /**
     * Test GET route
     */
    public function testGetRoute() {
        $s = new Slim();
        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "xyz"; };
        $route = $s->get('/bar', $mw1, $mw2, $callable);
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals('foobarxyz', $body);
        $this->assertEquals('/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test POST route
     */
    public function testPostRoute() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "xyz"; };
        $route = $s->post('/bar', $mw1, $mw2, $callable);
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals('foobarxyz', $body);
        $this->assertEquals('/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test PUT route
     */
    public function testPutRoute() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'PUT',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "xyz"; };
        $route = $s->put('/bar', $mw1, $mw2, $callable);
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals('foobarxyz', $body);
        $this->assertEquals('/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test DELETE route
     */
    public function testDeleteRoute() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'DELETE',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "xyz"; };
        $route = $s->delete('/bar', $mw1, $mw2, $callable);
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals('foobarxyz', $body);
        $this->assertEquals('/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test OPTIONS route
     */
    public function testOptionsRoute() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'OPTIONS',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "xyz"; };
        $route = $s->options('/bar', $mw1, $mw2, $callable);
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals('foobarxyz', $body);
        $this->assertEquals('/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test if route expects trailing slash and URL does not have one
     */
    public function testRouteWithSlashAndUrlWithout() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $s->get('/bar/', function () { echo "xyz"; });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals(301, $status);
    }

    /**
     * Test 405 Method Not Allowed
     */
    public function testMethodNotAllowed() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $s->get('/bar', function () { echo "xyz"; });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals(405, $status);
    }

    /**
     * Test if route does NOT expect trailing slash and URL has one
     */
    public function testRouteWithoutSlashAndUrlWithOne() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar/', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $s->get('/bar', function () { echo "xyz"; });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals(404, $status);
    }

    /**
     * Test if route contains URL encoded characters
     */
    public function testRouteWithUrlEncodedCharacters() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar/jo%20hn/smi%20th', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $s->get('/bar/:one/:two', function ($one, $two) { echo $one . $two; });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals('jo hnsmi th', $body);
    }

    /************************************************
     * VIEW
     ************************************************/

    /**
     * Test set view with string class name
     */
    public function testSetSlimViewFromString() {
        $s = new Slim();
        $this->assertInstanceOf('Slim_View', $s->view());
        $s->view('CustomView');
        $this->assertInstanceOf('CustomView', $s->view());
    }

    /**
     * Test set view with object instance
     */
    public function testSetSlimViewFromInstance() {
        $s = new Slim();
        $this->assertInstanceOf('Slim_View', $s->view());
        $s->view(new CustomView());
        $this->assertInstanceOf('CustomView', $s->view());
    }

    /**
     * Test view data is transferred to newer view
     */
    public function testViewDataTransfer() {
        $data = array('foo' => 'bar');
        $s = new Slim();
        $s->view()->setData($data);
        $s->view('CustomView');
        $this->assertSame($data, $s->view()->getData());
    }

    /************************************************
     * RENDERING
     ************************************************/

    /**
     * Test render with template and data
     */
    public function testRenderTemplateWithData() {
        $s = new Slim(array('templates.path' => dirname(__FILE__) . '/templates'));
        $s->get('/bar', function () use ($s) {
            $s->render('test.php', array('foo' => 'bar'));
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals(200, $status);
        $this->assertEquals('test output bar', $body);
    }

    /**
     * Test render with template and data and status
     */
    public function testRenderTemplateWithDataAndStatus() {
        $s = new Slim(array('templates.path' => dirname(__FILE__) . '/templates'));
        $s->get('/bar', function () use ($s) {
            $s->render('test.php', array('foo' => 'bar'), 500);
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals(500, $status);
        $this->assertEquals('test output bar', $body);
    }

    /************************************************
     * LOG
     ************************************************/

    /**
     * Test get log
     *
     * This asserts that a Slim app has a default Log
     * upon instantiation. The Log itself is tested 
     * separately in another file.
     */
    public function testGetLog() {
        $s = new Slim();
        $this->assertInstanceOf('Slim_Log', $s->getLog());
    }

    /************************************************
     * HTTP CACHING
     ************************************************/

    /**
     * Test Last-Modified match
     */
    public function testLastModifiedMatch() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'HTTP_IF_MODIFIED_SINCE' => 'Sun, 03 Oct 2010 17:00:52 -0400',
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $s->get('/bar', function () use ($s) {
            $s->lastModified(1286139652);
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals(304, $status);
    }

    /**
     * Test Last-Modified match
     */
    public function testLastModifiedDoesNotMatch() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'HTTP_IF_MODIFIED_SINCE' => 'Sun, 03 Oct 2010 17:00:52 -0400',
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $s->get('/bar', function () use ($s) {
            $s->lastModified(1286139250);
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals(200, $status);
    }

    public function testLastModifiedOnlyAcceptsIntegers(){
        $this->setExpectedException('InvalidArgumentException');
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'HTTP_IF_MODIFIED_SINCE' => 'Sun, 03 Oct 2010 17:00:52 -0400',
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $s->get('/bar', function () use ($s) {
            $s->lastModified('Test');
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
    }

    /**
     * Test ETag matches
     */
    public function testEtagMatches() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'HTTP_IF_NONE_MATCH' => '"abc123"',
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $s->get('/bar', function () use ($s) {
            $s->etag('abc123');
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals(304, $s->response()->status());
    }

    /**
     * Test ETag does not match
     */
    public function testEtagDoesNotMatch() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'HTTP_IF_NONE_MATCH' => '"abc1234"',
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $s->get('/bar', function () use ($s) {
            $s->etag('abc123');
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals(200, $s->response()->status());
    }

    /**
     * Test ETag with invalid type
     */
    public function testETagWithInvalidType(){
        $this->setExpectedException('InvalidArgumentException');
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'HTTP_IF_NONE_MATCH' => '"abc1234"',
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $s->get('/bar', function () use ($s) {
            $s->etag('123','foo');
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
    }

    /************************************************
     * COOKIES
     ************************************************/

    /**
     * Set cookie
     *
     * This tests that the Slim application instance sets
     * a cookie in the HTTP response header. This does NOT
     * test the implementation of setting the cookie; that is
     * tested in a separate file.
     */
    public function testSetCookie() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $s->get('/bar', function () use ($s) {
            $s->setCookie('foo', 'bar', '2 days');
            $s->setCookie('foo1', 'bar1', '2 days');
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $cookies = explode("\n", $header['Set-Cookie']);
        $this->assertEquals(2, count($cookies));
        $this->assertEquals(1, preg_match('@foo=bar@', $cookies[0]));
        $this->assertEquals(1, preg_match('@foo1=bar1@', $cookies[1]));
    }

    /**
     * Test get cookie
     *
     * This method ensures that the `Cookie:` HTTP request
     * header is parsed if present, and made accessible via the
     * Request object.
     */
    public function testGetCookie() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'HTTP_COOKIE' => 'foo=bar; foo2=bar2',
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $this->assertEquals('bar', $s->getCookie('foo'));
        $this->assertEquals('bar2', $s->getCookie('foo2'));
    }

    /**
     * Test get cookie when cookie does not exist
     */
    public function testGetCookieThatDoesNotExist() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $this->assertNull($s->getCookie('foo'));
    }

    /**
     * Test delete cookie
     *
     * This method ensures that the `Set-Cookie:` HTTP response
     * header is set. The implementation of setting the response
     * cookie is tested separately in another file.
     */
    public function testDeleteCookie() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'HTTP_COOKIE' => 'foo=bar; foo2=bar2',
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $s->get('/bar', function () use ($s) {
            $s->setCookie('foo', 'bar');
            $s->deleteCookie('foo');
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $cookies = explode("\n", $header['Set-Cookie']);
        $this->assertEquals(1, count($cookies));
        $this->assertEquals(1, preg_match('@^foo=;@', $cookies[0]));
    }

    /**
     * Test set encrypted cookie
     *
     * This method ensures that the `Set-Cookie:` HTTP request
     * header is set. The implementation is tested in a separate file.
     */
    public function testSetEncryptedCookie() {
        $s = new Slim();
        $s->setEncryptedCookie('foo', 'bar');
        $r = $s->response();
        $this->assertEquals(1, preg_match("@^foo=.+%7C.+%7C.+@", $r['Set-Cookie'])); //<-- %7C is a url-encoded pipe
    }

    /**
     * Test get encrypted cookie
     *
     * This only tests that this method runs without error. The implementation of
     * fetching the encrypted cookie is tested separately.
     */
    public function testGetEncryptedCookieAndDeletingIt() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w'),
        ));
        $s = new Slim();
        $r = $s->response();
        $this->assertFalse($s->getEncryptedCookie('foo'));
        $this->assertEquals(1, preg_match("@foo=;.*@", $r['Set-Cookie']));
    }

    /**
     * Test get encrypted cookie WITHOUT deleting it
     *
     * This only tests that this method runs without error. The implementation of
     * fetching the encrypted cookie is tested separately.
     */
    public function testGetEncryptedCookieWithoutDeletingIt() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w'),
        ));
        $s = new Slim();
        $r = $s->response();
        $this->assertFalse($s->getEncryptedCookie('foo', false));
        $this->assertEquals(0, preg_match("@foo=;.*@", $r['Set-Cookie']));
    }

    /************************************************
     * HELPERS
     ************************************************/

    /**
     * Test get filesystem path to Slim app root directory
     */
    public function testGetRoot() {
        $_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__); //<-- No trailing slash
        $s = new Slim();
        $this->assertEquals($_SERVER['DOCUMENT_ROOT'] . '/foo/', $s->root()); //<-- Appends physical app path with trailing slash
    }

    /**
     * Test stop
     */
    public function testStop() {
        $this->setExpectedException('Slim_Exception_Stop');
        $s = new Slim();
        $s->stop();
    }

    /**
     * Test stop with subsequent output
     */
    public function testStopWithSubsequentOutput() {
        $s = new Slim();
        $s->get('/bar', function () use ($s) {
            echo "Foo"; //<-- Should be in response body!
            $s->stop();
            echo "Bar"; //<-- Should not be in response body!
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals('Foo', $body);
    }

    /**
     * Test halt
     */
    public function testHalt() {
        $s = new Slim();
        $s->get('/bar', function () use ($s) {
            echo "Foo!"; //<-- Should not be in response body!
            $s->halt(500, 'Something broke');
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals(500, $status);
        $this->assertEquals('Something broke', $body);
    }

    /**
     * Test pass cleans buffer and throws exception
     */
    public function testPass() {
        ob_start();
        $s = new Slim();
        echo "Foo";
        try {
            $s->pass();
            $this->fail('Did not catch Slim_Exception_Pass');
        } catch ( Slim_Exception_Pass $e ) {}
        $output = ob_get_clean();
        $this->assertEquals('', $output);
    }

    /**
     * Test pass when there is a subsequent fallback route
     */
    public function testPassWithSubsequentRoute() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/name/Frank', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $s->get('/name/Frank', function () use ($s) {
            echo "Fail"; //<-- Should not be in response body!
            $s->pass();
        });
        $s->get('/name/:name', function ($name) {
            echo $name; //<-- Should be in response body!
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals('Frank', $body);
    }

    /**
     * Test pass when there is not a subsequent fallback route
     */
    public function testPassWithoutSubsequentRoute() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/name/Frank', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $s = new Slim();
        $s->get('/name/Frank', function () use ($s) {
            echo "Fail"; //<-- Should not be in response body!
            $s->pass();
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals(404, $status);
    }

    /**
     * Test content type
     */
    public function testContentType() {
        $s = new Slim();
        $s->get('/bar', function () use ($s) {
            $s->contentType('application/json');
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals('application/json', $header['Content-Type']);
    }

    /**
     * Test status
     */
    public function testStatus() {
        $s = new Slim();
        $s->get('/bar', function () use ($s) {
            $s->status(403);
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals(403, $status);
    }

    /**
     * Test URL for
     */
    public function testSlimUrlFor(){
        $s = new Slim();
        $s->get('/hello/:name', function () {})->name('hello');
        $this->assertEquals('/foo/hello/Josh', $s->urlFor('hello', array('name' => 'Josh'))); //<-- Prepends physical path!
    }

    /**
     * Test redirect sets status and header
     */
    public function testRedirect() {
        $s = new Slim();
        $s->get('/bar', function () use ($s) {
            echo "Foo"; //<-- Should not be in response body!
            $s->redirect('/somewhere/else', 303);
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals(303, $status);
        $this->assertEquals('/somewhere/else', $header['Location']);
        $this->assertEquals('/somewhere/else', $body);
    }

    /************************************************
     * RUNNER
     ************************************************/

    /**
     * Test that runner sends headers and body
     */
    public function testRun() {
        $this->expectOutputString('Foo');
        $s = new Slim();
        $s->get('/bar', function () use ($s) {
            echo "Foo";
        });
        $s->run();
    }

    /************************************************
     * MIDDLEWARE
     ************************************************/

    /**
     * Test add middleware
     *
     * This asserts that middleware are queued and called
     * in sequence. This also asserts that the environment
     * variables are passed by reference.
     */
    public function testAddMiddleware() {
        $this->expectOutputString('FooHello');
        $s = new Slim();
        $s->add('CustomMiddleware'); //<-- See top of this file for class definition
        $s->get('/bar', function () {
            echo 'Foo';
        });
        $s->run();
        $this->assertEquals('Hello', $s->response()->header('X-Slim-Test'));
    }

    /**
     * Test add middleware class that is not available
     */
    public function testAddMiddlewareWithInvalidClassName() {
        $this->setExpectedException('RuntimeException');
        $s = new Slim();
        $s->add('FooMiddleware');
    }

    /**
     * Test add middleware class not using a string name
     */
    public function testAddMiddlewareWithoutStringArgument() {
        $this->setExpectedException('InvalidArgumentException');
        $s = new Slim();
        $s->add(123);
    }

    /************************************************
     * FLASH MESSAGING
     ************************************************/

    public function testSetFlashForNextRequest() {
        $s = new Slim();
        $s->add('Slim_Middleware_Flash');
        $s->get('/bar', function () use ($s) {
            $s->flash('info', 'bar');
        });
        $this->assertFalse(isset($_SESSION['slim.flash']));
        $s->run();
        $this->assertEquals('bar', $_SESSION['slim.flash']['info']);
    }

    public function testSetFlashForCurrentRequest() {
        $s = new Slim();
        $s->add('Slim_Middleware_Flash');
        $s->get('/bar', function () use ($s) {
            $s->flashNow('info', 'bar');
        });
        $s->run();
        $env = $s->environment();
        $this->assertEquals('bar', $env['slim.flash']['info']);
    }

    public function testKeepFlashForNextRequest() {
        $_SESSION['slim.flash'] = array('info' => 'Foo');
        $s = new Slim();
        $s->add('Slim_Middleware_Flash');
        $s->get('/bar', function () use ($s) {
            $s->flashKeep();
        });
        $s->run();
        $this->assertEquals('Foo', $_SESSION['slim.flash']['info']);
    }

    /************************************************
     * NOT FOUND HANDLING
     ************************************************/

    /**
     * Test custom Not Found handler
     */
    public function testNotFound() {
        $s = new Slim();
        $s->notFound(function () {
            echo "Not Found";
        });
        $s->get('/foo', function () {});
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals(404, $status);
        $this->assertEquals('Not Found', $body);
    }

    /************************************************
     * ERROR HANDLING
     ************************************************/

    /**
     * Test default and custom error handlers
     *
     * Pre-conditions:
     * Invoked app route calls default error handler;
     *
     * Post-conditions:
     * Response status code is 500;
     */
    public function testSlimError() {
        $s = new Slim();
        $s->get('/bar', function () use ($s) {
            $s->error();
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals(500, $status);
    }

    /**
     * Test triggered errors are converted to ErrorExceptions
     *
     * Pre-conditions:
     * Custom error handler defined;
     * Invoked app route triggers error;
     *
     * Post-conditions:
     * Response status is 500;
     * Response body is equal to triggered error message;
     * Error handler's argument is ErrorException instance;
     */
    public function testTriggeredErrorsAreConvertedToErrorExceptions() {
        $s = new Slim(array(
            'debug' => false
        ));
        $s->error(function ( $e ) {
            if ( $e instanceof ErrorException ) {
                echo $e->getMessage();
            }
        });
        $s->get('/bar', function () {
            trigger_error('Foo I say!');
        });
        $env = $s->environment();
        list($status, $header, $body) = $s->call($env);
        $this->assertEquals(500, $status);
        $this->assertEquals('Foo I say!', $body);
    }

    /**
     * Test error triggered with multiple applications
     *
     * Pre-conditions:
     * Multiple Slim apps are instantiated;
     * Both apps are run;
     * One app returns 200 OK;
     * One app triggers an error;
     *
     * Post-conditions:
     * One app returns 200 OK with no Exceptions;
     * One app returns 500 Error;
     * Error triggered does not affect other app;
     */
    public function testErrorWithMultipleApps() {
        $s1 = new Slim(array(
            'debug' => false
        ));
        $s2 = new Slim();
        $s1->get('/bar', function () {
            trigger_error('error');
        });
        $s2->get('/bar', function () {
            echo 'success';
        });
        $s1Env = $s1->environment();
        $s2Env = $s2->environment();
        list($status1, $header1, $body1) = $s1->call($s1Env);
        list($status2, $header2, $body2) = $s2->call($s2Env);
        $this->assertEquals(500, $status1);
        $this->assertEquals(200, $status2);
    }

    /************************************************
     * HOOKS
     ************************************************/

    /**
     * Test hook listener
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Hook name does not exist;
     * Listeners are callable objects;
     *
     * Post-conditions:
     * Callables are invoked in expected order;
     */
    public function testRegistersAndCallsHooksByPriority() {
        $this->expectOutputString('barfoo');
        $app = new Slim();
        $callable1 = function () { echo "foo"; };
        $callable2 = function () { echo "bar"; };
        $app->hook('test.hook.one', $callable1); //default is 10
        $app->hook('test.hook.one', $callable2, 8);
        $hooks = $app->getHooks();
        $this->assertEquals(7, count($hooks)); //6 default, 1 custom
        $app->applyHook('test.hook.one');
    }

    /**
     * Test hook listener if listener is not callable
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Hook name does not exist;
     * Listener is NOT a callable object;
     *
     * Post-conditions:
     * Hook is created;
     * Callable is NOT assigned to hook;
     */
    public function testHookInvalidCallable() {
        $app = new Slim();
        $callable = 'test'; //NOT callable
        $app->hook('test.hook.one', $callable);
        $this->assertEquals(array(array()), $app->getHooks('test.hook.one'));
    }

    /**
     * Test hook invocation if hook does not exist
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Hook name does not exist;
     *
     * Post-conditions:
     * Hook is created;
     * Hook initialized with empty array;
     */
    public function testHookInvocationIfNotExists() {
        $app = new Slim();
        $app->applyHook('test.hook.one');
        $this->assertEquals(array(array()), $app->getHooks('test.hook.one'));
    }

    /**
     * Test clear hooks
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Two hooks exist, each with one listener;
     *
     * Post-conditions:
     * Case A: Listeners for 'test.hook.one' are cleared;
     * Case B: Listeners for all hooks are cleared;
     */
    public function testHookClear() {
        $app = new Slim();
        $app->hook('test.hook.one', function () {});
        $app->hook('test.hook.two', function () {});
        $app->clearHooks('test.hook.two');
        $this->assertEquals(array(array()), $app->getHooks('test.hook.two'));
        $hookOne = $app->getHooks('test.hook.one');
        $this->assertTrue(count($hookOne[10]) === 1);
        $app->clearHooks();
        $this->assertEquals(array(array()), $app->getHooks('test.hook.one'));
    }

    /**
     * Test hook filter behavior
     */
    public function testHookFilterBehavior() {
        $app = new Slim();
        $app->hook('test.hook', function ($arg) { return $arg . 'foo'; });
        $this->assertEquals('barfoo', $app->applyHook('test.hook', 'bar'));
    }
}