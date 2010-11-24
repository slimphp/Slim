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

/**
 * Slim Router
 *
 * The Router is responsible for registering route paths with associated
 * callback functions. When a Slim application is run, the Router is then
 * responsible for matching a registered route with the current HTTP request,
 * and if a matching route is found, executing the associated callback function
 * using any URL parameters in the request URI.
 *
 * @package	Slim
 * @author	Josh Lockhart <info@joshlockhart.com>
 * @since	Version 1.0
 */
class Router implements Iterator {

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var array Lookup hash of routes, keyed by Request method
	 */
	private $routes;

	/**
	 * @var array Lookup hash of named routes, keyed by route name
	 */
	private $namedRoutes;

	/**
	 * @var array Array of routes matching the Request method and URL
	 */
	private $matchedRoutes;
	
	/**
	 * @var mixed 404 Not Found callback function if a matching route is not found
	 */
	private $notFound;

	/**
	 * @var mixed Error callback function if there is an application error
	 */
	private $error;

	/**
	 * @var int Iterator position
	 */
	private $position;
	
	/**
	 * Constructor
	 *
	 * @param Request $request The current HTTP request object
	 */
	public function __construct( Request $request ) {
		$this->request = $request;
		$this->routes = array(
			'GET' => array(),
			'POST' => array(),
			'PUT' => array(),
			'DELETE' => array()
		);
		$this->position = 0;
	}
	
	/***** ACCESSORS *****/
	
	/**
	 * Get Request
	 *
	 * @return Request
	 */
	public function getRequest() {
		return $this->request;
	}
	
	/**
	 * Set Request
	 *
	 * @param Request
	 * @return void
	 */
	public function setRequest( Request $req ) {
		$this->request = $req;
	}
	
	/***** MAPPING *****/

	/**
	 * Map a route to a callback function
	 *
	 * @param	string	$pattern	The URL pattern (ie. "/books/:id")
	 * @param	mixed	$callable	Anything that returns TRUE for is_callable()
	 * @param	string	$method		The HTTP request method (GET, POST, PUT, DELETE)
	 * @return 	Route
	 */
	public function map( $pattern, $callable, $method ) {
		$route = new Route($pattern, $callable);
		$route->setRouter($this);
		$this->routes[$method][] = $route;
		if ( $method === $this->request->method && $route->matches($this->request->resource) ) {
			$this->matchedRoutes[] = $route;
		}
		return $route;
	}

	/**
	 * Cache named route
	 *
	 * @param	string				$name	The route name
	 * @param	Route				$route	The route object
	 * @throws	RuntimeException			If a named route already exists with the same name
	 * @return 	void
	 */
	public function cacheNamedRoute( $name, Route $route ) {
		if ( isset($this->namedRoutes[(string)$name]) ) {
			throw new RuntimeException('Named route already exists with name: ' . $name);
		}
		$this->namedRoutes[$name] = $route;
	}

	/**
	 * Get URL for named route
	 *
	 * @param	string				$name	The name of the route
	 * @param	array 						Associative array of URL parameter names and values
	 * @throws	RuntimeException			If named route not found
	 * @return 	string						The URL for the given route populated with the given parameters
	 */
	public function urlFor( $name, $params = array() ) {
		if ( !isset($this->namedRoutes[(string)$name]) ) {
			throw new RuntimeException('Named route not found for name: ' . $name);
		}
		$pattern = $this->namedRoutes[(string)$name]->pattern();
		foreach ( $params as $key => $value ) {
			$pattern = str_replace(':' . $key, $value, $pattern);
		}
		return $this->request->root . $pattern;
	}

	/**
	 * Register a 404 Not Found callback
	 *
	 * @param	mixed $callable Anything that returns TRUE for is_callable()
	 * @return 	mixed
	 */
	public function notFound( $callable = null ) {
		if ( is_callable($callable) ) {
			$this->notFound = $callable;
		}
		return $this->notFound;
	}

	/**
	 * Register a 500 Error callback
	 *
	 * @param	mixed $callable Anything that returns TRUE for is_callable()
	 * @return 	mixed
	 */
	public function error( $callable = null ) {
		if ( is_callable($callable) ) {
			$this->error = $callable;
		}
		return $this->error;
	}

	/***** ITERATOR INTERFACE *****/

	/**
	 * Return the current route being dispatched
	 *
	 * @return Route
	 */
	public function current() {
		return $this->matchedRoutes[$this->position];
	}

	/**
	 * Reset the current route to the first matching route
	 *
	 * @return void
	 */
	public function rewind() {
		$this->position = 0;
	}

	/**
	 * Return the 0-indexed position of the current route 
	 * being dispatched among all matching routes
	 *
	 * @return int
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * Return the 0-indexed position of the next route to 
	 * be dispatched among all matching routes
	 *
	 * @return int
	 */
	public function next() {
		$this->position = $this->position + 1;
	}

	/**
	 * Does a matching route exist at a given 0-indexed position?
	 *
	 * @return bool
	 */
	public function valid() {
		return isset($this->matchedRoutes[$this->position]);
	}

}
?>