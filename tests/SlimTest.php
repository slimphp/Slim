<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart
 * @link        http://www.slimframework.com
 * @copyright   2011 Josh Lockhart
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

class SlimTest extends PHPUnit_Extensions_OutputTestCase {

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
     * Test Slim initialization
     *
     * Pre-conditions:
     * You have initialized a Slim application without specifying
     * a custom View class.
     *
     * Post-conditions:
     * Slim should have a default Not Found handler that is callable;
     * Slim should have a default Error hanlder that is callable;
     * Slim should have a default View
     */
    public function testSlimInit() {
        Slim::init();
        $this->assertTrue(is_callable(Slim::router()->notFound()));
        $this->assertTrue(is_callable(Slim::router()->error()));
        $this->assertTrue(Slim::view() instanceof Slim_View);
        $this->assertEquals('20 minutes', Slim::config('cookies.lifetime'));
    }

    /**
     * Test Slim initialization
     *
     * Pre-conditions:
     * Case A: Slim application initialized with logging, without custom Logger
     *
     * Post-conditions:
     * Case A: Default Logger is set
     */
    public function testSlimInitWithDefaultLogger() {
        Slim::init(array(
            'log.path' => dirname(__FILE__) . '/logs',
            'log.enable' => true
        ));
        $this->assertTrue(Slim_Log::getLogger() instanceof Slim_Logger);
    }

    /**
     * Test Slim initialization
     *
     * Pre-conditions:
     * Case A: Slim application initialized with logging, with custom Logger
     *
     * Post-conditions:
     * Case A: Custom Logger is set
     */
    public function testSlimInitWithCustomLogger() {
        Slim::init(array(
            'log.enable' => true,
            'log.logger' => new CustomLogger()
        ));
        $this->assertTrue(Slim_Log::getLogger() instanceof CustomLogger);
    }

    /**
     * Test Slim initialization with custom view
     *
     * Pre-conditions:
     * Case A: Slim app initialized with string
     * Case B: Slim app initialized with View instance
     * Case C: Slim app initialized with array
     *
     * Post-conditions:
     * Case A: View is instance of CustomView
     * Case B: View is instance of CustomView
     * Case C: View is instance of CustomView
     */
    public function testSlimInitWithCustomView(){
        //Case A
        Slim::init('CustomView');
        $this->assertTrue(Slim::view() instanceof CustomView);
        //Case B
        Slim::init(new CustomView());
        $this->assertTrue(Slim::view() instanceOf CustomView);
        //Case C
        Slim::init(array('view' => 'CustomView'));
        $this->assertTrue(Slim::view() instanceOf CustomView);
    }

    /**
     * Test get Slim instance
     *
     * Pre-conditions:
     * Slim app initialized;
     *
     * Post-conditions:
     * The Slim app instance is returned
     */
    public function testSlimGetInstance() {
        Slim::init('CustomView');
        $app = Slim::getInstance();
        $this->assertTrue( $app instanceof Slim );
    }


    /************************************************
     * SLIM SETTINGS
     ************************************************/

    /**
     * Test Slim mode with ENV[SLIM_MODE]
     *
     * Pre-conditions:
     * SLIM_MODE environment variable set;
     * Slim app initialized with config mode;
     *
     * Post-conditions:
     * Only the production configuration is called;
     */
    public function testSlimModeEnvironment() {
        $this->expectOutputString('production mode');
        $_ENV['SLIM_MODE'] = 'production';
        Slim::init(array(
            'mode' => 'test'
        ));
        Slim::configureMode('test', function () {
            echo "test mode";
        });
        Slim::configureMode('production', function () {
            echo "production mode";
        });
    }

    /**
     * Test Slim mode with Config
     *
     * Pre-conditions:
     * ENV[SLIM_MODE] not set;
     * Slim app initialized with config mode;
     *
     * Post-conditions:
     * Only the test configuration is called;
     */
    public function testSlimModeConfig() {
        $this->expectOutputString('test mode');
        Slim::init(array(
            'mode' => 'test'
        ));
        Slim::configureMode('test', function () {
            echo "test mode";
        });
        Slim::configureMode('production', function () {
            echo "production mode";
        });
    }

