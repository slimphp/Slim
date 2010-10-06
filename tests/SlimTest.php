<?php
/**
 * Slim
 *
 * A simple PHP framework for PHP 5 or newer
 *
 * @author		Josh Lockhart <info@joshlockhart.com>
 * @link		http://slim.joshlockhart.com
 * @copyright	2010 Josh Lockhart
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

require_once '../slim/Slim.php';
require_once 'PHPUnit/Framework.php';

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
$_SERVER['DOCUMENT_ROOT'] = rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR);
$_SERVER['SERVER_ADMIN'] = "you@example.com";
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
$_SERVER['REMOTE_PORT'] = "55426";
$_SERVER['REDIRECT_URL'] = "/";
$_SERVER['GATEWAY_INTERFACE'] = "CGI/1.1";
$_SERVER['SERVER_PROTOCOL'] = "HTTP/1.1";
$_SERVER['REQUEST_METHOD'] = "GET";
$_SERVER['QUERY_STRING'] = "";
$_SERVER['REQUEST_URI'] = "/";
$_SERVER['SCRIPT_NAME'] = basename(__FILE__);
$_SERVER['PHP_SELF'] = '/'.basename(__FILE__);
$_SERVER['REQUEST_TIME'] = "1285647051";
$_SERVER['argv'] = array();
$_SERVER['argc'] = 0;

//Mock custom view
class CustomView extends View {
	function render($template) { echo "Custom view"; }
}

class SlimTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
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
	 * You have initialized a Slim application
	 *
	 * Post-conditions:
	 * Slim should have a default NotFound handler that is callable;
	 * Slim should have a View
	 */
	public function testSlimInitialization() {
		Slim::init();
		$this->assertTrue(is_callable(Slim::router()->notFound()));
		$this->assertTrue(Slim::view() instanceof View);
	}
	
	/**
	 * Test Slim initialization with custom view
	 *
	 * Pre-conditions:
	 * You have initialized a Slim application with a custom View
	 * 
	 * Post-conditions:
	 * Slim should have a View of a given custom class
	 */
	public function testSlimInitializationWithCustomView(){
		Slim::init('CustomView');
		$this->assertTrue(Slim::view() instanceof CustomView);
	}
	
	/************************************************
	 * SLIM SETTINGS
	 ************************************************/
	
	/**
	 * Test Slim can define an application setting
	 *
	 * Pre-conditions:
	 * You have intiailized a Slim application, and you
	 * set a single configuration setting.
	 *
	 * Post-conditions:
	 * The configuration setting is accessible.
	 */
	public function testSlimSetsSingleSetting(){
		Slim::init();
		Slim::config('foo', 'bar');
		$this->assertEquals(Slim::config('foo'), 'bar');
	}
	
	/**
	 * Test Slim configuration setting is NULL if non-existant
	 *
	 * Pre-conditions:
	 * You have intiailized a Slim application, and you
	 * fetch a non-existing configuration setting.
	 *
	 * Post-conditions:
	 * NULL is returned for the value of the setting
	 */
	public function testSlimNonExistingSettingValueIsNull(){
		Slim::init();
		$this->assertNull(Slim::config('foo'));
	}
	
	/**
	 * Test Slim can use array to set configuration settings
	 *
	 * Pre-conditions:
	 * You have intiailized a Slim application, and you
	 * set multiple settings with an associative array.
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
	 * Test Slim sets GET route
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app with a GET route.
	 *
	 * Post-conditions:
	 * The GET route is returned, and its pattern and
	 * callable are as expected.
	 */
	public function testSlimGetRoute(){
		Slim::init();
		$callable = function () { echo "foo"; };
		$route = Slim::get('/foo/bar', $callable);
		$this->assertEquals('foo/bar', $route->pattern());
		$this->assertSame($callable, $route->callable());
	}
	
	/**
	 * Test Slim sets POST route
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app with a POST route.
	 *
	 * Post-conditions:
	 * The POST route is returned, and its pattern and
	 * callable are as expected.
	 */
	public function testSlimPostRoute(){
		Slim::init();
		$callable = function () { echo "foo"; };
		$route = Slim::post('/foo/bar', $callable);
		$this->assertEquals('foo/bar', $route->pattern());
		$this->assertSame($callable, $route->callable());
	}
	
	/**
	 * Test Slim sets PUT route
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app with a PUT route.
	 *
	 * Post-conditions:
	 * The PUT route is returned, and its pattern and
	 * callable are as expected.
	 */
	public function testSlimPutRoute(){
		Slim::init();
		$callable = function () { echo "foo"; };
		$route = Slim::put('/foo/bar', $callable);
		$this->assertEquals('foo/bar', $route->pattern());
		$this->assertSame($callable, $route->callable());
	}
	
	/**
	 * Test Slim sets DELETE route
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app with a DELETE route.
	 *
	 * Post-conditions:
	 * The DELETE route is returned and its pattern and
	 * callable are as expected.
	 */
	public function testSlimDeleteRoute(){
		Slim::init();
		$callable = function () { echo "foo"; };
		$route = Slim::delete('/foo/bar', $callable);
		$this->assertEquals('foo/bar', $route->pattern());
		$this->assertSame($callable, $route->callable());
	}
	
	/************************************************
	 * SLIM BEFORE AND AFTER CALLBACKS
	 ************************************************/
	
	/**
	 * Test Slim runs Before and After callbacks
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app with an accessible route
	 * that does not throw Exceptions or Errors. You append the Response
	 * body in the before and after callbacks.
	 *
	 * Post-conditions:
	 * The response body is as expected.
	 */
	public function testSlimRunsBeforeAndAfterCallbacks() {
		Slim::init();
		Slim::before(function () { Slim::response()->write('One '); });
		Slim::before(function () { Slim::response()->write('Two '); });
		Slim::after(function () { Slim::response()->write('Four '); });
		Slim::after(function () { Slim::response()->write('Five'); });
		Slim::get('/', function () { echo 'Three '; });
		Slim::run();
		$this->assertEquals(Slim::response()->body(), 'One Two Three Four Five');
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
		Slim::view()->data($data);
		Slim::view('CustomView');
		$this->assertEquals($data, Slim::view()->data());
	}
	
	/************************************************
	 * SLIM RENDERING
	 ************************************************/
	
	/**
	 * Test Slim sets HTTP OK status when rendering
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app and render an existing template
	 * and no Exceptions or Errors are thrown.
	 *
	 * Post-conditions:
	 * The HTTP response status is 200
	 */
	public function testSlimRenderSetsResponseStatusOk(){
		Slim::init();
		Slim::render('test.php', array('foo' => 'bar'));
		$this->assertEquals(Slim::response()->status(), 200);
	}
	
	/**
	 * Test Slim sets view data when rendering
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app and render an existing template
	 *
	 * Post-conditions:
	 * The render method passes array of data into View
	 */
	public function testSlimRenderSetsViewData(){
		Slim::init();
		$data = array('foo' => 'bar');
		Slim::render('test.php', $data);
		$this->assertSame(Slim::view()->data(), $data);
	}
	
	/**
	 * Test Slim sets custom status code
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app and render a template while
	 * specifying a non-200 status code.
	 *
	 * Post-conditions:
	 * The HTTP response status code is set correctly.
	 */
	public function testSlimRenderSetsStatusCode(){
		Slim::init();
		$data = array('foo' => 'bar');
		Slim::render('test.php', $data, 400);
		$this->assertEquals(Slim::response()->status(), 400);
	}
	
	/************************************************
	 * SLIM SESSIONS
	 ************************************************/
	
	/**
	 * Test Slim returns existing Cookie value
	 *
	 * Pre-conditions:
	 * You have initialized a Slim application and
	 * there are existing Cookies sent with the HTTP request.
	 *
	 * Post-conditions:
	 * Slim will return the correct value for the Cookie
	 */
	public function testSlimReadsExistingCookieValue(){
		Slim::init();
		$this->assertEquals('bar', Slim::session('foo'));
	}
	
	/**
	 * Test Slim returns NULL for non-existing Cookie value
	 *
	 * Pre-conditions:
	 * You have initialized a Slim application and there are
	 * no existing Cookies of a given name with the HTTP request.
	 *
	 * Post-conditions:
	 * Slim returns NULL when a non-existing Cookie value is requested
	 */
	public function testSlimReadsNonExistingCookieValueAsNull(){
		Slim::init();
		$this->assertNull(Slim::session('fake'));
	}
	
	/**
	 * Test Slim sets Cookies in Response
	 *
	 * Pre-conditions:
	 * You have initialized a Slim application and you
	 * set a session variable.
	 *
	 * Post-conditions:
	 * The Response has a Cookie object with the expected
	 * name and value.
	 */
	public function testSlimSetsCookie(){
		Slim::init();
		Slim::session('testCookie', 'testValue');
		$cookies = Slim::response()->getCookies();
		$this->assertEquals('testCookie', $cookies[0]->name);
		$this->assertEquals('testValue', $cookies[0]->value);
	}
	
	/************************************************
	 * SLIM HTTP CACHING
	 ************************************************/
	
	/**
	 * Test Slim returns 304 Not Modified when ETag matches `If-None-Match` request header
	 *
	 * Pre-conditions:
	 * You have initialized a Slim application that sets an ETag for the requested
	 * resource route. The HTTP `If-None-Match` header is set and matches
	 * the ETag identifier value.
	 *
	 * Post-conditions:
	 * The Slim application will return an HTTP 304 Not Modified response.
	 */
	public function testSlimEtagMatches(){
		Slim::init();
		Slim::get('/', function () {
			Slim::etag('abc123');
		});
		Slim::run();
		$this->assertEquals(304, Slim::response()->status());
	}
	
	/**
	 * Test Slim returns 200 OK when ETag does not match `If-None-Match` request header
	 *
	 * Pre-conditions:
	 * You have initialized a Slim application that sets an ETag for the requested
	 * resource route. The HTTP `If-None-Match` header is set and does not match
	 * the ETag identifier value.
	 *
	 * Post-conditions:
	 * The Slim application will return an HTTP 200 OK response.
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
	 * Test Slim ETag only accepts 'strong' or 'weak' types
	 *
	 * Pre-conditions:
	 * You have initialized a Slim application that sets an ETag with an
	 * invalid type argument.
	 *
	 * Post-conditions:
	 * An InvalidArgumentExceptio is thrown
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
	 * Test Slim returns 304 Not Modified when Last Modified date matches `If-Modified-Since` request header
	 *
	 * Pre-conditions:
	 * You have initialized a Slim application that sets the `Last-Modified` response
	 * header for the requested resource route. The HTTP `If-Modified-Since` header is
	 * set and matches the `Last-Modified` date.
	 *
	 * Post-conditions:
	 * The Slim application will return an HTTP 304 Not Modified response
	 */
	public function testSlimLastModifiedDateMatches(){
		Slim::init();
		Slim::get('/', function () {
			Slim::lastModified(1286139652);
		});
		Slim::run();
		$this->assertEquals(304, Slim::response()->status());
	}
	
	/**
	 * Test Slim returns 200 OK when Last Modified date does not match `If-Modified-Since` request header
	 *
	 * Pre-conditions:
	 * You have initialized a Slim application that sets the `Last-Modified` response
	 * header for the requested resource route. The HTTP `If-Modified-Since` header is
	 * set and does not match the `Last-Modified` date.
	 *
	 * Post-conditions:
	 * The Slim application will return an HTTP 200 OK response
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
	 * SLIM HELPERS
	 ************************************************/
	
	/**
	 * Test Slim sets ContentType header
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app and set the Content-Type
	 * HTTP response header.
	 *
	 * Post-conditions:
	 * The Response content type header is set correctly.
	 */
	public function testSlimContentTypeHelperSetsResponseContentType(){
		Slim::init();
		Slim::contentType('image/jpeg');
		$this->assertEquals(Slim::response()->header('Content-Type'), 'image/jpeg');
	}
	
	/**
	 * Test Slim sets status code
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app and set the status code.
	 *
	 * Post-conditions:
	 * The Response status code is set correctly.
	 */
	public function testSlimStatusHelperSetsResponseStatusCode(){
		Slim::init();
		Slim::status(302);
		$this->assertSame(Slim::response()->status(), 302);
	}
	
	/**
	 * Test Slim URL For
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app with an accessible named route.
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
	 * Test Slim::redirect supports 301 permanent redirects
	 *
	 * Pre-conditions:
	 * You have initialized a Slim application with an accessible route.
	 * The route invokes the Slim::redirect helper.
	 *
	 * Post-conditions:
	 * The Response status code is set correctly
	 */
	public function testSlimRedirectPermanent() {
		Slim::init();
		Slim::get('/', function () {
			Slim::redirect('/foo', 301);
		});
		Slim::run();
		$this->assertEquals(Slim::response()->status(), 301);
	}
	
	/**
	 * Test Slim::redirect supports 302 temporary redirects
	 *
	 * Pre-conditions:
	 * You have initialized a Slim application with an accessible route.
	 * The route invokes the Slim::redirect helper.
	 *
	 * Post-conditions:
	 * The Response status code is set correctly
	 */
	public function testSlimRedirectTemporary() {
		Slim::init();
		Slim::get('/', function () {
			Slim::redirect('/foo', 307);
		});
		Slim::run();
		$this->assertEquals(Slim::response()->status(), 307);
	}
	
	/**
	 * Test Slim::redirect fails if status is not 301 or 302
	 *
	 * Pre-conditions:
	 * You have initialized a Slim application with an accessible route.
	 * The route attempts to redirect with an invalid HTTP status code.
	 *
	 * Post-conditions:
	 * Slim throws an InvalidArgumentException
	 */
	public function testSlimRedirectFailsAndThrowsException() {
		$this->setExpectedException('InvalidArgumentException');
		Slim::init();
		Slim::get('/', function () {
			Slim::redirect('/foo', 400);
		});
		Slim::run();
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
		Slim::get('name/Frank', function (){
			echo "Your name is Frank";
			Slim::pass();
		});
		Slim::get('name/:name', function ($name) {
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
		$_SERVER['REQUEST_URI'] = "/name/Frank";
		Slim::init();
		Slim::get('name/Frank', function (){
			echo "Your name is Frank";
			Slim::pass();
		});
		Slim::run();
		$this->assertEquals(Slim::response()->status(), 404);
	}
	
	/************************************************
	 * SLIM ERROR AND EXCEPTION HANDLING
	 ************************************************/
	
	/**
	 * Test Slim raises SlimException
	 *
	 * Pre-conditions:
	 * None
	 *
	 * Post-conditions:
	 * A SlimException is thrown with the expected status code and message
	 */
	public function testSlimRaisesSlimException(){
		try {
			Slim::raise(404, 'Page Not Found');
			$this->fail('SlimException not caught');
		} catch ( SlimException $e ) {
			$this->assertEquals($e->getCode(), 404);
			$this->assertEquals($e->getMessage(), 'Page Not Found');
		}
	}
	
	/**
	 * Test SlimException sets Response
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app with an accessible route 
	 * and raise a SlimException in that route.
	 *
	 * Post-conditions:
	 * The response status will match the code and message of the SlimException
	 */
	public function testSlimRaiseSetsResponse() {
		Slim::init();
		Slim::get('/', function () {
			Slim::raise(501, 'Error!');
		});
		Slim::run();
		$this->assertEquals(Slim::response()->status(), 501);
		$this->assertEquals(Slim::response()->body(), 'Error!');
	}
	
	/**
	 * Test Slim returns 404 response if route not found
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app with a NotFound handler and
	 * a route that does not match the mock HTTP request.
	 *
	 * Post-conditions:
	 * The response status will be 404
	 */
	public function testSlimRouteNotFound() {
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
	
}

?>