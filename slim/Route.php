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
 * @author Josh Lockhart <info@joshlockhart.com>
 * @since Version 1.0
 */
class Route {
	
	/**
	 * @var string The route pattern (ie. "/books/:id")
	 */
	private $pattern;
	
	/**
	 * @var mixed The callable associated with this route
	 */
	private $callable;
	
	/**
	 * @var array Conditions for this route's URL parameters (not implemented yet)
	 */
	private $conditions;
	
	/**
	 * @var string The name of this route (optional)
	 */
	private $name;
	
	/**
	 * @var array Array of URL parameter names and values
	 */
	private $params;
	
	/**
	 * Constructor
	 *
	 * This method was modeled after the techniques demonstrated by
	 * Dan Sosedoff at:
	 *
	 * http://blog.sosedoff.com/2009/09/20/rails-like-php-url-router/
	 *
	 * @param string $pattern The URL pattern (ie. "/books/:id")
	 * @param string $method The HTTP request method required for this route
	 * @param Request $request The current Request object
	 * @param mixed $callable Anything that returns TRUE for is_callable()
	 * @param array $conditions Conditions for the route pattern parameters (not implemented yet)
	 */
	public function __construct( $pattern, $callable ) {
		
		$this->pattern = ltrim($pattern, '/');
		$this->callable = $callable;
		$this->conditions = array();
		$this->params = array();
		
	}
	
	/**
	 * Matches URI?
	 *
	 * Parse this route's pattern, and then compare it to an HTTP resource URI
	 *
	 * @param string $resourceUri A Request URI
	 * @return bool
	 */
	public function matches( $resourceUri ) {
		
		//Extract URL params		
		preg_match_all('@:([\w]+)@', $this->pattern, $paramNames, PREG_PATTERN_ORDER);
		$paramNames = $paramNames[0];
		
		//Convert URL params into regex patterns, construct a regex for this route
		$patternAsRegex = preg_replace_callback('@:[\w]+@', array($this, 'convertPatternToRegex'), $this->pattern);
		$patternAsRegex = '@^' . $patternAsRegex . '$@';
				
		//Cache URL params' names and values if this route matches the current HTTP request
		if( preg_match($patternAsRegex, $resourceUri, $paramValues) ) {
			array_shift($paramValues);
			foreach( $paramNames as $index => $value ) {
				$this->params[substr($value, 1)] = urldecode($paramValues[$index]);
			}
			return true;
		} else {
			return false;
		}
		
	}
	
	/**
	 * Convert a URL parameter (ie. ":id") into a regular expression
	 *
	 * @param array Array of URL params
	 * @return string Regex for specific URL param's name
	 */
	private function convertPatternToRegex($matches) {
		$key = str_replace(':', '', $matches[0]);
		if( array_key_exists($key, $this->conditions) ) {
			return '(' . $this->conditions[$key] . ')';
		} else {
			return '([a-zA-Z0-9_\+\-%]+)';
		}
	}
	
	/**
	 * Return the callable for this Route
	 *
	 * @return mixed
	 */
	public function callable() {
		return $this->callable;
	}
	
	/**
	 * Return the parameter names and values for this Route
	 *
	 * @return array
	 */
	public function params() {
		return $this->params;
	}
	
	/**
	 * Set and/or get this route's name
	 *
	 * @param string $name The name of the route
	 * @return The name of the route as a string, or NULL if name not set
	 */
	public function name( $name = null ) {
		if ( !is_null($name) ) {
			$this->name = (string)$name;
		}
		return $this->name;
	}
	
	/**
	 * Set and/or get this route's conditions
	 *
	 * @param array $conditions An associative array of URL parameter conditions
	 * @return The array of conditions for the route
	 */
	public function conditions( $conditions = null ) {
		if ( is_array($conditions) ) {
			$this->conditions = $conditions;
		}
		return $this->conditions;
	}
	 
}

?>