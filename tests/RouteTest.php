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

require_once '../slim/Route.php';
require_once '../slim/Router.php';
require_once 'PHPUnit/Framework.php';
 
/**
 * Router Mock
 *
 * This is a mock for the Router class so that it, 
 * A) provides the necessary features for this test and
 * B) removes dependencies on the Request class.
 */
class RouterMock extends Router {
	
	public $cache = array();
	
	public function __construct() {}
	
	public function cacheNamedRoute($name, Route $route) {
		$this->cache[$name] = $route;
	}
	
}

class RouteTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * Route should set name and be cached by Router
	 */
	public function testRouteSetsNameAndIsCached() {
		$router = new RouterMock();
		$route = new Route('/foo/bar', function () {});
		$route->setRouter($router);
		$route->name('foo');
		$cacheKeys = array_keys($router->cache);
		$cacheValues = array_values($router->cache);
		$this->assertEquals($cacheKeys[0], 'foo');
		$this->assertSame($cacheValues[0], $route);
	}
	
	/**
	 * Route should set pattern, and the Route pattern should not
	 * retain the leading slash.
	 */
	public function testRouteSetsPatternWithoutLeadingSlash() {
		$route = new Route('/foo/bar', function () {});
		$this->assertEquals('foo/bar', $route->pattern());
	}
	
	/**
	 * Route should store a reference to the callable
	 * anonymous function.
	 */
	public function testRouteSetsCallableAsFunction() {
		$callable = function () { echo "Foo!"; };
		$route = new Route('/foo/bar', $callable);
		$this->assertSame($callable, $route->callable());
	}
	
	/**
	 * Route should store a reference to the callable
	 * regular function (for PHP 5 < 5.3)
	 */
	public function testRouteSetsCallableAsString() {
		$route = new Route('/foo/bar', 'testCallable');
		$this->assertEquals('testCallable', $route->callable());
	}
	
	/**
	 * If route matches a resource URI, param should be extracted.
	 */
	public function testRouteMatchesAndParamExtracted() {
		$resource = 'hello/Josh';
		$route = new Route('/hello/:name', function () {});
		$result = $route->matches($resource);
		$this->assertTrue($result);
		$this->assertEquals($route->params(), array('name' => 'Josh'));
	}
	
	/**
	 * If route matches a resource URI, multiple params should be extracted.
	 */
	public function testRouteMatchesAndMultipleParamsExtracted() {
		$resource = 'hello/Josh/and/John';
		$route = new Route('/hello/:first/and/:second', function () {});
		$result = $route->matches($resource);
		$this->assertTrue($result);
		$this->assertEquals($route->params(), array('first' => 'Josh', 'second' => 'John'));
	}
	
	/**
	 * If route does not match a resource URI, params remain an empty array
	 */
	public function testRouteDoesNotMatchAndParamsNotExtracted() {
		$resource = 'foo/bar';
		$route = new Route('/hello/:name', function () {});
		$result = $route->matches($resource);
		$this->assertFalse($result);
		$this->assertEquals($route->params(), array());
	}
	
	/**
	 * Route matches URI with conditions
	 */
	public function testRouteMatchesResourceWithConditions() {
		$resource = 'hello/Josh/and/John';
		$route = new Route('/hello/:first/and/:second', function () {});
		$route->conditions(array('first' => '[a-zA-Z]{3,}'));
		$result = $route->matches($resource);
		$this->assertTrue($result);
		$this->assertEquals($route->params(), array('first' => 'Josh', 'second' => 'John'));
	}
	
	/**
	 * Route does not match URI with conditions
	 */
	public function testRouteDoesNotMatchResourceWithConditions() {
		$resource = 'hello/Josh/and/John';
		$route = new Route('/hello/:first/and/:second', function () {});
		$route->conditions(array('first' => '[a-z]{3,}'));
		$result = $route->matches($resource);
		$this->assertFalse($result);
		$this->assertEquals($route->params(), array());
	}
	
}

?>