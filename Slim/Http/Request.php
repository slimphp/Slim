<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart
 * @link        http://www.slimframework.com
 * @copyright   2011 Josh Lockhart
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
 * Request
 *
 * Object-oriented representation of an HTTP request. This class
 * is responsible for parsing the raw HTTP request into a format
 * usable by the Slim application; parsed data includes:
 *
 * - Resource URI (ie. "/person/1")
 * - GET parameters
 * - POST parameters
 * - PUT parameters
 * - Cookies
 * - Headers
 *
 * This class will automatically remove slashes from GET, POST, PUT,
 * and Cookie data if magic quotes are enabled.
 *
 * @package Slim
 * @author  Josh Lockhart <info@joshlockhart.com>
 * @author  Kris Jordan <http://www.github.com/KrisJordan>
 * @since   Version 1.0
 */
class Slim_Http_Request {

    const METHOD_HEAD = 'HEAD';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_OVERRIDE = '_METHOD';

    /**
     * @var string  Request method (ie. "GET", "POST", "PUT", or "DELETE")
     */
    public $method;

    /**
     * @var string  Resource URI (ie. "/person/1")
     */
    public $resource;

    /**
     * @var string  The root URI of the Slim application with trailing slash.
     *              This will be "/" if the app is installed at the web
     *              document root.  If the app is installed in a
     *              sub-directory "/foo", this will be "/foo/".
     */
    public $root;

    /**
     * @var array   Key-value array of HTTP request headers
     */
    private $headers;

    /**
     * @var array   Key-value array of HTTP cookies
     */
    private $cookies;

    /**
     * @var bool    Is this an AJAX request?
     */
    public $isAjax;

    /**
     * @var array   Key-value array of HTTP GET parameters
     */
    private $get;

    /**
     * @var array   Key-value array of HTTP POST parameters
     */
    private $post;

    /**
     * @var array   Key-value array of HTTP PUT parameters
     */
    private $put;

    /**
     * @var string  Raw HTTP request input from POST and PUT requests
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
        if ( $this->method === self::METHOD_PUT ) {
            $this->input = file_get_contents('php://input');
            $this->put = get_magic_quotes_gpc() ? self::stripSlashesFromRequestData($this->getPutParameters()) : $this->getPutParameters();
        }
        $this->headers = $this->getHttpHeaders();
        $this->cookies = get_magic_quotes_gpc() ? self::stripSlashesFromRequestData($_COOKIE) : $_COOKIE;
        $this->isAjax = isset($this->headers['X_REQUESTED_WITH']) && $this->headers['X_REQUESTED_WITH'] === 'XMLHttpRequest';
        $this->checkForHttpMethodOverride();
    }

    /***** ACCESSOR METHODS FOR GET, POST, AND PUT DATA *****/

    /**
     * Fetch PUT|POST|GET parameter value
     *
     * This is the preferred method to fetch the value of a
     * PUT, POST, or GET parameter (searched in that order).
     *
     * @param   string      $key    The paramter name
     * @return  string|null         The value of parameter, or NULL if parameter not found
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
     * @param   string              $key    Name of parameter
     * @return  array|string|null           All parameters, parameter value if $key
     *                                      and parameter exists, or NULL if $key
     *                                      and parameter does not exist.
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
     * @param   string              $key    Name of parameter
     * @return  array|string|null           All parameters, parameter value if $key
     *                                      and parameter exists, or NULL if $key
     *                                      and parameter does not exist.
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
     * @param   string              $key    Name of parameter
     * @return  array|string|null           All parameters, parameter value if $key
     *                                      and parameter exists, or NULL if $key
     *                                      and parameter does not exist.
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
     * @param   string      $name   The cookie name
     * @return  string|null         The cookie value, or NULL if cookie not set
     */
    public function cookie( $name ) {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
    }

    /***** HELPERS *****/

    /**
     * Strip slashes from string or array of strings
     *
     * @param   array|string $rawData
     * @return  array|string
     */
    public static function stripSlashesFromRequestData( $rawData ) {
        return is_array($rawData) ? array_map(array('self', 'stripSlashesFromRequestData'), $rawData) : stripslashes($rawData);
    }

    /**
     * Extract resource URL
     *
     * Convert raw HTTP request URL into an application resource URI.
     * Exclude the application root URI and query string.
     *
     * @return  string
     */
    private function extractQueryString() {
        $this->root = rtrim(dirname($_SERVER['PHP_SELF']), '/');
        if ( !empty($_SERVER['PATH_INFO']) ) {
            $uri = $_SERVER['PATH_INFO'];
        } else {
            if ( isset($_SERVER['REQUEST_URI']) ) {
                $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                $uri = rawurldecode($uri);
            } else if ( isset($_SERVER['PHP_SELF']) ) {
                $uri = $_SERVER['PHP_SELF'];
            } else {
                throw new RuntimeException('Unable to detect request URI');
            }
        }
        if ( $this->root !== '' && strpos($uri, $this->root) === 0 ) {
            $uri = substr($uri, strlen($this->root));
        }
        return $uri;
    }

    /**
     * Get PUT parameters
     *
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
     * Get HTTP request headers
     *
     * @author  Kris Jordan <http://www.github.com/KrisJordan>
     * @author  Jud Stephenson <http://judstephenson.com/blog>
     * @return  array
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
     * Get HTTP request header
     *
     * @param   string      $name   The header name
     * @return  string|null         The header value, or NULL if header does not exist
     */
    public function header( $name ) {
        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

    /**
     * Check for HTTP request method override
     *
     * Because traditional web browsers do not support PUT and DELETE
     * HTTP methods, we use a hidden form input field to
     * mimic PUT and DELETE requests. We check for this override here.
     *
     * @return  void
     */
    private function checkForHttpMethodOverride() {
        if ( array_key_exists(self::METHOD_OVERRIDE, $this->post) ) {
            $this->method = $this->post[self::METHOD_OVERRIDE];
            unset($this->post[self::METHOD_OVERRIDE]);
            if ( $this->method === self::METHOD_PUT ) {
                $this->put = $this->post;
            }
        }
    }

}

?>