<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@joshlockhart.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.5.0
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
 * usable by the Slim application.
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
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_OVERRIDE = '_METHOD';

    /**
     * @var string  Request method (ie. "GET", "POST", "PUT", "DELETE", "HEAD")
     */
    protected $method;

    /**
     * @var array   Key-value array of HTTP request headers
     */
    protected $headers;

    /**
     * @var array   Names of additional headers to parse from the current
     *              HTTP request that are not prefixed with "HTTP_"
     */
    protected $additionalHeaders = array('content-type', 'content-length', 'php-auth-user', 'php-auth-pw', 'auth-type', 'x-requested-with');

    /**
     * @var array   Key-value array of cookies sent with the
     *              current HTTP request
     */
    protected $cookies;

    /**
     * @var array   Key-value array of HTTP GET parameters
     */
    protected $get;

    /**
     * @var array   Key-value array of HTTP POST parameters
     */
    protected $post;

    /**
     * @var array   Key-value array of HTTP PUT parameters
     */
    protected $put;

    /**
     * @var string  Raw body of HTTP request
     */
    protected $body;

    /**
     * @var string  Content type of HTTP request
     */
    protected $contentType;

    /**
     * @var string  Resource URI (ie. "/person/1")
     */
    protected $resource;

    /**
     * @var string  The root URI of the Slim application without trailing slash.
     *              This will be "" if the app is installed at the web
     *              document root.  If the app is installed in a
     *              sub-directory "/foo", this will be "/foo".
     */
    protected $root;

    /**
     * Constructor
     */
    public function __construct() {
        $this->method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : false;
        $this->headers = $this->loadHttpHeaders();
        $this->body = @file_get_contents('php://input');
        $this->get = self::stripSlashesIfMagicQuotes($_GET);
        $this->post = self::stripSlashesIfMagicQuotes($_POST);
        $this->put = self::stripSlashesIfMagicQuotes($this->loadPutParameters());
        $this->cookies = self::stripSlashesIfMagicQuotes($_COOKIE);
        $this->root = Slim_Http_Uri::getBaseUri(true);
        $this->resource = Slim_Http_Uri::getUri(true);
        $this->checkForHttpMethodOverride();
    }

    /**
     * Is this a GET request?
     * @return bool
     */
    public function isGet() {
        return $this->method === self::METHOD_GET;
    }

    /**
     * Is this a POST request?
     * @return bool
     */
    public function isPost() {
        return $this->method === self::METHOD_POST;
    }

    /**
     * Is this a PUT request?
     * @return bool
     */
    public function isPut() {
        return $this->method === self::METHOD_PUT;
    }

    /**
     * Is this a DELETE request?
     * @return bool
     */
    public function isDelete() {
        return $this->method === self::METHOD_DELETE;
    }

    /**
     * Is this a HEAD request?
     * @return bool
     */
    public function isHead() {
        return $this->method === self::METHOD_HEAD;
    }

    /**
     * Is this a OPTIONS request?
     * @return bool
     */
    public function isOptions() {
        return $this->method === self::METHOD_OPTIONS;
    }

    /**
     * Is this a XHR request?
     * @return bool
     */
    public function isAjax() {
        return ( $this->params('isajax') || $this->headers('X_REQUESTED_WITH') === 'XMLHttpRequest' );
    }

    /**
     * Fetch a PUT|POST|GET parameter value
     *
     * The preferred method to fetch the value of a
     * PUT, POST, or GET parameter (searched in that order).
     *
     * @param   string      $key    The paramter name
     * @return  string|null         The value of parameter, or NULL if parameter not found
     */
    public function params( $key ) {
        foreach ( array('put', 'post', 'get') as $dataSource ) {
            $source = $this->$dataSource;
            if ( isset($source[(string)$key]) ) {
                return $source[(string)$key];
            }
        }
        return null;
    }

    /**
     * Fetch GET parameter(s)
     * @param   string              $key    Name of parameter
     * @return  array|string|null           All parameters, parameter value if $key
     *                                      and parameter exists, or NULL if $key
     *                                      and parameter does not exist.
     */
    public function get( $key = null ) {
        return $this->arrayOrArrayValue($this->get, $key);
    }

    /**
     * Fetch POST parameter(s)
     * @param   string              $key    Name of parameter
     * @return  array|string|null           All parameters, parameter value if $key
     *                                      and parameter exists, or NULL if $key
     *                                      and parameter does not exist.
     */
    public function post( $key = null ) {
        return $this->arrayOrArrayValue($this->post, $key);
    }

    /**
     * Fetch PUT parameter(s)
     * @param   string              $key    Name of parameter
     * @return  array|string|null           All parameters, parameter value if $key
     *                                      and parameter exists, or NULL if $key
     *                                      and parameter does not exist.
     */
    public function put( $key = null ) {
        return $this->arrayOrArrayValue($this->put, $key);
    }

    /**
     * Fetch COOKIE value(s)
     * @param   string      $key    The cookie name
     * @return  array|string|null   All parameters, parameter value if $key
     *                              and parameter exists, or NULL if $key
     *                              and parameter does not exist.
     */
    public function cookies( $key = null ) {
        return $this->arrayOrArrayValue($this->cookies, $key);
    }

    /**
     * Get HTTP request header
     * @param   string      $key    The header name
     * @return  array|string|null   All parameters, parameter value if $key
     *                              and parameter exists, or NULL if $key
     *                              and parameter does not exist.
     */
    public function headers( $key = null ) {
        return is_null($key) ? $this->headers : $this->arrayOrArrayValue($this->headers, $this->convertHttpHeaderName($key));
    }

    /**
     * Get HTTP request body
     * @return string|false String, or FALSE if body could not be read
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Get HTTP method
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * Get HTTP request content type
     * @return string
     */
    public function getContentType() {
        if ( !isset($this->contentType) ) {
            $contentType = 'application/x-www-form-urlencoded';
            $header = $this->headers('CONTENT_TYPE');
            if ( !is_null($header) ) {
                $headerParts = preg_split('/\s*;\s*/', $header);
                $contentType = $headerParts[0];
            }
            $this->contentType = $contentType;
        }
        return $this->contentType;
    }

    /**
     * Get HTTP request resource URI
     * @return string
     */
    public function getResourceUri() {
        return $this->resource;
    }

    /**
     * Get HTTP request root URI
     * @return string
     */
    public function getRootUri() {
        return $this->root;
    }

    /**
     * Fetch array or array value
     * @param   array           $array
     * @param   string          $key
     * @return  array|mixed     Array if key is null, else array value
     */
    protected function arrayOrArrayValue( array &$array, $key = null ) {
        return is_null($key) ? $array : $this->arrayValueForKey($array, $key);
    }

    /**
     * Fetch value from array
     * @return mixed|null
     */
    protected function arrayValueForKey( array &$array, $key ) {
        return isset($array[(string)$key]) ? $array[(string)$key] : null;
    }

    /**
     * Strip slashes from string or array of strings
     * @param   array|string $rawData
     * @return  array|string
     */
    public static function stripSlashesIfMagicQuotes( $rawData ) {
        if ( get_magic_quotes_gpc() ) {
            return is_array($rawData) ? array_map(array('self', 'stripSlashesIfMagicQuotes'), $rawData) : stripslashes($rawData);
        } else {
            return $rawData;
        }
    }

    /**
     * Get PUT parameters
     * @return array Key-value array of HTTP request PUT parameters
     */
    protected function loadPutParameters() {
        if ( $this->getContentType() === 'application/x-www-form-urlencoded' ) {
            $input = is_string($this->body) ? $this->body : '';
            if ( function_exists('mb_parse_str') ) {
                mb_parse_str($input, $output);
            } else {
                parse_str($input, $output);
            }
            return $output;
        } else {
            return array();
        }
    }

    /**
     * Get HTTP request headers
     * @return array Key-value array of HTTP request headers
     */
    protected function loadHttpHeaders() {
        $headers = array();
        foreach ( $_SERVER as $key => $value ) {
            $key = $this->convertHttpHeaderName($key);
            if ( strpos($key, 'http-') === 0 || in_array($key, $this->additionalHeaders) ) {
                $name = str_replace('http-', '', $key);
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    /**
     * Convert HTTP header name
     * @return string
     */
    protected function convertHttpHeaderName( $name ) {
        return str_replace('_', '-', strtolower($name));
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
    protected function checkForHttpMethodOverride() {
        if ( isset($this->post[self::METHOD_OVERRIDE]) ) {
            $this->method = $this->post[self::METHOD_OVERRIDE];
            unset($this->post[self::METHOD_OVERRIDE]);
            if ( $this->isPut() ) {
                $this->put = $this->post;
            }
        }
    }

}