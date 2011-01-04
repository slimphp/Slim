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
 * Slim Route
 *
 * @package	Slim
 * @author	Josh Lockhart <info@joshlockhart.com>
 * @since	Version 1.0
 */
class Route {

	/**
	 * @var string The route pattern (ie. "/books/:id")
	 */
	protected $pattern;

	/**
	 * @var mixed The callable associated with this route
	 */
	protected $callable;

	/**
	 * @var array Conditions for this route's URL parameters
	 */
	protected $conditions = array();
	
	/**
	 * @var array Default conditions applied to all Route instances
	 */
	protected static $defaultConditions = array();

	/**
	 * @var string The name of this route
	 */
	protected $name;

	/**
	 * @var array Array of URL parameter names and values
	 */
	protected $params = array();

	/**
	 * @var Router The Router that contains this Route
	 */
	protected $router;

	/**
	 * Constructor
	 *
	 * @param string	$pattern	The URL pattern (ie. "/books/:id")
	 * @param mixed		$callable	Anything that returns TRUE for is_callable()
	 */
	public function __construct( $pattern, $callable ) {
		$this->setPattern($pattern);
		$this->setCallable($callable);
		$this->setConditions(self::getDefaultConditions());
	}

	/***** CLASS METHODS *****/
	
	/**
	 * Set default route conditions for all instances
	 *
	 * @param array $defaultConditions
	 * @return void
	 */
	public static function setDefaultConditions( array $defaultConditions ) {
		self::$defaultConditions = $defaultConditions;
	}
	
	/**
	 * Get default route conditions for all instances
	 *
	 * @return array
	 */
	public static function getDefaultConditions() {
		return self::$defaultConditions;
	}
	
	/***** INSTANCE ACCESSORS *****/
	
	/**
	 * Get route pattern
	 *
	 * @return string
	 */
	public function getPattern() {
		return $this->pattern;
	}
	
	/**
	 * Set route pattern
	 *
	 * @param string $pattern
	 * @return void
	 */
	public function setPattern( $pattern ) {
		$this->pattern = str_replace(')', ')?', (string)$pattern);
	}
	
	/**
	 * Get route callable
	 *
	 * @return mixed
	 */
	public function getCallable() {
		return $this->callable;
	}
	
	/**
	 * Set route callable
	 *
	 * @param mixed $callable
	 * @return void
	 */
	public function setCallable($callable) {
		$this->callable = $callable;
	}
	
	/**
	 * Get route conditions
	 *
	 * @return array
	 */
	public function getConditions() {
		return $this->conditions;
	}
	
	/**
	 * Set route conditions
	 *
	 * @param array $conditions
	 * @return void
	 */
	public function setConditions( array $conditions ) {
		$this->conditions = $conditions;
	}
	
	/**
	 * Get route name
	 *
	 * @return string|null
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Set route name
	 *
	 * @param string $name
	 * @return void
	 */
	public function setName( $name ) {
		$this->name = (string)$name;
		$this->getRouter()->cacheNamedRoute($name, $this);
	}
	
	/**
	 * Get route parameters
	 *
	 * @return array
	 */
	public function getParams() {
		return $this->params;
	}
	
	/**
	 * Get router
	 *
	 * @return Router
	 */
	public function getRouter() {
		return $this->router;
	}
	
	/**
	 * Set router
	 *
	 * @param Router $router
	 * @return void
	 */
	public function setRouter( Router $router ) {
		$this->router = $router;
	}
	
	/***** ROUTE PARSING AND MATCHING *****/
	
	/**
	 * Matches URI?
	 *
	 * Parse this route's pattern, and then compare it to an HTTP resource URI
	 * This method was modeled after the techniques demonstrated by Dan Sosedoff at:
	 *
	 * http://blog.sosedoff.com/2009/09/20/rails-like-php-url-router/
	 *
	 * This method is also smart about trailing slashes. If a route is defined
	 * with a trailing slash, and if the current request URI does not have
	 * a trailing slash but otherwise matches the route, a SlimRequestSlashException
	 * will be thrown triggering a 301 Redirect to the same URI with a trailing slash.
	 * This exception is caught in the `Slim::run` loop. If a route is
	 * defined without a trailing slash, and the current request URI does
	 * have a trailing slash, the route will not be matched and a 404 Not Found
	 * response will be sent if no subsequent matching routes are found.
	 *
	 * @param	string						$resourceUri A Request URI
	 * @throws	SlimRequestSlashException
	 * @return	bool
	 */
	public function matches( $resourceUri ) {

		//Extract URL params
		preg_match_all('@:([\w]+)@', $this->getPattern(), $paramNames, PREG_PATTERN_ORDER);
		$paramNames = $paramNames[0];

		//Convert URL params into regex patterns, construct a regex for this route
		$patternAsRegex = preg_replace_callback('@:[\w]+@', array($this, 'convertPatternToRegex'), $this->getPattern());
		if ( substr($this->getPattern(), -1) === '/' ) {
			$patternAsRegex = $patternAsRegex . '?';
		}
		$patternAsRegex = '@^' . $patternAsRegex . '$@';

		//Cache URL params' names and values if this route matches the current HTTP request
		if ( preg_match($patternAsRegex, $resourceUri, $paramValues) ) {
			array_shift($paramValues);
			foreach ( $paramNames as $index => $value ) {
				$val = substr($value, 1);
				if ( isset($paramValues[$val]) ) {
					$this->params[$val] = urldecode($paramValues[$val]);
				}
			}
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Convert a URL parameter (ie. ":id") into a regular expression
	 *
	 * @param	array 	URL parameters
	 * @return 	string	Regular expression for URL parameter
	 */
	private function convertPatternToRegex( $matches ) {
		$key = str_replace(':', '', $matches[0]);
		if ( array_key_exists($key, $this->conditions) ) {
			return '(?P<' . $key . '>' . $this->conditions[$key] . ')';
		} else {
			return '(?P<' . $key . '>[a-zA-Z0-9_\-\.\!\~\*\\\'\(\)\:\@\&\=\$\+,%]+)';
		}
	}

	/***** HELPERS *****/
	
	/**
	 * Set route name (alias for Route::setName)
	 *
	 * @param	string $name The name of the route
	 * @return 	Route
	 */
	public function name( $name ) {
		$this->setName($name);
		return $this;
	}

	/**
	 * Merge route conditions
	 *
	 * @param	array $conditions An associative array of URL parameter conditions
	 * @return 	Route
	 */
	public function conditions( array $conditions ) {
		$this->conditions = array_merge($this->conditions, $conditions);
		return $this;
	}
	
	/***** DISPATCHING *****/
	
	/**
	 * Dispatch route
	 *
	 * @return bool
	 */
	public function dispatch() {
		if ( substr($this->getPattern(), -1) === '/' && substr($this->getRouter()->getRequest()->resource, -1) !== '/' ) {
			throw new SlimRequestSlashException();
		}
		if ( is_callable($this->getCallable()) ) {
			call_user_func_array($this->getCallable(), array_values($this->getParams()));
			return true;
		}
		return false;
	}

}
?>