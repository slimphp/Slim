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
 * @author Josh Lockhart <info@joshlockhart.com>
 * @since Version 1.0
 */
class Router {
	
	/**
	 * @var Request
	 */
	private $request;
	
	/**
	 * @var array Routes that are registered with the Router
	 */
	private $routes;
	
	/**
	 * @var Route The Route that matches the current HTTP request, or NULL
	 */
	private $matchedRoute;
	
	/**
	 * @var mixed 404 Not Found callback function if a matching route is not found
	 */
	private $notFound;
	
	/**
	 * @var array Paramter names and values extracted from the resource URI for the matching route
	 */
	private $params;
	
	/**
	 * Constructor
	 *
	 * @param Request $request The current HTTP request object
	 */
	public function __construct( Request $request ) {
		$this->request = $request;
		$this->routes = array();
		$this->params = array();
	}
	
	/**
	 * Map a route to a callback function
	 *
	 * @param string $pattern The URL pattern (ie. "/books/:id")
	 * @param mixed $callable Anything that returns TRUE for is_callable()
	 * @param string $method The HTTP request method (GET, POST, PUT, DELETE)
	 */
	public function map( $pattern, $callable, $method ) {
		$route = new Route( $pattern, $method, $this->request, $callable );
		if( $route->matched() ) {
			$this->matchedRoute = $route;
			$this->params = $route->params();
		}
		$this->routes[$method][] = $route;
	}
	
	/**
	 * Register a 404 Not Found callback
	 *
	 * @param mixed $callable Anything that returns TRUE for is_callable()
	 * @return mixed
	 */
	public function notFound( $callable = null ) {
		if( is_callable($callable) ) {
			$this->notFound = $callable;
		}
		return $this->notFound;
	}
	
	/**
	 * Dispatch request
	 *
	 * @return true|false TRUE if matching route is found and callable, else FALSE
	 */
	public function dispatch() {
		if( !is_null($this->matchedRoute) ) {
			$callable = $this->matchedRoute->callable();
			if( is_callable($callable) ) {
				call_user_func_array($callable, array_values($this->params));
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
}

?>