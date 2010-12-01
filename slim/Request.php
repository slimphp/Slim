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
 * the HTTP request resource URI (taking into account sub-directories),
 * GET params, POST params, PUT params, cookies, and headers.
 *
 * @package Slim
 * @author	Josh Lockhart <info@joshlockhart.com>
 * @since	Version 1.0
 */
class Request {

	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_DELETE = 'DELETE';
	const METHOD_OVERRIDE = '_METHOD';

	/**
	 * @var string Request method
	 */
	public $method;

	/**
	 * @var string Resource URI (excluding the application directory path)
	 */
	public $resource;

	/**
	 * @var string Path to the application directory
	 */
	public $root;

	/**
	 * @var array HTTP request headers
	 */
	private $headers;

	/**
	 * @var array HTTP request cookies
	 */
	private $cookies;

	/**
	 * @var bool Is this an AJAX request?
	 */
	public $isAjax;

	/**
	 * @var array GET paramters' names and values
	 */
	private $get;

	/**
	 * @var array POST paramters' names and values
	 */
	private $post;

	/**
	 * @var array PUT paramters' names and values
	 */
	private $put;

	/**
	 * @var string Raw request input (for POST and PUT requests)
	 */
	private $input;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->resource = $this->extractQueryString();
		$this->get = get_magic_quotes_gpc() ? self::stripSlashesFromRequestData($_GET) : $_GET;
		$this->post = get_magic_quotes_gpc() ? self::stripSlashesFromRequestData($_POST) : $_POST;
		if ( $this->method === Request::METHOD_PUT ) {
			$this->input = file_get_contents('php://input');
			$this->put = get_magic_quotes_gpc() ? self::stripSlashesFromRequestData($this->getPutParameters()) : $this->getPutParameters();
		}
		$this->headers = $this->getHttpHeaders();
		$this->cookies = get_magic_quotes_gpc() ? self::stripSlashesFromRequestData($_COOKIE) : $_COOKIE;
		$this->isAjax = isset($this->headers['X_REQUESTED_WITH']) && $this->headers['X_REQUESTED_WITH'] === 'XMLHttpRequest';
		$this->checkForHttpMethodOverride();
	}

	/***** PARAM ACCESSORS *****/

	/**
	 * Fetch PUT|POST|GET parameter
	 *
	 * This is the preferred method to fetch the value of a
	 * PUT, POST, or GET parameter (searched in that order).
	 * 
	 * @param	string		$key The paramter name
	 * @return 	string|null
	 */
	public function params( $key ) {
		if ( isset($this->put[$key]) ) {
			return $this->put[$key];
		}
		if ( isset($this->post[$key]) ) {
			return $this->post[$key];
		}
		if ( isset($this->get[$key]) ) {
			return $this->get[$key];
		}
		return null;
	}

	/**
	 * Fetch GET parameter(s)
	 *
	 * @param	string				$key	Name of parameter
	 * @return 	array|string|null			All parameters, or parameter value if $key provided.
	 */
	public function get( $key = null ) {
		if ( is_null($key) ) {
			return $this->get;
		}
		return ( isset($this->get[$key]) ) ? $this->get[$key] : null;
	}

	/**
	 * Fetch POST parameter(s)
	 *
	 * @param	string				$key	Name of parameter
	 * @return 	array|string|null			All parameters, or parameter value if $key provided.
	 */
	public function post( $key = null ) {
		if ( is_null($key) ) {
			return $this->post;
		}
		return ( isset($this->post[$key]) ) ? $this->post[$key] : null;
	}

	/**
	 * Fetch PUT parameter(s)
	 *
	 * @param	string				$key	Name of parameter
	 * @return 	array|string|null			All parameters, or parameter value if $key provided.
	 */
	public function put( $key = null ) {
		if ( is_null($key) ) {
			return $this->put;
		}
		return ( isset($this->put[$key]) ) ? $this->put[$key] : null;
	}

	/**
	 * Fetch COOKIE value
	 *
	 * @param	string		$name 	The cookie name
	 * @return 	string|null			The cookie value, or NULL if cookie not set
	 */
	public function cookie( $name ) {
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
	}

	/***** HELPERS *****/

	/**
	 * Strip slashes from Request data
	 *
	 * You can pass an array or a string into this method, and the filtered
	 * array or string will be returned. This method will strip slashes from 
	 * the data. This should only be used if `get_magic_quotes_gpc` is enabled.
	 *
	 * @param 	array|string $rawData
	 * @return 	array|string
	 */
	public static function stripSlashesFromRequestData( $rawData ) {
		return is_array($rawData) ? array_map(array('Request', 'stripSlashesFromRequestData'), $rawData) : stripslashes($rawData);
	}
	
	/**
	 * Extract Resource URL
	 *
	 * This method converts the raw HTTP request URL into the desired
	 * resource string, excluding the path to the root Slim app directory
	 * and any query string.
	 *
	 * @author	Kris Jordan <http://www.github.com/KrisJordan>
	 * @return 	string The resource URI
	 */
	private function extractQueryString() {
		$this->root = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/';
		$uri = ltrim(preg_replace('@' . preg_quote($this->root, '@') . '@', '', $_SERVER['REQUEST_URI'], 1), '/');
		$questionMarkPosition = strpos($uri, '?');
		if ( !!$questionMarkPosition ) {
			return substr($uri, 0, $questionMarkPosition);
		}
		return $uri;
	}

	/**
	 * Fetch and parse raw POST or PUT paramters
	 *
	 * @author	Kris Jordan <http://www.github.com/KrisJordan>
	 * @return string
	 */
	private function getPutParameters() {
		$putdata = $this->input();
		if ( function_exists('mb_parse_str') ) {
			mb_parse_str($putdata, $outputdata);
		} else {
			parse_str($putdata, $outputdata);
		}
		return $outputdata;
	}

	/**
	 * Fetch HTTP request headers
	 *
	 * @author	Kris Jordan <http://www.github.com/KrisJordan>
	 * @author  Jud Stephenson <http://judstephenson.com/blog>
	 * @return array
	 */
	private function getHttpHeaders() {
		$httpHeaders = array();
		foreach ( array_keys($_SERVER) as $key ) {
			if ( (substr($key, 0, 5) === 'HTTP_') || (substr($key, 0, 8) === 'PHP_AUTH') ) {
				$httpHeaders[((substr($key, 0, 5) == 'HTTP_') ? substr($key, 5) : substr($key, 4))] = $_SERVER[$key];
			}
		}
		return $httpHeaders;
	}

	/**
	 * Fetch HTTP header
	 *
	 * @param	string		$name	The header name
	 * @return 	string|null			The header value, or NULL if header does not exist
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
	 *
	 * @author	Kris Jordan <http://www.github.com/KrisJordan>
	 * @return void
	 */
	private function checkForHttpMethodOverride() {
		if ( array_key_exists(Request::METHOD_OVERRIDE, $this->post) ) {
			$this->method = $this->post[Request::METHOD_OVERRIDE];
			unset($this->post[Request::METHOD_OVERRIDE]);
			if ( $this->method === Request::METHOD_PUT ) {
				$this->put = $this->post;
			}
		}
	}

}

?>