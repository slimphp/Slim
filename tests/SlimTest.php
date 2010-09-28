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

	/**
	 * Test Slim initialization
	 *
	 * Pre-conditions:
	 * None
	 *
	 * Post-conditions:
	 * Slim should have a default NotFound handler that is callable
	 */
	public function testSlimInitialization() {
		Slim::init();
		$notFound = Slim::router()->notFound();
		$this->assertTrue(is_callable($notFound));
	}
	
	/**
	 * Test Slim initialization with custom view
	 *
	 * Pre-conditions:
	 * None
	 * 
	 * Post-conditions:
	 * Slim should have a View of a given custom class
	 */
	public function testSlimInitializationWithCustomView(){
		Slim::init('CustomView');
		$view = Slim::view();
		$this->assertTrue($view instanceof CustomView);
	}
	
	/**
	 * Test Slim sets GET route
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app
	 *
	 * Post-conditions:
	 * A Route is returned with same pattern and callable
	 */
	public function testSlimGetRoute(){
		Slim::init();
		$callable = function () { echo "foo"; };
		$route = Slim::get('/foo/bar', $callable);
		$routeCallable = $route->callable();
		$this->assertEquals('foo/bar', $route->pattern());
		$this->assertSame($callable, $routeCallable);
	}
	
	/**
	 * Test Slim sets POST route
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app
	 *
	 * Post-conditions:
	 * A Route is returned with same pattern and callable
	 */
	public function testSlimPostRoute(){
		Slim::init();
		$callable = function () { echo "foo"; };
		$route = Slim::post('/foo/bar', $callable);
		$routeCallable = $route->callable();
		$this->assertEquals('foo/bar', $route->pattern());
		$this->assertSame($callable, $routeCallable);
	}
	
	/**
	 * Test Slim sets PUT route
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app
	 *
	 * Post-conditions:
	 * A Route is returned with same pattern and callable
	 */
	public function testSlimPutRoute(){
		Slim::init();
		$callable = function () { echo "foo"; };
		$route = Slim::put('/foo/bar', $callable);
		$routeCallable = $route->callable();
		$this->assertEquals('foo/bar', $route->pattern());
		$this->assertSame($callable, $routeCallable);
	}
	
	/**
	 * Test Slim sets DELETE route
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app
	 *
	 * Post-conditions:
	 * A Route is returned with same pattern and callable
	 */
	public function testSlimDeleteRoute(){
		Slim::init();
		$callable = function () { echo "foo"; };
		$route = Slim::delete('/foo/bar', $callable);
		$routeCallable = $route->callable();
		$this->assertEquals('foo/bar', $route->pattern());
		$this->assertSame($callable, $routeCallable);
	}
	
	/**
	 * Test Slim has and returns Request object
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app
	 *
	 * Post-conditions:
	 * A Request object is returned for the current HTTP request
	 */
	public function testSlimReturnsRequestObject(){
		Slim::init();
		$request = Slim::request();
		$this->assertTrue($request instanceof Request);
	}
	
	/**
	 * Test Slim sets custom View
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app without a custom view
	 *
	 * Post-conditions:
	 * The Slim app's view is an object of the same Class as you specify
	 */
	public function testSlimSetsView(){
		Slim::init();
		Slim::view('CustomView');
		$view = Slim::view();
		$this->assertTrue($view instanceof CustomView);
	}
	
	/**
	 * Test Slim sets default view when rendering if no view set
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app without specifying a custom view
	 *
	 * Post-conditions:
	 * The render method sets a default view, "View", used to render templates
	 */
	public function testSlimRenderSetsViewIfNoViewSet(){
		Slim::init();
		Slim::render('test.php', array('foo' => 'bar'));
		$this->assertTrue(Slim::view() instanceof View);
	}
	
	/**
	 * Test Slim sets default view when rendering if no view set
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app
	 *
	 * Post-conditions:
	 * The render method sets the default HTTP status as 200
	 */
	public function testSlimRenderSetsResponseStatusOk(){
		Slim::init();
		Slim::render('test.php', array('foo' => 'bar'));
		$this->assertEquals(Slim::response()->status(), 200);
	}
	
	/**
	 * Test Slim sets view data
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app
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
	 * You have initialized a Slim app
	 *
	 * Post-conditions:
	 * The render method sets custom status code
	 */
	public function testSlimRenderSetsStatusCode(){
		Slim::init();
		$data = array('foo' => 'bar');
		Slim::render('test.php', $data, 400);
		$this->assertEquals(Slim::response()->status(), 400);
	}
	
	/**
	 * Test Slim sets ContentType header
	 *
	 * Pre-conditions:
	 * You have initialized a Slim app
	 *
	 * Post-conditions:
	 * The Response content type header is set
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
	 * You have initialized a Slim app
	 *
	 * Post-conditions:
	 * The Response status code is set
	 */
	public function testSlimStatusHelperSetsResponseStatusCode(){
		Slim::init();
		Slim::status(302);
		$this->assertSame(Slim::response()->status(), 302);
	}
	
}

?>