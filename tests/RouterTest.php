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

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once '../slim/Router.php';
require_once '../slim/Route.php';
require_once 'PHPUnit/Framework.php';

//Mock Request object
class Request {
	public $root;
	public $method;
	public $resource;
	public function __construct($method, $resource) {
		$this->root = '/';
		$this->method = $method;
		$this->resource = $resource;
	}
}

class RouterTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * Router::urlFor should return a full route pattern
	 * even if no params data is provided.
	 */
	public function testUrlForNamedRouteWithoutParams() {
		$request = new Request('GET', '/');
		$router = new Router($request);
		$route = $router->map('/foo/bar', function () {}, 'GET');
		$router->cacheNamedRoute('foo', $route);
		$this->assertEquals($router->urlFor('foo'), '/foo/bar');
	}
	
	/**
	 * Router::urlFor should eturn a full route pattern if
	 * param data is provided.
	 */
	public function testUrlForNamedRouteWithParams() {
		$request = new Request('GET', '/');
		$router = new Router($request);
		$route = $router->map('/foo/:one/and/:two', function ($one, $two) {}, 'GET');
		$router->cacheNamedRoute('foo', $route);
		$this->assertEquals($router->urlFor('foo', array('one' => 'Josh', 'two' => 'John')), '/foo/Josh/and/John');
	}
	
	/**
	 * Router::urlFor should throw an exception if Route with name
	 * does not exist.
	 */
	public function testUrlForNamedRouteThatDoesNotExist() {
		$this->setExpectedException('RuntimeException');
		$request = new Request('GET', '/');
		$router = new Router($request);
		$route = $router->map('/foo/bar', function () {}, 'GET');
		$router->cacheNamedRoute('bar', $route);
		$router->urlFor('foo');
	}
	
	/**
	 * Router::cacheNamedRoute should throw na exception if named Route
	 * with same name already exists.
	 */
	public function testNamedRouteWithExistingName() {
		$this->setExpectedException('RuntimeException');
		$request = new Request('GET', '/');
		$router = new Router($request);
		$route1 = $router->map('/foo/bar', function () {}, 'GET');
		$route2 = $router->map('/foo/bar/2', function () {}, 'GET');
		$router->cacheNamedRoute('bar', $route1);
		$router->cacheNamedRoute('bar', $route2);
	}
	
	/**
	 * Router should keep reference to a callable NotFound callback
	 */
	public function testNotFoundHandler() {
		$request = new Request('GET', '/');
		$router = new Router($request);
		$notFoundCallback = function () { echo "404"; };
		$callback = $router->notFound($notFoundCallback);
		$this->assertSame($notFoundCallback, $callback);
	}
	
	/**
	 * Router should NOT keep reference to a callback that is not callable
	 */
	public function testNotFoundHandlerIfNotCallable() {
		$request = new Request('GET', '/');
		$router = new Router($request);
		$notFoundCallback = 'foo';
		$callback = $router->notFound($notFoundCallback);
		$this->assertEquals($callback, null);
	}
	
}

?>