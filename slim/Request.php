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
 * Slim Request
 *
 * This class is responsible for parsing the raw HTTP request into
 * a usable form for the Slim application. This class also interprets
 * the desired resource URI (taking into account sub-directories).
 *
 * @author Josh Lockhart <info@joshlockhart.com>
 * @since Version 1.0
 */
class Request {

	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_DELETE = 'DELETE';
	const METHOD_OVERRIDE = '_METHOD';

	/**
	 * @var string The request method
	 */
	public $method;

	/**
	 * @var string The resource string (excluding path to the application root directory)
	 */
	public $resource;

	/**
	 * @var string The path to the application's root directory
	 */
	public $root;

	/**
	 * @var array Array of HTTP request headers
	 */
	private $headers;

	/**
	 * @var array Array of HTTP request cookies
	 */
	private $cookies;

	/**
	 * @var bool Is this an AJAX request?
	 */
	public $isAjax;

	/**
	 * @var array Array of GET paramters' names and values
	 */
	private $get;

	/**
	 * @var array Array of POST paramters' names and values
	 */
	private $post;

	/**
	 * @var array Array of PUT paramters' names and values
	 */
	private $put;

	/**
	 * @var string Raw request input (used with POST and PUT requests)
	 */
	private $input;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->resource = $this->extractQueryString();
		$this->get = $_GET;
		$this->post = $_POST;
		if(	$this->method == Request::METHOD_PUT ) {
			$this->input = file_get_contents('php://input');
			$this->put = $this->getPutParameters();
		}
		$this->headers = $this->getHttpHeaders();
		$this->cookies = $_COOKIE;
		$this->isAjax = isset($request->headers['X_REQUESTED_WITH']) && $request->headers['X_REQUESTED_WITH'] == 'XMLHttpRequest';
		$this->checkForHttpMethodOverride();
	}

	/***** PARAM ACCESSORS *****/

	/**
	 * Return PUT|POST|GET parameter
	 *
	 * The suggested method for accessing request parameter values.
	 * 
	 * @param string $key The paramter name
	 * @return mixed The parameter value, or NULL if parameter not found
	 */
	public function params( $key ) {
		if( isset($this->put[$key]) ) {
			return $this->put[$key];
		}
		if( isset($this->post[$key]) ) {
			return $this->post[$key];
		}
		if( isset($this->get[$key]) ) {
			return $this->get[$key];
		}
		return null;
	}

	/**
	 * Fetch GET parameter(s)
	 *
	 * @param string $key Name of parameter
	 * @return array|string Array of all parameters, or parameter value if $key provided.
	 */
	public function get( $key = null ) {
		if( is_null($key) ) {
			return $this->get;
		}
		return ( isset($this->get[$key]) ) ? $this->get[$key] : null;
	}

	/**
	 * Fetch POST parameter(s)
	 *
	 * @param string $key Name of parameter
	 * @return array|string Array of all parameters, or parameter value if $key provided.
	 */
	public function post( $key = null ) {
		if( is_null($key) ) {
			return $this->post;
		}
		return ( isset($this->post[$key]) ) ? $this->post[$key] : null;
	}

	/**
	 * Fetch PUT parameter(s)
	 *
	 * @param string $key Name of parameter
	 * @return array|string Array of all parameters, or parameter value if $key provided.
	 */
	public function put( $key = null ) {
		if( is_null($key) ) {
			return $this->put;
		}
		return ( isset($this->put[$key]) ) ? $this->put[$key] : null;
	}

	/**
	 * Fetch COOKIE value
	 *
	 * @param string $name The cookie name
	 * @return The cookie value, or NULL if cookie not set
	 */
	public function cookie( $name ) {
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
	}

	/***** HELPERS *****/

	/**
	 * Extract Resource URL
	 *
	 * This method converts the raw HTTP request URL into the desired
	 * resource string, excluding the path to the root Slim app directory
	 * and any query string.
	 *
	 * @return string The resource string
	 */
	private function extractQueryString() {
		$this->root = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/';
		$uri = ltrim( preg_replace('@'.preg_quote($this->root,'@').'@', '', $_SERVER['REQUEST_URI'], 1), '/');
		$questionMarkPosition = strpos($uri, '?');
		if( !!$questionMarkPosition ) {
			return substr($uri, 0, $questionMarkPosition);
		}
		return $uri;
	}

	/**
	 * Fetch and parse raw POST or PUT paramters
	 *
	 * @return string
	 */
	private function getPutParameters() {
		$putdata = $this->input();
		if( function_exists('mb_parse_str') ) {
			mb_parse_str($putdata, $outputdata);
		} else {
			parse_str($putdata, $outputdata);
		}
		return $outputdata;
	}

	/**
	 * Fetch HTTP request headers
	 *
	 * @return array
	 */
	private function getHttpHeaders() {
		$httpHeaders = array();
		foreach( array_keys($_SERVER) as $key ) {
			if( substr($key, 0, 5) == 'HTTP_' ) {
				$httpHeaders[substr($key, 5)] = $_SERVER[$key];
			}
		}
		return $httpHeaders;
	}

	/**
	 * Fetch HTTP header
	 *
	 * @param string $name The header name
	 * @return The header string value, or NULL if header does not exist
	 */
	public function header( $name ) {
		return isset($this->headers[$name]) ? $this->headers[$name] : null;
	}

	/**
	 * Check for HTTP request method override
	 *
	 * Because traditional web browsers do not support PUT and DELETE
	 * HTTP methods, we must use a hidden form input field to
	 * mimic PUT and DELETE requests. We check for this override here.
	 */
	private function checkForHttpMethodOverride() {
		if( array_key_exists(Request::METHOD_OVERRIDE, $this->post) ) {
			$this->method = $this->post[Request::METHOD_OVERRIDE];
			unset($this->post[Request::METHOD_OVERRIDE]);
			if( $this->method == Request::METHOD_PUT ) {
				$this->put = $this->post;
			}
		}
	}

}

?>
