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

//Start session before PHPUnit sends output. This only prevents us from using
//the default Slim Session cookie store.
session_start();

require_once 'Slim/Slim.php';

//Prepare mock HTTP request
$_SERVER['REDIRECT_STATUS'] = "200";
$_SERVER['HTTP_HOST'] = "slim";
$_SERVER['HTTP_CONNECTION'] = "keep-alive";
$_SERVER['HTTP_CACHE_CONTROL'] = "max-age=0";
$_SERVER['HTTP_ACCEPT'] = "application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
$_SERVER['HTTP_USER_AGENT'] = "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_4; en-US) AppleWebKit/534.3 (KHTML, like Gecko) Chrome/6.0.472.63 Safari/534.3";
$_SERVER['HTTP_ACCEPT_ENCODING'] = "gzip,deflate,sdch";
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = "en-US,en;q=0.8";
$_SERVER['HTTP_ACCEPT_CHARSET'] = "ISO-8859-1,utf-8;q=0.7,*;q=0.3";
$_SERVER['HTTP_IF_MODIFIED_SINCE'] = "Sun, 03 Oct 2010 17:00:52 -0400";
$_SERVER['HTTP_IF_NONE_MATCH'] = '"abc123"';
$_SERVER['HTTP_COOKIE'] = 'foo=bar; foo2=bar2';
$_SERVER['PATH'] = "/usr/bin:/bin:/usr/sbin:/sbin";
$_SERVER['SERVER_SIGNATURE'] = "";
$_SERVER['SERVER_SOFTWARE'] = "Apache";
$_SERVER['SERVER_NAME'] = "slim";
$_SERVER['SERVER_ADDR'] = "127.0.0.1";
$_SERVER['SERVER_PORT'] = "80";
$_SERVER['REMOTE_ADDR'] = "127.0.0.1";
$_SERVER['DOCUMENT_ROOT'] = '/home/account/public';
$_SERVER['SERVER_ADMIN'] = "you@example.com";
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
$_SERVER['REMOTE_PORT'] = "55426";
$_SERVER['REDIRECT_URL'] = "/";
$_SERVER['GATEWAY_INTERFACE'] = "CGI/1.1";
$_SERVER['SERVER_PROTOCOL'] = "HTTP/1.1";
$_SERVER['REQUEST_METHOD'] = "GET";
$_SERVER['QUERY_STRING'] = "";
$_SERVER['REQUEST_URI'] = "/";
$_SERVER['SCRIPT_NAME'] = '/bootstrap.php';
$_SERVER['PHP_SELF'] = '/bootstrap.php';
$_SERVER['REQUEST_TIME'] = "1285647051";
$_SERVER['argv'] = array();
$_SERVER['argc'] = 0;

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

//Mock custom Logger
class CustomLogger{
    public function debug( $var ) {
        print_r($var);
    }
    public function info( $var ) {
        print_r($var);
    }
    public function warn( $var ) {
        print_r($var);
    }
    public function error( $var ) {
        print_r($var);
    }
    public function fatal( $var ) {
        print_r($var);
    }
}

class SlimTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $_SERVER['REQUEST_METHOD'] = "GET";
        $_ENV['SLIM_MODE'] = null;
        $_COOKIE['foo'] = 'bar';
        $_COOKIE['foo2'] = 'bar2';
        $_SERVER['REQUEST_URI'] = "/";
    }

    /************************************************
     * SLIM INITIALIZATION
     ************************************************/

    /**
     * Test Slim default View
     *
     * Pre-conditions:
     * Slim app instantiated with default View;
     *
     * Post-conditions:
     * Slim app has default View of class Slim_View;
     */
    public function testSlimDefaultView() {
        $app = new Slim();
        $this->assertTrue($app->view() instanceof Slim_View);
    }

    /**
     * Test Slim custom View
     *
     * Pre-conditions:
     * Case A: Slim app instantiated with View setting as string;
     * Case B: Slim app instantiated with View as instance;
     *
     * Post-conditions:
     * Case A: View is instance of CustomView
     * Case B: View is instance of CustomView
     */
    public function testSlimInitWithCustomView(){
        //Case A
        $app1 = new Slim(array('view' => 'CustomView'));
        $this->assertTrue($app1->view() instanceof CustomView);
        //Case B
        $app2 = new Slim(array('view' => new CustomView()));
        $this->assertTrue($app2->view() instanceOf CustomView);
    }

    /**
     * Test Slim default Logger
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Logging enabled;
     * Default Logger used;
     *
     * Post-conditions:
     * Slim app has default Logger of class Slim_Logger;
     */
    public function testSlimInitWithDefaultLogger() {
        $app = new Slim(array(
            'log.path' => dirname(__FILE__) . '/logs',
            'log.enable' => true
        ));
        $this->assertTrue($app->getLog()->getLogger() instanceof Slim_Logger);
    }

    /**
     * Test Slim custom Logger
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Logging enabled;
     * Custom Logger used;
     *
     * Post-conditions:
     * Slim app has custom Logger of class CustomLogger;
     */
    public function testSlimInitWithCustomLogger() {
        $app = new Slim(array(
            'log.enable' => true,
            'log.logger' => new CustomLogger()
        ));
        $this->assertTrue($app->getLog()->getLogger() instanceof CustomLogger);
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

    /**
     * Test Slim get instance
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Set app name;
     *
     * Post-conditions:
     * A default app exists;
     * The instantiated app is returned by the name assigned to it;
     */
    public function testGetInstance() {
        $app = new Slim();
        $app->setName('foo');
        $this->assertTrue(Slim::getInstance() instanceof Slim);
        $this->assertEquals('foo', $app->getName());
        $this->assertSame($app, Slim::getInstance('foo'));
    }

    /**
     * Test Slim does not affect default Response HTTP status
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Case A: Use default settings;
     * Case B: Set to "1.0";
     *
     * Post-conditions:
     * Case A: Response HTTP version is "1.1";
     * Case B: Response HTTP version is "1.0";
     */
    public function testSlimSetsResponseHttpVersion() {
        $app1 = new Slim();
        $app2 = new Slim(array(
            'http.version' => '1.0'
        ));
        $this->assertEquals('1.1', $app1->response()->httpVersion());
        $this->assertEquals('1.0', $app2->response()->httpVersion());
    }

    /************************************************
     * SLIM SETTINGS
     ************************************************/

    /**
     * Test Slim mode with ENV[SLIM_MODE]
     *
     * Pre-conditions:
     * SLIM_MODE environment variable set;
     * Slim app instantiated with config mode;
     *
     * Post-conditions:
     * Only the production configuration is called;
     */
    public function testSlimModeEnvironment() {
        $this->expectOutputString('production mode');
        $_ENV['SLIM_MODE'] = 'production';
        $app = new Slim(array(
            'mode' => 'test'
        ));
        $app->configureMode('test', function () {
            echo "test mode";
        });
        $app->configureMode('production', function () {
            echo "production mode";
        });
    }

    /**
     * Test Slim mode with Config
     *
     * Pre-conditions:
     * ENV[SLIM_MODE] not set;
     * Slim app instantiated with config mode;
     *
     * Post-conditions:
     * Only the test configuration is called;
     */
    public function testSlimModeConfig() {
        $this->expectOutputString('test mode');
        $app = new Slim(array(
            'mode' => 'test'
        ));
        $app->configureMode('test', function () {
            echo "test mode";
        });
        $app->configureMode('production', function () {
            echo "production mode";
        });
    }

    /**
     * Test Slim mode with default
     *
     * Pre-conditions:
     * ENV[SLIM_MODE] not set;
     * Slim app instantiated without config mode;
     *
     * Post-conditions:
     * Only the development configuration is called;
     */
    public function testSlimModeDefault() {
        $this->expectOutputString('dev mode');
        $app = new Slim();
        $app->configureMode('development', function () {
            echo "dev mode";
        });
        $app->configureMode('production', function () {
            echo "production mode";
        });
    }

    /**
     * Test Slim Logging for given mode
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Set custom Logger for current app mode;
     *
     * Post-conditions:
     * Slim app Logger correct based on mode;
     */
    public function testSlimLoggerInMode() {
        $app = new Slim(array(
            'mode' => 'test'
        ));
        $app->configureMode('test', function () use ($app) {
            $app->config(array(
                'log.enable' => true,
                'log.logger' => new CustomLogger()
            ));
        });
        $app->configureMode('development', function () use ($app) {
            $app->config(array(
                'log.enable' => true
            ));
        });
        $this->assertTrue($app->getLog()->getLogger() instanceof CustomLogger);
    }

    /**
     * Test Slim defines one application setting
     *
     * Pre-conditions:
     * Slim app instantiated;
     * One configuration setting is set;
     *
     * Post-conditions:
     * Configuration setting `foo` === `bar`;
     */
    public function testSlimConfigSetsOneSetting(){
        $app = new Slim();
        $app->config('foo', 'bar');
        $this->assertEquals('bar', $app->config('foo'));
    }

    /**
     * Test Slim setting is NULL if non-existant
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Fetch non-existing configuration setting;
     *
     * Post-conditions:
     * NULL is returned for the value of the setting;
     */
    public function testSlimConfigIfSettingDoesNotExist(){
        $app = new Slim();
        $this->assertNull($app->config('foo'));
    }

    /**
     * Test Slim defines multiple settings with array
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Batch-define multiple configuration settings with associative array;
     *
     * Post-conditions:
     * Multiple settings are set correctly;
     */
    public function testSlimCongfigurationWithArray(){
        $app = new Slim();
        $app->config(array(
            'one' => 'A',
            'two' => 'B',
            'three' => 'C'
        ));
        $this->assertEquals('A', $app->config('one'));
        $this->assertEquals('B', $app->config('two'));
        $this->assertEquals('C', $app->config('three'));
    }

    /************************************************
     * SLIM ROUTING
     ************************************************/

    /**
     * Test Slim GET route
     *
     * Pre-conditions:
     * Slim app instantiated;
     * One GET route defined;
     *
     * Post-conditions:
     * The GET route is returned;
     * The GET route's pattern and callable are set correctly;
     */
    public function testSlimGetRoute(){
        $app = new Slim();
        $callable = function () { echo "foo"; };
        $route = $app->get('/foo/bar', $callable);
        $this->assertEquals('/foo/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test Slim GET route with middleware
     *
     * Pre-conditions:
     * Slim app instantiated and run;
     * One GET route defined with middleware;
     *
     * Post-conditions:
     * The GET route and its middleware are invoked in sequence;
     */
    public function testSlimGetRouteWithMiddleware(){ 
        $app = new Slim();
        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "foo"; };
        $app->get('/', $mw1, $mw2, $callable);
        $this->expectOutputString('foobarfoo');
        $app->run();
    }

    /**
     * Test Slim sets POST route
     *
     * Pre-conditions:
     * Slim app instantiated;
     * One POST route defined;
     *
     * Post-conditions:
     * The POST route is returned;
     * The POST route's pattern and callable are set correctly;
     */
    public function testSlimPostRoute(){
        $app = new Slim();
        $callable = function () { echo "foo"; };
        $route = $app->post('/foo/bar', $callable);
        $this->assertEquals('/foo/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test Slim POST route with middleware
     *
     * Pre-conditions:
     * Slim app instantiated and run;
     * One POST route defined with middleware;
     *
     * Post-conditions:
     * The POST route and its middleware are invoked in sequence;
     */
    public function testSlimPostRouteWithMiddleware(){ 
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $app = new Slim();
        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "foo"; };
        $route = $app->post('/', $mw1, $mw2, $callable);
        $this->expectOutputString('foobarfoo');
        $app->run();
    }

    /**
     * Test Slim sets PUT route
     *
     * Pre-conditions:
     * Slim app instantiated;
     * One PUT request defined;
     *
     * Post-conditions:
     * The PUT route is returned;
     * The PUT route's pattern and callable are set correctly;
     */
    public function testSlimPutRoute(){
        $app = new Slim();
        $callable = function () { echo "foo"; };
        $route = $app->put('/foo/bar', $callable);
        $this->assertEquals('/foo/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test Slim PUT route with middleware
     *
     * Pre-conditions:
     * Slim app instantiated and run;
     * One PUT request defined with middleware;
     *
     * Post-conditions:
     * The PUT route and its middleware are invoked in sequence;
     */
    public function testSlimPutRouteWithMiddleware(){ 
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $app = new Slim();
        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "foo"; };
        $route = $app->put('/', $mw1, $mw2, $callable);
        $this->expectOutputString('foobarfoo');
        $app->run();
    }

    /**
     * Test Slim sets DELETE route
     *
     * Pre-conditions:
     * Slim app instantiated;
     * One DELETE route defined;
     *
     * Post-conditions:
     * The DELETE route is returned;
     * The DELETE route's pattern and callable are set correctly;
     */
    public function testSlimDeleteRoute(){
        $app = new Slim();
        $callable = function () { echo "foo"; };
        $route = $app->delete('/foo/bar', $callable);
        $this->assertEquals('/foo/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test Slim DELETE route with middleware
     *
     * Pre-conditions:
     * Slim app instatiated and run;
     * One DELETE route defined with middleware;
     *
     * Post-conditions:
     * The DELETE route and its middleware are invoked in sequence;
     */
    public function testSlimDeleteRouteWithMiddleware(){ 
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $app = new Slim();
        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "foo"; };
        $route = $app->delete('/', $mw1, $mw2, $callable);
        $this->expectOutputString('foobarfoo');
        $app->run();
    }
    
    /**
     * Test Slim sets DELETE route
     *
     * Pre-conditions:
     * Slim app instantiated;
     * One OPTIONS route defined;
     *
     * Post-conditions:
     * The OPTIONS route is returned;
     * The OPTIONS route's pattern and callable are set correctly;
     */
    public function testSlimOptionsRoute(){
        $app = new Slim();
        $callable = function () { echo "foo"; };
        $route = $app->options('/foo/bar', $callable);
        $this->assertEquals('/foo/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test Slim DELETE route with middleware
     *
     * Pre-conditions:
     * Slim app instatiated and run;
     * One OPTIONS route defined with middleware;
     *
     * Post-conditions:
     * The OPTIONS route and its middleware are invoked in sequence;
     */
    public function testSlimOptionsRouteWithMiddleware(){ 
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $app = new Slim();
        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "foo"; };
        $route = $app->options('/', $mw1, $mw2, $callable);
        $this->expectOutputString('foobarfoo');
        $app->run();
    }

    /**
     * Test Slim routing and trailing slashes
     *
     * Pre-conditions:
     * A route is defined that expects a trailing slash, but
     * the resource URI does not have a trailing slash - but
     * otherwise matches the route pattern.
     *
     * Post-conditions:
     * Slim will send a 301 redirect response to the same
     * resource URI but with a trailing slash.
     */
    public function testRouteWithSlashAndUrlWithout() {
        $_SERVER['REQUEST_URI'] = '/foo/bar/bob';
        $this->expectOutputString('/foo/bar/bob/');
        $app = new Slim();
        $app->get('/foo/bar/:name/', function ($name) {});
        $app->run();
        $this->assertEquals(301, $app->response()->status());
        $this->assertEquals('/foo/bar/bob/', $app->response()->header('Location'));
    }

    /**
     * Test Slim routing and trailing slashes
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Route defined that matches current HTTP request;
     * Route does NOT expect trailing slash;
     * HTTP request DOES have trailing slash;
     *
     * Post-conditions:
     * Slim response status is 404;
     */
    public function testRouteWithoutSlashAndUrlWith() {
        $_SERVER['REQUEST_URI'] = '/foo/bar/bob/';
        $this->expectOutputRegex('@404 Page Not Found@');
        $app = new Slim();
        $app->get('/foo/bar/:name', function ($name) {});
        $app->run();
        $this->assertEquals(404, $app->response()->status());
    }

    /**
     * Test Slim routing with URL encoded characters
     *
     * Pre-conditions:
     * Slim initialized;
     * Route defined and matches current request;
     * URL encoded spaces in URL;
     *
     * Post-conditions:
     * Route matched;
     * Route callable invoked;
     * Route callable arguments are URL decoded;
     */
    public function testRouteWithUrlEncodedParameters() {
        $_SERVER['REQUEST_URI'] = '/foo/jo%20hn/smi%20th';
        $app = new Slim();
        $app->get('/foo/:one/:two', function ($one, $two) {
            echo "$one and $two";
        });
        $app->run();
        $this->expectOutputString('jo hn and smi th');
    }

    /************************************************
     * SLIM ACCESSORS
     ************************************************/

    public function testSlimAccessors() {
        $app = new Slim();
        $this->assertTrue($app->request() instanceof Slim_Http_Request);
        $this->assertTrue($app->response() instanceof Slim_Http_Response);
        $this->assertTrue($app->router() instanceof Slim_Router);
    }

    /************************************************
     * SLIM VIEW
     ************************************************/

    /**
     * Test Slim copies data from old View to new View
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Data set in default View;
     * New View is defined;
     *
     * Post-conditions:
     * The data from the original View is accessible in the new View;
     */
    public function testSlimCopiesViewData(){
        $data = array('foo' => 'bar');
        $app = new Slim();
        $app->view()->setData($data);
        $this->assertTrue($app->view() instanceof Slim_View);
        $this->assertEquals($data, $app->view()->getData());
        $app->view('CustomView');
        $this->assertTrue($app->view() instanceof CustomView);
        $this->assertEquals($data, $app->view()->getData());
    }

    /************************************************
     * SLIM RENDERING
     ************************************************/

    /**
     * Test Slim rendering with custom status
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Render an existing template with custom data and status;
     *
     * Post-conditions:
     * The response status is 404;
     * The response body is correct;
     */
    public function testSlimRenderSetsResponseStatusOk(){
        $this->expectOutputString('test output bar');
        $app = new Slim(array(
            'templates.path' => dirname(__FILE__) . '/templates'
        ));
        $app->render('test.php', array('foo' => 'bar'), 404);
        $this->assertEquals(404, $app->response()->status());
    }

    /**
     * Test Slim rendering
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Render an existing template with custom data;
     *
     * Post-conditions:
     * The response body is correct;
     */
    public function testSlimRender(){
        $this->expectOutputString('test output bar');
        $app = new Slim(array(
            'templates.path' => dirname(__FILE__) . '/templates'
        ));
        $app->render('test.php', array('foo' => 'bar'));
    }

    /************************************************
     * SLIM HTTP CACHING
     ************************************************/

    /**
     * Test Slim HTTP caching if ETag match
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Define route that matches current HTTP request;
     * Route sets ETag header, matches request's `If-None-Match` header;
     *
     * Post-conditions:
     * Slim app response status is 304;
     */
    public function testSlimEtagMatches(){
        $app = new Slim();
        $app->get('/', function () use ($app) {
            $app->etag('abc123');
        });
        $app->run();
        $this->assertEquals(304, $app->response()->status());
    }

    /**
     * Test Slim HTTP caching if ETag does not match
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Define route that matches current HTTP request;
     * Route sets ETag header, does not match request's `If-None-Match` header;
     *
     * Post-conditions:
     * Slim app response status is 200;
     */
    public function testSlimEtagDoesNotMatch(){
        $app = new Slim();
        $app->get('/', function () use ($app) {
            $app->etag('xyz789');
        });
        $app->run();
        $this->assertEquals(200, $app->response()->status());
    }

    /**
     * Test Slim::etag only accepts 'strong' or 'weak' types
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Define route that matches current HTTP request;
     * Route sets ETag header with an invalid argument;
     *
     * Post-conditions:
     * Slim app response status is 500;
     */
    public function testSlimETagThrowsExceptionForInvalidType(){
        $this->expectOutputRegex('@Invalid Slim::etag type. Expected "strong" or "weak"@');
        $app = new Slim();
        $app->get('/', function () use ($app) {
            $app->etag('123','foo');
        });
        $app->run();
        $this->assertEquals(500, $app->response()->status());
    }

    /**
     * Test Slim HTTP caching with Last Modified match
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Define route that matches current HTTP request;
     * Route correctly sets a Last-Modified header;
     *
     * Post-conditions:
     * Slim app response status is 304 Not Modified;
     */
    public function testSlimLastModifiedDateMatches(){
        $app = new Slim();
        $app->get('/', function () use ($app) {
            $app->lastModified(1286139652);
        });
        $app->run();
        $this->assertEquals(304, $app->response()->status());
    }

    /**
     * Test Slim HTTP caching if Last Modified does not match
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Define route that matches current HTTP request;
     * Route sets `Last-Modified` header;
     * The HTTP `If-Modified-Since` header does not match the `Last-Modified` date;
     *
     * Post-conditions:
     * Slim app response status is 200;
     */
    public function testSlimLastModifiedDateDoesNotMatch(){
        $app = new Slim();
        $app->get('/', function () use ($app) {
            $app->lastModified(1286139250);
        });
        $app->run();
        $this->assertEquals(200, $app->response()->status());
    }

    /**
     * Test Slim Last Modified only accepts integer values
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Define route that matches current HTTP request;
     * Route sets LastModified header value incorrectly;
     *
     * Post-conditions:
     * Slim app response status is 500;
     */
    public function testSlimLastModifiedOnlyAcceptsIntegers(){
        $this->expectOutputRegex('@Slim::lastModified only accepts an integer UNIX timestamp value@');
        $app = new Slim();
        $app->get('/', function () use ($app) {
            $app->lastModified('Test');
        });
        $app->run();
        $this->assertEquals(500, $app->response()->status());
    }

    /************************************************
     * SLIM COOKIES
     ************************************************/

    /**
     * Test Slim gets cookie
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Case A: Cookie `foo` exists, available in HTTP request;
     * Case B: Cookie `bad` does not exist;
     *
     * Post-conditions:
     * Case A: Cookie `foo` value is "bar";
     * Case B: Cooke `bad` value is NULL;
     */
    public function testSlimGetsCookie() {
        $app = new Slim();
        //Case A
        $this->assertEquals('bar', $app->getCookie('foo'));
        //Case B
        $this->assertNull($app->getCookie('doesNotExist'));
    }

    /**
     * Test Slim sets cookie with default time
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Case A: Cookie time not set;
     * Case B: Cookie time set as seconds from now (integer);
     * Case C: Cookie time set as string;
     * Case D: Cookie time is set to 0;
     *
     * Post-conditions:
     * Cookie available in response;
     * Case A: Cookie time set using default value;
     * Case C: Cookie time set using `strtotime()`;
     * Case D: Cookie time is 0;
     */
    public function testSlimSetsCookie() {
        $app = new Slim();
        $cj = $app->response()->getCookieJar();
        //Case A
        $timeA = time();
        $app->setCookie('myCookie1', 'myValue1');
        $cookieA = $cj->getResponseCookie('myCookie1');
        $this->assertEquals('myCookie1', $cookieA->getName());
        $this->assertEquals('myValue1', $cookieA->getValue());
        $this->assertEquals($timeA + 1200, $cookieA->getExpires()); //default duration is 20 minutes
        $this->assertEquals('/', $cookieA->getPath());
        $this->assertEquals('', $cookieA->getDomain());
        $this->assertFalse($cookieA->getSecure());
        $this->assertFalse($cookieA->getHttpOnly());
        //Case C
        $timeC = time();
        $app->setCookie('myCookie3', 'myValue3', '1 hour');
        $cookieC = $cj->getResponseCookie('myCookie3');
        $this->assertEquals($timeC + 3600, $cookieC->getExpires());
        //Case D
        $timeD = time();
        $app->setCookie('myCookie4', 'myValue4', 0);
        $cookieD = $cj->getResponseCookie('myCookie4');
        $this->assertEquals(0, $cookieD->getExpires());
    }

    /**
     * Test Slim sets encrypted cookie
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Case A: Cookie time not set;
     * Case B: Cookie time set as seconds from now (integer);
     * Case C: Cookie time set as string;
     * Case D: Cookie time is set to 0;
     *
     * Post-conditions:
     * Cookie available in response;
     * Case A: Cookie time set using default value;
     * Case C: Cookie time set using `strtotime()`;
     * Case D: Cookie time is 0;
     */
    public function testSlimSetsEncryptedCookie() {
        $app = new Slim();
        $cj = $app->response()->getCookieJar();
        //Case A
        $timeA = time();
        $app->setEncryptedCookie('myCookie1', 'myValue1');
        $cookieA = $cj->getResponseCookie('myCookie1');
        $this->assertEquals('myCookie1', $cookieA->getName());
        $this->assertEquals($timeA + 1200, $cookieA->getExpires()); //default duration is 20 minutes
        $this->assertEquals('/', $cookieA->getPath());
        $this->assertEquals('', $cookieA->getDomain());
        $this->assertFalse($cookieA->getSecure());
        $this->assertFalse($cookieA->getHttpOnly());
        //Case C
        $timeC = time();
        $app->setEncryptedCookie('myCookie3', 'myValue3', '1 hour');
        $cookieC = $cj->getResponseCookie('myCookie3');
        $this->assertEquals($timeC + 3600, $cookieC->getExpires());
        //Case D
        $timeD = time();
        $app->setEncryptedCookie('myCookie4', 'myValue4', 0);
        $cookieD = $cj->getResponseCookie('myCookie4');
        $this->assertEquals(0, $cookieD->getExpires());
    }

    /**
     * Test Slim deletes cookies
     *
     * Pre-conditions:
     * Case A: Classic cookie
     * Case B: Encrypted cookie
     *
     * Post-conditions:
     * Response Cookies replaced with empty, auto-expiring Cookies
     */
    public function testSlimDeletesCookies() {
        $app = new Slim();
        $cj = $app->response()->getCookieJar();
        //Case A
        $app->setCookie('foo1', 'bar1');
        $this->assertEquals('bar1', $cj->getResponseCookie('foo1')->getValue());
        $this->assertTrue($cj->getResponseCookie('foo1')->getExpires() > time());
        $app->deleteCookie('foo1');
        $this->assertEquals('', $app->getCookie('foo1'));
        $this->assertTrue($cj->getResponseCookie('foo1')->getExpires() < time());
        //Case B
        $app->setEncryptedCookie('foo2', 'bar2');
        $this->assertTrue(strlen($cj->getResponseCookie('foo2')->getValue()) > 0);
        $this->assertTrue($cj->getResponseCookie('foo2')->getExpires() > time());
        $app->deleteCookie('foo2');
        $this->assertEquals('', $cj->getResponseCookie('foo2')->getValue());
        $this->assertTrue($cj->getResponseCookie('foo2')->getExpires() < time());
    }

    /************************************************
     * SLIM HELPERS
     ************************************************/

    /**
     * Test Slim Root
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Slim app installed in document root directory;
     *
     * Post-conditions:
     * Slim correctly reports root path;
     */
    public function testRootPathInBaseDirectory() {
        $rootPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/';
        $app = new Slim();
        $this->assertEquals($rootPath, $app->root());
    }

    /**
     * Test Slim Root From Subdirectory
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Slim app installed in a physical, public subdirectory of document root;
     *
     * Post-conditions:
     * Slim correctly reports root path;
     */
    public function testRootPathInSubDirectory() {
        $_SERVER['REQUEST_URI'] = '/foo/bar';
        $_SERVER['SCRIPT_NAME'] = '/foo/bootstrap.php';
        $_SERVER['PHP_SELF'] = '/foo/bootstrap.php';
        $rootPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/foo/';
        $app = new Slim();
        $this->assertEquals($rootPath, $app->root());
    }

    /**
     * Test Slim Stop
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Slim app stopped while route invocation in process;
     *
     * Post-conditions:
     * Slim ignores output after `stop()` is invoked;
     */
    public function testSlimStop() {
        $this->expectOutputString('foo');
        $app = new Slim();
        $app->get('/', function () use ($app) {
            try {
                echo "foo";
                $app->stop();
                echo "bar";
            } catch ( Slim_Exception_Stop $e ) {}
        });
        $app->run();
    }

    /**
     * Test Slim Halt inside route callback
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Define route that matches current HTTP request;
     * Halt app from within invoked route;
     *
     * Post-conditions:
     * Slim app response status is 404;
     * Slim app response body is 'Halt not found';
     */
    public function testSlimHaltInsideCallback() {
        $this->expectOutputString('Halt not found');
        $app = new Slim();
        $app->get('/', function () use ($app) {
            echo 'foo';
            $app->halt(404, 'Halt not found');
            echo 'bar';
        });
        $app->run();
        $this->assertEquals(404, $app->response()->status());
    }

    /**
     * Test Slim Halt outside route callback
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Slim::halt is invoked outside of a route callback;
     *
     * Post-conditions:
     * The new response should be returned with the expected
     * status code and body, regardless of the current route
     * callback's expected output.
     */
    public function testSlimHaltOutsideCallback() {
        $this->setExpectedException('Slim_Exception_Stop');
        $this->expectOutputString('External error');
        $app = new Slim();
        $app->halt(500, 'External error');
        $app->get('/', function () {
            echo "foo";
        });
        $app->run();
        $this->assertEquals(500, $app->response()->status());
    }

    /**
     * Test Slim Pass continues to next matching route
     *
     * Pre-conditions:
     * Slim app instantiated with two routes;
     * First route is most specific, invokes `pass()`;
     * Second route is next matching route;
     *
     * Post-conditions:
     * The response body is set by the second matching route;
     */
    public function testSlimPassWithFallbackRoute() {
        $_SERVER['REQUEST_URI'] = "/name/Frank";
        $this->expectOutputString('I think your name is Frank');
        $app = new Slim();
        $app->get('/name/Frank', function () use ($app) {
            echo "Your name is Frank";
            $app->pass();
        });
        $app->get('/name/:name', function ($name) {
            echo "I think your name is $name";
        });
        $app->run();
    }

    /**
     * Test Slim Pass continues, but next matching route not found
     *
     * Pre-conditions:
     * Slim app initiated;
     * Define route that matches current HTTP request;
     * Route passes;
     * No subsequent routes available;
     *
     * Post-conditions:
     * Slim app response status is 404;
     */
    public function testSlimPassWithoutFallbackRoute() {
        $_SERVER['REQUEST_URI'] = '/name/Frank';
        $this->expectOutputRegex('#Not Found#');
        $app = new Slim();
        $app->get('/name/Frank', function () use ($app) {
            echo 'Your name is Frank';
            $app->pass();
        });
        $app->run();
        $this->assertEquals(404, $app->response()->status());
    }

    /**
     * Test Slim::contentType
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Content-Type header is set using helper method;
     *
     * Post-conditions:
     * The Response content type header is set correctly;
     */
    public function testSlimContentType(){
        $app = new Slim();
        $app->contentType('image/jpeg');
        $this->assertEquals('image/jpeg', $app->response()->header('Content-Type'));
    }

    /**
     * Test Slim::status
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Case A: Valid HTTP status is set using helper method;
     * Case B: Invalid HTTP status is set using helper method;
     *
     * Post-conditions:
     * Case A: The Response status code is set correctly;
     * Case B: InvalidArgumentException is thrown;
     */
    public function testSlimStatus(){
        $app1 = new Slim();
        //Case A
        $app1->status(302);
        $this->assertSame($app1->response()->status(), 302);
        //Case B
        $this->setExpectedException('InvalidArgumentException');
        $app2 = new Slim();
        $app2->status(900);
    }

    /**
     * Test Slim URL For
     *
     * Pre-conditions:
     * Slim app instantiatd with named route;
     *
     * Post-conditions:
     * Slim returns an accurate URL for the named route;
     */
    public function testSlimUrlFor(){
        $app = new Slim();
        $app->get('/hello/:name', function () {})->name('hello');
        $this->assertEquals('/hello/Josh', $app->urlFor('hello', array('name' => 'Josh')));
    }

    /**
     * Test Slim::redirect
     *
     * Pre-conditions:
     * Case A: Status code is less than 300
     * Case B: Status code is greater than 307
     * Case C: Status code is 300
     * Case D: Status code is 302 (between 300 and 307)
     * Case E: Status code is 307
     *
     * Post-conditions:
     * Case A: Response code is 500 (due to invalid redirect status)
     * Case B: Response code is 500 (due to invalid redirect status)
     * Case C: Response code is 300
     * Case D: Response code is 302
     * Case E: Response code is 307
     */
    public function testSlimRedirect() {
        //Case A
        $app1 = new Slim();
        $app1->get('/', function () use ($app1) {
            $app1->redirect('/foo', 200);
        });
        ob_start();
        $app1->run();
        $app1Out = ob_get_clean();
        $this->assertEquals(500, $app1->response()->status());

        //Case B
        $app2 = new Slim();
        $app2->get('/', function () use ($app2) {
            $app2->redirect('/foo', 308);
        });
        ob_start();
        $app2->run();
        $app2Out = ob_get_clean();
        $this->assertEquals(500, $app2->response()->status());

        //Case C
        $app3 = new Slim();
        $app3->get('/', function () use ($app3) {
            $app3->redirect('/foo', 300);
        });
        ob_start();
        $app3->run();
        ob_clean();
        $this->assertEquals(300, $app3->response()->status());

        //Case D
        $app4 = new Slim();
        $app4->get('/', function () use ($app4) {
            $app4->redirect('/foo', 302);
        });
        ob_start();
        $app4->run();
        ob_clean();
        $this->assertEquals(302, $app4->response()->status());

        //Case E
        $app5 = new Slim();
        $app5->get('/', function () use ($app5) {
            $app5->redirect('/foo', 307);
        });
        ob_start();
        $app5->run();
        ob_clean();
        $this->assertEquals(307, $app5->response()->status());
    }

    /************************************************
     * SLIM FLASH MESSAGING
     ************************************************/

    /**
     * Slim Flash
     *
     * Pre-conditions:
     * Slim app sets Flash message for next request;
     *
     * Post-conditions:
     * Message is persisted to $_SESSION after app is run;
     */
    public function testSlimFlash() {
        $app = new Slim();
        $app->get('/', function () use ($app) {
            $app->flash('info', 'Foo');
        });
        $app->run();
        $this->assertArrayHasKey('info', $_SESSION['flash']);
        $this->assertEquals('Foo', $_SESSION['flash']['info']);
    }

    /**
     * Slim Flash with Redirect
     *
     * Pre-conditions:
     * Slim app sets Flash message for next request;
     * Slim app halts with 302 redirect
     *
     * Post-conditions:
     * Message is persisted to $_SESSION after app is run;
     */
    public function testSlimFlashWithRedirect() {
        $this->expectOutputString('/foo');
        $app = new Slim();
        $app->get('/', function () use ($app) {
            $app->flash('info', 'Foo redirect');
            $app->redirect('/foo');
        });
        $app->run();
        $this->assertArrayHasKey('info', $_SESSION['flash']);
        $this->assertEquals('Foo redirect', $_SESSION['flash']['info']);
    }

    /**
     * Slim Flash Now
     *
     * Pre-conditions:
     * Slim app sets Flash message for current request;
     *
     * Post-conditions:
     * Message is persisted to View data;
     */
    public function testSlimFlashNow() {
        $app = new Slim();
        $app->get('/', function () use ($app) {
            $app->flashNow('info', 'Foo');
        });
        $app->run();
        $flash = $app->view()->getData('flash');
        $this->assertEquals('Foo', $flash['info']);
    }

    /**
     * Slim Keep Flash
     *
     * Pre-conditions:
     * Slim app receives existing Flash message from $_SESSION;
     *
     * Post-conditions:
     * Message is re-persisted to $_SESSION after app is run;
     */
    public function testSlimFlashKeep() {
        $_SESSION['flash'] = array('info' => 'Foo');
        $app = new Slim();
        $app->get('/', function () use ($app) {
            $app->flashKeep();
        });
        $app->run();
        $this->assertArrayHasKey('info', $_SESSION['flash']);
        $this->assertEquals('Foo', $_SESSION['flash']['info']);
    }

    /************************************************
     * SLIM ERROR AND EXCEPTION HANDLING
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
        $this->expectOutputRegex('#Error#');
        $app = new Slim();
        $app->get('/', function () use ($app) {
            $app->error();
        });
        $app->run();
        $this->assertEquals(500, $app->response()->status());
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
        $this->expectOutputString('Foo I say!');
        $app = new Slim(array(
            'debug' => false
        ));
        $app->error(function ( $e ) {
            if ( $e instanceof ErrorException ) {
                echo $e->getMessage();
            }
        });
        $app->get('/', function () {
            trigger_error('Foo I say!');
        });
        $app->run();
        $this->assertEquals(500, $app->response()->status());
    }

    /**
     * Test error handler receives Exception as argument
     *
     * Pre-conditions:
     * Custom error handler defined;
     * Invoked app route throws Exception;
     *
     * Post-conditions:
     * Response status is 500;
     * Error handler's argument is the thrown Exception
     */
    public function testErrorHandlerReceivesException() {
        $this->expectOutputString('ErrorException');
        $app = new Slim(array(
            'debug' => false
        ));
        $app->error(function ( $e ) {
            if ( $e instanceof Exception ) {
                echo get_class($e);
            }
        });
        $app->get('/', function () {
            $result = 1 / 0;
        });
        $app->run();
        $this->assertEquals(500, $app->response()->status());
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
        $app1 = new Slim();
        $app2 = new Slim();
        $app1->get('/', function () {
            trigger_error('error');
        });
        $app2->get('/', function () {
            echo 'success';
        });
        ob_start();
        $app1->run();
        $app1Output = ob_get_clean();
        ob_start();
        $app2->run();
        $app2Output = ob_get_clean();
        $this->assertEquals(500, $app1->response()->status());
        $this->assertEquals(200, $app2->response()->status());
    }

    /**
     * Test Slim Not Found handler
     *
     * Pre-conditions:
     * Initialize one Slim app without custom Not Found handler;
     * Initialize one Slim app with custom Not Found Handler;
     * Both app's routes do not match HTTP request;
     *
     * Post-conditions:
     * Both Slim apps' response status is 404;
     * Custom Not Found handler is invoked if specified;
     */
    public function testSlimRouteNotFound() {
        $app1 = new Slim();
        $app1->get('/foo', function () {});
        ob_start();
        $app1->run();
        $app1Out = ob_get_clean();
        $app2 = new Slim();
        $app2->notFound(function () {
            echo 'Not Found';
        });
        $app2->get('/bar', function () {});
        ob_start();
        $app2->run();
        $app2Out = ob_get_clean();
        $this->assertEquals(404, $app1->response()->status());
        $this->assertEquals(404, $app2->response()->status());
        $this->assertEquals('Not Found', $app2->response()->body());
    }

    /**
     * Test Slim app without errors
     *
     * Pre-conditions:
     * Slim app does not have Errors and Exceptions;
     *
     * Post-conditions:
     * Slim app response status is 200;
     */
    public function testSlimOkResponse() {
        $this->expectOutputString('Ok');
        $app = new Slim();
        $app->get('/', function () {
            echo 'Ok';
        });
        $app->run();
        $this->assertEquals(200, $app->response()->status());
    }

    /************************************************
     * SLIM HOOKS
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

    /************************************************
     * SLIM RUN LOOP
     ************************************************/

    /**
     * Test that a route returns 200 OK response when the route is
     * manually assigned multiple HTTP methods
     */
    public function testMatchingRouteWithMultipleMethods() {
        $this->expectOutputString('Foo!');
        $_SERVER['REQUEST_URI'] = "/foo";
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $app = new Slim();
        $app->map('/foo', function () {
            echo "Foo!";
        })->via('GET', 'POST');
        $app->run();
        $this->assertEquals(200, $app->response()->status());
    }

    /**
     * Test that a route returns 200 OK response when a prior matching
     * route was not invoked because it did not support HTTP request method.
     */
    public function testMatchingRoutesThatSupportDifferentMethods() {
        $this->expectOutputString('Foo POST');
        $_SERVER['REQUEST_URI'] = "/foo";
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $app = new Slim();
        //GET route
        $app->map('/foo', function () {
            echo "Foo GET";
        })->via('GET');
        //POST route
        $app->map('/foo', function () {
            echo "Foo POST";
        })->via('POST');
        $app->run();
        $this->assertEquals(200, $app->response()->status());
    }
    
    /**
     * Test that app returns 405 response when matching routes
     * are round but do not support the HTTP request method. Ensure the 
     * response's Allow header is also set and aggregates all matching
     * routes' supported methods into the response header.
     */
    public function testMatchingRoutesThatDoNotSupportRequestMethod() {
        $_SERVER['REQUEST_URI'] = "/foo";
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $app = new Slim();
        //GET route
        $app->map('/foo', function () {
            echo "Foo GET";
        })->via('GET');
        //POST and PUT route
        $app->map('/foo', function () {
            echo "Foo POST and PUT";
        })->via('POST', 'PUT');
        $app->run();
        $this->assertEquals(405, $app->response()->status());
        $this->assertEquals('GET POST PUT', $app->response()->header('Allow'));
    }

    /**
     * Test that app returns 405 response when matched, invoked route calls
     * $app->pass(); the subsequent matching route does not support the current
     * HTTP request method.
     */
    public function testInvokedRoutePassesToAnotherRouteThatDoesNotSupportMethod() {
        $_SERVER['REQUEST_URI'] = "/foo";
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $app = new Slim();
        //GET route
        $app->map('/foo', function () use ($app) {
            $app->pass();
            echo "Foo GET";
        })->via('GET');
        //POST and PUT route
        $app->map('/foo', function () {
            echo "Foo POST";
        })->via('POST');
        $app->run();
        $this->assertEquals(405, $app->response()->status());
        $this->assertEquals('POST', $app->response()->header('Allow'));
    }

    /**
     * Test that app returns 404 response when matched, invoked route calls
     * $app->pass(); there are no subsequent matching routes.
     */
    public function testInvokedRoutePassesWithoutSubsequentRoutes() {
        $this->expectOutputRegex('#404 Page Not Found#');
        $_SERVER['REQUEST_URI'] = "/foo";
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $app = new Slim();
        $app->map('/foo', function () use ($app) {
            $app->pass();
            echo "Foo GET";
        })->via('GET');
        $app->run();
        $this->assertEquals(404, $app->response()->status());
    }

    /**
     * Test that app returns 404 response when there are no matching routes
     */
    public function testNotFoundIfNoMatchingRoutes() {
        $this->expectOutputRegex('#404 Page Not Found#');
        $_SERVER['REQUEST_URI'] = "/foo";
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $app = new Slim();
        $app->map('/foo/bar', function () {
            echo "Foo bar!";
        })->via('GET');
        $app->run();
        $this->assertEquals(404, $app->response()->status());
    }

    /**
     * Test that app sends response with default HTTP version
     */
    public function testAppSendsResponseWithDefaultHttpVersion() {
        $app = new Slim();
        $app->get('/', function () {});
        $app->run();
        $this->assertEquals('1.1', $app->response()->httpVersion());
    }

    /**
     * Test that app sends response with custom HTTP version
     */
    public function testAppSendsResponseWithCustomHttpVersion() {
        $app = new Slim(array('http.version' => '1.0'));
        $app->get('/', function () {});
        $app->run();
        $this->assertEquals('1.0', $app->response()->httpVersion());
    }
}
