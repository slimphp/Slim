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
		$this->assertEquals('/foo/bar', $route->pattern());
	}

	/**
	 * Route should store a reference to the callable
	 * anonymous function.
	 */
	public function testRouteSetsCallableAsFunction() {
		$callable = function () { echo "Foo!"; };
		$route = new Route('/foo/bar', $callable);
		$this->assertSame($callable, $route->getCallable());
	}

	/**
	 * Route should store a reference to the callable
	 * regular function (for PHP 5 < 5.3)
	 */
	public function testRouteSetsCallableAsString() {
		$route = new Route('/foo/bar', 'testCallable');
		$this->assertEquals('testCallable', $route->getCallable());
	}

	/**
	 * If route matches a resource URI, param should be extracted.
	 */
	public function testRouteMatchesAndParamExtracted() {
		$resource = '/hello/Josh';
		$route = new Route('/hello/:name', function () {});
		$result = $route->matches($resource);
		$this->assertTrue($result);
		$this->assertEquals($route->getParams(), array('name' => 'Josh'));
	}

	/**
	 * If route matches a resource URI, multiple params should be extracted.
	 */
	public function testRouteMatchesAndMultipleParamsExtracted() {
		$resource = '/hello/Josh/and/John';
		$route = new Route('/hello/:first/and/:second', function () {});
		$result = $route->matches($resource);
		$this->assertTrue($result);
		$this->assertEquals($route->getParams(), array('first' => 'Josh', 'second' => 'John'));
	}

	/**
	 * If route does not match a resource URI, params remain an empty array
	 */
	public function testRouteDoesNotMatchAndParamsNotExtracted() {
		$resource = '/foo/bar';
		$route = new Route('/hello/:name', function () {});
		$result = $route->matches($resource);
		$this->assertFalse($result);
		$this->assertEquals($route->getParams(), array());
	}

	/**
	 * Route matches URI with conditions
	 */
	public function testRouteMatchesResourceWithConditions() {
		$resource = '/hello/Josh/and/John';
		$route = new Route('/hello/:first/and/:second', function () {});
		$route->conditions(array('first' => '[a-zA-Z]{3,}'));
		$result = $route->matches($resource);
		$this->assertTrue($result);
		$this->assertEquals($route->getParams(), array('first' => 'Josh', 'second' => 'John'));
	}

	/**
	 * Route does not match URI with conditions
	 */
	public function testRouteDoesNotMatchResourceWithConditions() {
		$resource = '/hello/Josh/and/John';
		$route = new Route('/hello/:first/and/:second', function () {});
		$route->conditions(array('first' => '[a-z]{3,}'));
		$result = $route->matches($resource);
		$this->assertFalse($result);
		$this->assertEquals($route->getParams(), array());
	}

	/*
	 * Route should match URI with valid path component according to rfc2396
	 *
	 * "Uniform Resource Identifiers (URI): Generic Syntax" http://www.ietf.org/rfc/rfc2396.txt
	 *
	 * Excludes "+" which is valid but decodes into a space character
	 */
	public function testRouteMatchesResourceWithValidRfc2396PathComponent() {
		$symbols = ":@&=$,";
		$resource = '/rfc2386/'.$symbols;
		$route = new Route('/rfc2386/:symbols', function () {});
		$result = $route->matches($resource);
		$this->assertTrue($result);
		$this->assertEquals($route->getParams(), array('symbols' => $symbols));
	}

	/*
	 * Route should match URI including unreserved punctuation marks from rfc2396
	 *
	 * "Uniform Resource Identifiers (URI): Generic Syntax" http://www.ietf.org/rfc/rfc2396.txt
	 */
	public function testRouteMatchesResourceWithUnreservedMarks() {
		$marks = "-_.!~*'()";
		$resource = '/marks/'.$marks;
		$route = new Route('/marks/:marks', function () {});
		$result = $route->matches($resource);
		$this->assertTrue($result);
		$this->assertEquals($route->getParams(), array('marks' => $marks));
	}
	
	/**
	 * Route optional parameters
	 *
	 * Pre-conditions:
	 * Route pattern requires :year, optionally accepts :month and :day
	 *
	 * Post-conditions:
	 * All: Year is 2010
	 * Case A: Month and day default values are used
	 * Case B: Month is "05" and day default value is used
	 * Case C: Month is "05" and day is "13"
	 */
	public function testRouteOptionalParameters() {
		$pattern = 'archive/:year(/:month(/:day))';
		
		//Case A
		$routeA = new Route($pattern, function () {});
		$resourceA = 'archive/2010';
		$resultA = $routeA->matches($resourceA);
		$this->assertTrue($resultA);
		$this->assertEquals($routeA->getParams(), array('year' => '2010'));
		
		//Case B
		$routeB = new Route($pattern, function () {});
		$resourceB = 'archive/2010/05';
		$resultB = $routeB->matches($resourceB);
		$this->assertTrue($resultB);
		$this->assertEquals($routeB->getParams(), array('year' => '2010', 'month' => '05'));
		
		//Case C
		$routeC = new Route($pattern, function () {});
		$resourceC = 'archive/2010/05/13';
		$resultC = $routeC->matches($resourceC);
		$this->assertTrue($resultC);
		$this->assertEquals($routeC->getParams(), array('year' => '2010', 'month' => '05', 'day' => '13'));
	}

}

?>