    /**
     * Test Slim mode with default
     *
     * Pre-conditions:
     * ENV[SLIM_MODE] not set;
     * Slim app initialized without config mode;
     *
     * Post-conditions:
     * Only the development configuration is called;
     */
    public function testSlimModeDefault() {
        $this->expectOutputString('dev mode');
        Slim::init();
        Slim::configureMode('development', function () {
            echo "dev mode";
        });
        Slim::configureMode('production', function () {
            echo "production mode";
        });
    }

    /**
     * Test Slim defines one application setting
     *
     * Pre-conditions:
     * You have intiailized a Slim application, and you
     * set a single configuration setting.
     *
     * Post-conditions:
     * The configuration setting is set correctly.
     */
    public function testSlimConfigSetsOneSetting(){
        Slim::init();
        Slim::config('foo', 'bar');
        $this->assertEquals(Slim::config('foo'), 'bar');
    }

    /**
     * Test Slim setting is NULL if non-existant
     *
     * Pre-conditions:
     * You have intiailized a Slim application, and you
     * fetch a non-existing config setting.
     *
     * Post-conditions:
     * NULL is returned for the value of the setting
     */
    public function testSlimConfigIfSettingDoesNotExist(){
        Slim::init();
        $this->assertNull(Slim::config('foo'));
    }

    /**
     * Test Slim defines multiple settings with array
     *
     * Pre-conditions:
     * You have intiailized a Slim application, and you
     * pass an associative array into Slim::config
     *
     * Post-conditions:
     * Multiple settings are set correctly.
     */
    public function testSlimCongfigurationWithArray(){
        Slim::init();
        Slim::config(array(
            'one' => 'A',
            'two' => 'B',
            'three' => 'C'
        ));
        $this->assertEquals(Slim::config('one'), 'A');
        $this->assertEquals(Slim::config('two'), 'B');
        $this->assertEquals(Slim::config('three'), 'C');
    }

    /************************************************
     * SLIM ROUTING
     ************************************************/

    /**
     * Test Slim GET route
     *
     * Pre-conditions:
     * You have initialized a Slim app with a GET route.
     *
     * Post-conditions:
     * The GET route is returned, and its pattern and
     * callable are set correctly.
     */
    public function testSlimGetRoute(){
        Slim::init();
        $callable = function () { echo "foo"; };
        $route = Slim::get('/foo/bar', $callable);
        $this->assertEquals('/foo/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test Slim GET route with middleware
     *
     * Pre-conditions:
     * You have initialized a Slim app with a GET route and middleware
     *
     * Post-conditions:
     * The GET route is returned, and its pattern and
     * callable are set correctly.
     */
    public function testSlimGetRouteWithMiddleware(){ 
        Slim::init();
        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "foo"; };
        $route = Slim::get('/', $mw1, $mw2, $callable);
        $this->expectOutputString('foobarfoo');
        Slim::run();
    }

    /**
     * Test Slim sets POST route
     *
     * Pre-conditions:
     * You have initialized a Slim app with a POST route.
     *
     * Post-conditions:
     * The POST route is returned, and its pattern and
     * callable are set correctly.
     */
    public function testSlimPostRoute(){
        Slim::init();
        $callable = function () { echo "foo"; };
        $route = Slim::post('/foo/bar', $callable);
        $this->assertEquals('/foo/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test Slim POST route with middleware
     *
     * Pre-conditions:
     * You have initialized a Slim app with a POST route and middleware
     *
     * Post-conditions:
     * The POST route and its middleware are invoked
     */
    public function testSlimPostRouteWithMiddleware(){ 
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Slim::init();
        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "foo"; };
        $route = Slim::post('/', $mw1, $mw2, $callable);
        $this->expectOutputString('foobarfoo');
        Slim::run();
    }

    /**
     * Test Slim sets PUT route
     *
     * Pre-conditions:
     * You have initialized a Slim app with a PUT route.
     *
     * Post-conditions:
     * The PUT route is returned, and its pattern and
     * callable are set correctly.
     */
    public function testSlimPutRoute(){
        Slim::init();
        $callable = function () { echo "foo"; };
        $route = Slim::put('/foo/bar', $callable);
        $this->assertEquals('/foo/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test Slim PUT route with middleware
     *
     * Pre-conditions:
     * You have initialized a Slim app with a PUT route and middleware
     *
     * Post-conditions:
     * The PUT route and its middleware are invoked
     */
    public function testSlimPutRouteWithMiddleware(){ 
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        Slim::init();
        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "foo"; };
        $route = Slim::put('/', $mw1, $mw2, $callable);
        $this->expectOutputString('foobarfoo');
        Slim::run();
    }

    /**
     * Test Slim sets DELETE route
     *
     * Pre-conditions:
     * You have initialized a Slim app with a DELETE route.
     *
     * Post-conditions:
     * The DELETE route is returned and its pattern and
     * callable are set correctly.
     */
    public function testSlimDeleteRoute(){
        Slim::init();
        $callable = function () { echo "foo"; };
        $route = Slim::delete('/foo/bar', $callable);
        $this->assertEquals('/foo/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test Slim DELETE route with middleware
     *
     * Pre-conditions:
     * You have initialized a Slim app with a DELETE route and middleware
     *
     * Post-conditions:
     * The DELETE route and its middleware are invoked
     */
    public function testSlimDeleteRouteWithMiddleware(){ 
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        Slim::init();
        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "foo"; };
        $route = Slim::delete('/', $mw1, $mw2, $callable);
        $this->expectOutputString('foobarfoo');
        Slim::run();
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
        $this->setExpectedException('Slim_Exception_Stop');
        $_SERVER['REQUEST_URI'] = '/foo/bar/bob';
        Slim::init();
        Slim::get('/foo/bar/:name/', function ($name) {});
        Slim::run();
        $this->assertEquals(Slim::response()->status(), 301);
        $this->assertEquals(Slim::response()->header('Location'), '/foo/bar/bob/');
    }

    /**
     * Test Slim routing and trailing slashes
     *
     * Pre-conditions:
     * A route is defined that expects no trailing slash, but
     * the resource URI does have a trailing slash - but
     * otherwise matches the route pattern.
     *
     * Post-conditions:
     * Slim will send a 404 Not Found response
     */
    public function testRouteWithoutSlashAndUrlWith() {
        $this->setExpectedException('Slim_Exception_Stop');
        $_SERVER['REQUEST_URI'] = '/foo/bar/bob/';
        Slim::init();
        Slim::get('/foo/bar/:name', function ($name) {});
        Slim::run();
        $this->assertEquals(Slim::response()->status(), 404);
    }

    /**
     * Test Slim routing with URL encoded characters
     *
     * Pre-conditions:
     * Slim initialized;
     * Route defined and matches current request;
     * URL encoded spaces in URL
     *
     * Post-conditions:
     * Route matched;
     * Route callable invoked;
     * Route callable arguments are URL decoded;
     */
    public function testRouteWithUrlEncodedParameters() {
        $_SERVER['REQUEST_URI'] = '/foo/jo%20hn/smi%20th';
        Slim::init();
        Slim::get('/foo/:one/:two', function ($one, $two) {
            echo "$one and $two";
        });
        Slim::run();
        $this->expectOutputString('jo hn and smi th');
    }

    /************************************************
     * SLIM VIEW
     ************************************************/

    /**
     * Test Slim copies data from old View to new View
     *
     * Pre-conditions:
     * You have intialized a Slim app with a View;
     * You set data for the initial View;
     * You tell Slim to use a new View
     *
     * Post-conditions:
     * The data from the original view should be accessible
     * in the new View
     */
    public function testSlimCopiesViewData(){
        $data = array('foo' => 'bar');
        Slim::init();
        Slim::view()->setData($data);
        Slim::view('CustomView');
        $this->assertEquals($data, Slim::view()->getData());
    }

    /************************************************
     * SLIM RENDERING
     ************************************************/

    /**
     * Test Slim::render legacy
     *
     * Pre-conditions:
     * You have initialized a Slim app and render an existing
     * template. No Exceptions or Errors are thrown.
     *
     * Post-conditions:
     * The response status is 404;
     * The View data is set correctly;
     * The response status code is set correctly;
     * The response body is correct;
     */
    public function testSlimRenderSetsResponseStatusOk(){
        $this->expectOutputString('test output bar');
        Slim::init(array(
            'templates.path' => null,
            'templates_dir' => dirname(__FILE__) . '/templates'
        ));
        Slim::render('test.php', array('foo' => 'bar'), 404);
        $this->assertEquals(Slim::response()->status(), 404);
    }

    /**
     * Test Slim::render
     *
     * Pre-conditions:
     * You have initialized a Slim app and render an existing
     * template. No Exceptions or Errors are thrown.
     *
     * Post-conditions:
     * The response body is correct
     */
    public function testSlimRender(){
        $this->expectOutputString('test output bar');
        Slim::init(array(
            'templates.path' => dirname(__FILE__) . '/templates'
        ));
        Slim::render('test.php', array('foo' => 'bar'));
    }

    /************************************************
     * SLIM HTTP CACHING
     ************************************************/

    /**
     * Test Slim HTTP caching if ETag match
     *
     * Pre-conditions:
     * You have initialized a Slim application that sets an ETag
     * for a route. The HTTP `If-None-Match` header is set and matches
     * the ETag identifier value.
     *
     * Post-conditions:
     * The Slim application will return a 304 Not Modified response
     * because the ETag value matches `If-None-Match` request header.
     */
    public function testSlimEtagMatches(){
        $this->setExpectedException('Slim_Exception_Stop');
        Slim::init();
        Slim::get('/', function () {
            Slim::etag('abc123');
        });
        Slim::run();
        $this->assertEquals(304, Slim::response()->status());
    }

    /**
     * Test Slim HTTP caching if ETag does not match
     *
     * Pre-conditions:
     * You have initialized a Slim application that sets an ETag for the requested
     * resource route. The HTTP `If-None-Match` header is set and does not match
     * the ETag identifier value.
     *
     * Post-conditions:
     * The Slim application returns a 200 OK response because the
     * ETag does not match `If-None-Match` request header
     */
    public function testSlimEtagDoesNotMatch(){
        Slim::init();
        Slim::get('/', function () {
            Slim::etag('xyz789');
        });
        Slim::run();
        $this->assertEquals(200, Slim::response()->status());
    }

    /**
     * Test Slim::etag only accepts 'strong' or 'weak' types
     *
     * Pre-conditions:
     * You have initialized a Slim application that sets an ETag
     * with an invalid type argument.
     *
     * Post-conditions:
     * An InvalidArgumentException is thrown
     */
    public function testSlimETagThrowsExceptionForInvalidType(){
        $this->setExpectedException('InvalidArgumentException');
        Slim::init();
        Slim::get('/', function () {
            Slim::etag('123','foo');
        });
        Slim::run();
    }

    /**
     * Test Slim HTTP caching with Last Modified match
     *
     * Pre-conditions:
     * You have initialized a Slim application that sets the
     * `Last-Modified` response header for a route. The
     * HTTP `If-Modified-Since` header is set and matches the
     * `Last-Modified` date in the HTTP request.
     *
     * Post-conditions:
     * The Slim application will return an HTTP 304 Not Modified response
     * because the Last Modified date matches `If-Modified-Since` header.
     */
    public function testSlimLastModifiedDateMatches(){
        $this->setExpectedException('Slim_Exception_Stop');
        Slim::init();
        Slim::get('/', function () {
            Slim::lastModified(1286139652);
        });
        Slim::run();
        $this->assertEquals(304, Slim::response()->status());
    }

    /**
     * Test Slim HTTP caching if Last Modified does not match
     *
     * Pre-conditions:
     * You have initialized a Slim application that sets the `Last-Modified` response
     * header for the requested resource route. The HTTP `If-Modified-Since` header is
     * set and does not match the `Last-Modified` date.
     *
     * Post-conditions:
     * The Slim application will return an HTTP 200 OK response because
     * the Last Modified date does not match the `If-Modified-Since`
     * request header.
     */
    public function testSlimLastModifiedDateDoesNotMatch(){
        Slim::init();
        Slim::get('/', function () {
            Slim::lastModified(1286139250);
        });
        Slim::run();
        $this->assertEquals(200, Slim::response()->status());
    }

    /**
     * Test Slim Last Modified only accepts integer values
     *
     * Pre-conditions:
     * You have initialized a Slim application that sets the Last Modified
     * date for a route to a non-integer value.
     *
     * Post-conditions:
     * An InvalidArgumentException is thrown
     */
    public function testSlimLastModifiedOnlyAcceptsIntegers(){
        $this->setExpectedException('InvalidArgumentException');
        Slim::init();
        Slim::get('/', function () {
            Slim::lastModified('Test');
        });
        Slim::run();
    }

    /************************************************
     * SLIM COOKIES
     ************************************************/

    /**
     * Test Slim gets cookie
     *
     * Pre-conditions:
     * Cookie `foo` available in HTTP request;
     * Slim app initialized;
     * Case A: Cookie `foo` exists;
     * Case B: Cookie `bad` does not exist;
     *
     * Post-conditions:
     * Case A: Cookie `foo` value is "bar";
     * Case B: Cooke `bad` value is NULL;
     */
    public function testSlimGetsCookie() {
        Slim::init();
        //Case A
        $this->assertEquals(Slim::getCookie('foo'), 'bar');
        //Case B
        $this->assertNull(Slim::getCookie('doesNotExist'));
    }

    /**
     * Test Slim sets cookie with default time
     *
     * Pre-conditions:
     * Slim app initialized;
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
        Slim::init();
        $cj = Slim::response()->getCookieJar();
        //Case A
        $timeA = time();
        Slim::setCookie('myCookie1', 'myValue1');
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
        Slim::setCookie('myCookie3', 'myValue3', '1 hour');
        $cookieC = $cj->getResponseCookie('myCookie3');
        $this->assertEquals($timeC + 3600, $cookieC->getExpires());
        //Case D
        $timeD = time();
        Slim::setCookie('myCookie4', 'myValue4', 0);
        $cookieD = $cj->getResponseCookie('myCookie4');
        $this->assertEquals(0, $cookieD->getExpires());
    }

    /**
     * Test Slim sets encrypted cookie
     *
     * Pre-conditions:
     * Slim app initialized;
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
        Slim::init();
        $cj = Slim::response()->getCookieJar();
        //Case A
        $timeA = time();
        Slim::setEncryptedCookie('myCookie1', 'myValue1');
        $cookieA = $cj->getResponseCookie('myCookie1');
        $this->assertEquals('myCookie1', $cookieA->getName());
        $this->assertEquals($timeA + 1200, $cookieA->getExpires()); //default duration is 20 minutes
        $this->assertEquals('/', $cookieA->getPath());
        $this->assertEquals('', $cookieA->getDomain());
        $this->assertFalse($cookieA->getSecure());
        $this->assertFalse($cookieA->getHttpOnly());
        //Case C
        $timeC = time();
        Slim::setEncryptedCookie('myCookie3', 'myValue3', '1 hour');
        $cookieC = $cj->getResponseCookie('myCookie3');
        $this->assertEquals($timeC + 3600, $cookieC->getExpires());
        //Case D
        $timeD = time();
        Slim::setEncryptedCookie('myCookie4', 'myValue4', 0);
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
        Slim::init();
        $cj = Slim::response()->getCookieJar();
        //Case A
        Slim::setCookie('foo1', 'bar1');
        $this->assertEquals('bar1', $cj->getResponseCookie('foo1')->getValue());
        $this->assertTrue($cj->getResponseCookie('foo1')->getExpires() > time());
        Slim::deleteCookie('foo1');
        $this->assertEquals('', Slim::getCookie('foo1'));
        $this->assertTrue($cj->getResponseCookie('foo1')->getExpires() < time());
        //Case B
        Slim::setEncryptedCookie('foo2', 'bar2');
        $this->assertTrue(strlen($cj->getResponseCookie('foo2')->getValue()) > 0);
        $this->assertTrue($cj->getResponseCookie('foo2')->getExpires() > time());
        Slim::deleteCookie('foo2');
        $this->assertEquals('', $cj->getResponseCookie('foo2')->getValue());
        $this->assertTrue($cj->getResponseCookie('foo2')->getExpires() < time());
    }

    /************************************************
     * SLIM HELPERS
     ************************************************/

    /**
     * Test Slim Stop
     *
     * Pre-conditions:
     * You have initialized a Slim application and stop
     * the application inside of a route callback.
     *
     * Post-conditions:
     * A SlimStopException is thrown;
     * The response is unaffected by code after Slim::stop is called
     */
    public function testSlimStop() {
        Slim::init();
        Slim::get('/', function () {
            try {
                echo "foo";
                Slim::stop();
                echo "bar";
            } catch ( Slim_Exception_Stop $e ) {}
        });
        Slim::run();
        $this->assertEquals(Slim::response()->body(), 'foo');
    }

    /**
     * Test Slim::halt inside route callback
     *
     * Pre-conditions:
     * Slim::halt is invoked inside a route callback
     *
     * Post-conditions:
     * The new response should be set correctly, and preivous
     * and subsequent invocations from within the route
     * callback are ignored.
     */
    public function testSlimHaltInsideCallback() {
        $this->setExpectedException('Slim_Exception_Stop');
        Slim::init();
        Slim::get('/', function () {
            echo "foo";
            Slim::halt(404, 'Halt not found');
            echo "bar";
        });
        Slim::run();
        $this->assertEquals(Slim::response()->status(), 404);
        $this->assertEquals(Slim::response()->body(), 'Halt not found');
    }

    /**
     * Test Slim::halt outside route callback
     *
     * Pre-conditions:
     * Slim::halt is invoked outside of a route callback
     *
     * Post-conditions:
     * The new response should be returned with the expected
     * status code and body, regardless of the current route
     * callback's expected output.
     */
    public function testSlimHaltOutsideCallback() {
        $this->setExpectedException('Slim_Exception_Stop');
        Slim::init();
        Slim::halt(500, 'External error');
        Slim::get('/', function () {
            echo "foo";
        });
        Slim::run();
        $this->assertEquals(Slim::response()->status(), 500);
        $this->assertEquals(Slim::response()->body(), 'External error');
    }

    /**
     * Test Slim::pass continues to next matching route
     *
     * Pre-conditions:
     * You have initialized a Slim application with two accesible routes.
     * The first matching route should be the most specific and should
     * invoke Slim::pass(). The second accessible route should be
     * the next matching route.
     *
     * Post-conditions:
     * The response body should be set by the second matching route.
     */
    public function testSlimPassWithFallbackRoute() {
        $_SERVER['REQUEST_URI'] = "/name/Frank";
        Slim::init();
        Slim::get('/name/Frank', function (){
            echo "Your name is Frank";
            Slim::pass();
        });
        Slim::get('/name/:name', function ($name) {
            echo "I think your name is $name";
        });
        Slim::run();
        $this->assertEquals(Slim::response()->body(), "I think your name is Frank");
    }

    /**
     * Test Slim::pass continues, but next matching route not found
     *
     * Pre-conditions:
     * You have initialized a Slim application with one accesible route.
     * The first matching route should be the most specific and should
     * invoke Slim::pass().
     *
     * Post-conditions:
     * No second matching route is found, and a HTTP 404 response is
     * sent to the client.
     */
    public function testSlimPassWithoutFallbackRoute() {
        $this->setExpectedException('Slim_Exception_Stop');
        $_SERVER['REQUEST_URI'] = "/name/Frank";
        Slim::init();
        Slim::get('name/Frank', function (){
            echo "Your name is Frank";
            Slim::pass();
        });
        Slim::run();
        $this->assertEquals(Slim::response()->status(), 404);
    }

    /**
     * Test Slim::contentType
     *
     * Pre-conditions:
     * You have initialized a Slim app and set the Content-Type
     * HTTP response header.
     *
     * Post-conditions:
     * The Response content type header is set correctly.
     */
    public function testSlimContentType(){
        Slim::init();
        Slim::contentType('image/jpeg');
        $this->assertEquals(Slim::response()->header('Content-Type'), 'image/jpeg');
    }

    /**
     * Test Slim::status
     *
     * Pre-conditions:
     * You have initialized a Slim app and set the status code.
     *
     * Post-conditions:
     * The Response status code is set correctly.
     */
    public function testSlimStatus(){
        Slim::init();
        Slim::status(302);
        $this->assertSame(Slim::response()->status(), 302);

        $this->setExpectedException('InvalidArgumentException');
        Slim::init();
        Slim::status(900);
    }

    /**
     * Test Slim URL For
     *
     * Pre-conditions:
     * You have initialized a Slim app with a named route.
     *
     * Post-conditions:
     * Slim returns an accurate URL for the named route.
     */
    public function testSlimUrlFor(){
        Slim::init();
        Slim::get('/hello/:name', function () {})->name('hello');
        $this->assertEquals('/hello/Josh', Slim::urlFor('hello', array('name' => 'Josh')));
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
     * Case A: An InvalidArgumentException is thrown
     * Case B: An InvalidArgumentException is thrown
     * Case C: Response code is 300
     * Case D: Response code is 302
     * Case E: Response code is 307
     */
    public function testSlimRedirect() {
        //Case A
        Slim::init();
        Slim::get('/', function () {
            Slim::redirect('/foo', 200);
        });
        try {
            Slim::run();
            $this->fail('InvalidArgumentException not caught');
        } catch( InvalidArgumentException $e ) {}

        //Case B
        Slim::init();
        Slim::get('/', function () {
            Slim::redirect('/foo', 308);
        });
        try {
            Slim::run();
            $this->fail('InvalidArgumentException not caught');
        } catch( InvalidArgumentException $e ) {}

        //Case C
        Slim::init();
        Slim::get('/', function () {
            Slim::redirect('/foo', 300);
        });
        try {
            Slim::run();
            $this->fail("SlimStopException not caught");
        } catch ( Slim_Exception_Stop $e ) {}
        $this->assertEquals(Slim::response()->status(), 300);

        //Case D
        Slim::init();
        Slim::get('/', function () {
            Slim::redirect('/foo', 302);
        });
        try {
            Slim::run();
            $this->fail("SlimStopException not caught");
        } catch ( Slim_Exception_Stop $e ) {}
        $this->assertEquals(Slim::response()->status(), 302);

        //Case E
        Slim::init();
        Slim::get('/', function () {
            Slim::redirect('/foo', 307);
        });
        try {
            Slim::run();
            $this->fail("SlimStopException not caught");
        } catch ( Slim_Exception_Stop $e ) {}
        $this->assertEquals(Slim::response()->status(), 307);
    }

    /************************************************
     * SLIM ERROR AND EXCEPTION HANDLING
     ************************************************/

    /**
     * Test Slim Not Found handler
     *
     * Pre-conditions:
     * You have initialized a Slim app with a NotFound handler and
     * a route that does not match the mock HTTP request.
     *
     * Post-conditions:
     * The response status will be 404
     */
    public function testSlimRouteNotFound() {
        $this->setExpectedException('Slim_Exception_Stop');
        Slim::init();
        Slim::get('/foo', function () {});
        Slim::run();
        $this->assertEquals(Slim::response()->status(), 404);
    }

    /**
     * Test Slim returns 500 response if error thrown within Slim app
     *
     * Pre-conditions:
     * You have initialized a Slim app with a custom Error handler with
     * an accessible route that calls Slim::error().
     *
     * Post-conditions:
     * The response status will be 500
     */
    public function testSlimError() {
        $this->setExpectedException('Slim_Exception_Stop');
        Slim::init();
        Slim::get('/', function () { Slim::error(); });
        Slim::run();
        $this->assertEquals(Slim::response()->status(), 500);
    }

    /**
     * Test Slim returns 200 OK for successful route
     *
     * Pre-conditions:
     * You have initialized a Slim app with an accessible route that
     * does not throw any Exceptions and does not set a custom status.
     *
     * Post-conditions:
     * The response status is 200 and response body is as expected.
     */
    public function testSlimOkResponse() {
        Slim::init();
        Slim::get('/', function () { echo "Ok"; });
        Slim::run();
        $this->assertEquals(Slim::response()->status(), 200);
        $this->assertEquals(Slim::response()->body(), 'Ok');
    }

    /************************************************
     * SLIM HOOKS
     ************************************************/

    /**
     * Test hook listener
     *
     * Pre-conditions:
     * Slim app initialized;
     * Hook name does not exist;
     * Listeners are callable objects;
     *
     * Post-conditions:
     * Callables are invoked in expected order
     */
    public function testRegistersAndCallsHooksByPriority() {
        $this->expectOutputString('barfoo');
        Slim::init();
        $callable1 = function () { echo "foo"; };
        $callable2 = function () { echo "bar"; };
        Slim::hook('test.hook.one', $callable1); //default is 10
        Slim::hook('test.hook.one', $callable2, 8);
        Slim::applyHook('test.hook.one');
    }

    /**
     * Test hook listener if listener is not callable
     *
     * Pre-conditions:
     * Slim app initialized;
     * Hook name does not exist;
     * Listener is NOT a callable object
     *
     * Post-conditions:
     * Hook is created;
     * Callable is NOT assigned to hook;
     */
    public function testHookInvalidCallable() {
        Slim::init();
        $callable = 'test'; //NOT callable
        Slim::hook('test.hook.one', $callable);
        $this->assertEquals(array(array()), Slim::getHooks('test.hook.one'));
    }

    /**
     * Test hook invocation if hook does not exist
     *
     * Pre-conditions:
     * Slim app intialized;
     * Hook name does not exist;
     *
     * Post-conditions:
     * Hook is created;
     * Hook initialized with empty array
     */
    public function testHookInvocationIfNotExists() {
        Slim::init();
        Slim::applyHook('test.hook.one');
        $this->assertEquals(array(array()), Slim::getHooks('test.hook.one'));
    }

    /**
     * Test clear hooks
     *
     * Pre-conditions:
     * Slim app initialized
     * Two hooks exist, each with one listener
     *
     * Post-conditions:
     * Case A: Listeners for 'test.hook.one' are cleared
     * Case B: Listeners for all hooks are cleared
     */
    public function testHookClear() {
        Slim::init();
        Slim::hook('test.hook.one', function () {});
        Slim::hook('test.hook.two', function () {});
        Slim::clearHooks('test.hook.two');
        $this->assertEquals(array(array()), Slim::getHooks('test.hook.two'));
        $hookOne = Slim::getHooks('test.hook.one');
        $this->assertTrue(count($hookOne[10]) === 1);
        Slim::clearHooks();
        $this->assertEquals(array(array()), Slim::getHooks('test.hook.one'));
    }

    /**
     * Test hook filter behavior
     *
     */
    public function testHookFilterBehavior() {
        Slim::init();
        Slim::hook('test.hook', function ($arg) { return $arg . 'foo'; });
        $this->assertEquals('barfoo', Slim::applyHook('test.hook', 'bar'));
    }

}

