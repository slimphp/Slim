<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.0
 * @package     Slim
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
namespace Slim;

/**
 * Environment
 *
 * This class determines the environmental variables used by
 * the Slim application. It removes the Slim application's dependency
 * on the $_SERVER superglobal and lets the Slim application
 * depend on a controlled set of environmental variables that may be
 * mocked, if necessary.
 *
 * Only one instance of \Slim\Environment will be created. It will be stored
 * and returned as a singleton value.
 *
 * The set of environmental variables mirrors the Rack (Ruby) specification
 * as closely as possible. The environmental variables are:
 *
 *     1. SERVER_PROTOCOL
 *     The HTTP request protocol (e.g. "HTTP/1.1")
 *
 *     2. REQUEST_METHOD
 *     The HTTP request method (e.g. "GET", "POST", "PUT", "DELETE")
 *
 *     3. SCRIPT_NAME
 *     The initial portion of the request URI’s “path” that corresponds to the
 *     physical directory in which the Slim application is installed — so that
 *     the application knows its virtual “location”. This may be an empty string
 *     if the application is installed in the top-level of the public document
 *     root directory. This will never have a trailing slash.
 *
 *     4. PATH_INFO
 *     The remaining portion of the request URI’s “path” that determines the
 *     “virtual” location of the HTTP request’s target resource within the Slim
 *     application context. This will always have a leading slash; it may or
 *     may not have a trailing slash.
 *
 *     5. QUERY_STRING
 *     The part of the HTTP request’s URI after, but not including, the “?”.
 *     This is required but may be an empty string.
 *
 *     6. SERVER_NAME
 *     This is the `Host:` HTTP header. When combined with SCRIPT_NAME and PATH_INFO,
 *     this can be used to create a fully qualified URL to an application resource.
 *     However, if HTTP_HOST is present, that should be used instead of this.
 *     This is required and may never be an empty string.
 *
 *     7. SERVER_PORT
 *     When combined with SCRIPT_NAME and PATH_INFO, this can be used to create a
 *     fully qualified URL to any application resource. This is required and
 *     may never be an empty string.
 *
 *     8. HTTP_*
 *     Variables matching the HTTP request headers sent by the client. The existence
 *     of these variables correspond with those sent in the current HTTP request.
 *     These variables will retain the "HTTP_" prefix.
 *
 *     9. REMOTE_ADDR
 *     The IP address from which the user is viewing the current page.
 *
 *     10. slim.url_scheme
 *     The HTTP request scheme. Will be “http” or “https”.
 *
 *     11. slim.input
 *     The raw HTTP request body. If the HTTP request body is empty (e.g. with a GET request),
 *     this will be an empty string.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.6.0
 */
class Environment implements \ArrayAccess, \IteratorAggregate
{
    /**
     * The environmental variables
     * @var array
     */
    protected $properties;

    /**
     * A singleton reference to the \Slim\Environment instance
     * @var \Slim\Environment
     */
    protected static $environment;

    /**
     * Get instance
     * @param  bool               $refresh  Refresh environmental variables?
     * @return \Slim\Environment
     */
    public static function getInstance($refresh = false)
    {
        if (is_null(static::$environment) || $refresh) {
            static::$environment = new static();
        }

        return static::$environment;
    }

    /**
     * Mock environment
     *
     * Use this method to create a set of mock environmental variables
     * instead of relying on the $_SERVER superglobal. This is useful
     * for unit testing.
     *
     * @param  array                $userSettings
     * @return \Slim\Environment
     */
    public static function mock($userSettings = array())
    {
        $defaults = array(
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD'  => 'GET',
            'SCRIPT_NAME'     => '',
            'PATH_INFO'       => '',
            'QUERY_STRING'    => '',
            'SERVER_NAME'     => 'localhost',
            'SERVER_PORT'     => 80,
            'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'HTTP_ACCEPT_CHARSET'  => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'HTTP_USER_AGENT'      => 'Slim Framework',
            'REMOTE_ADDR'     => '127.0.0.1',
            'slim.url_scheme' => 'http',
            'slim.input'      => ''
        );
        static::$environment = new self(array_merge($defaults, $userSettings));

        return static::$environment;
    }

    /**
     * Constructor (private)
     * @param array $settings Environmental variables. Leave blank to use $_SERVER superglobal
     */
    private function __construct(array $settings = null)
    {
        if ($settings) {
            $this->properties = $settings;
        } else {
            $env = array();

            // The HTTP request method
            $env['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'];

            // The IP address
            $env['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];

            /**
             * Application paths
             *
             * This derives two paths: SCRIPT_NAME and PATH_INFO. The SCRIPT_NAME
             * is the real, physical path to the application, be it in the root
             * directory or a subdirectory of the public document root. The PATH_INFO is the
             * virtual path to the requested resource within the application context.
             *
             * With htaccess, the SCRIPT_NAME will be an absolute path (without file name);
             * if not using htaccess, it will also include the file name. If it is "/",
             * it is set to an empty string (since it cannot have a trailing slash).
             *
             * The PATH_INFO will be an absolute path with a leading slash; this will be
             * used for application routing.
             */
            if (strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) === 0) {
                $env['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME']; //Without URL rewrite
            } else {
                $env['SCRIPT_NAME'] = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); //With URL rewrite
            }
            $env['PATH_INFO'] = substr_replace($_SERVER['REQUEST_URI'], '', 0, strlen($env['SCRIPT_NAME']));
            if (strpos($env['PATH_INFO'], '?') !== false) {
                $env['PATH_INFO'] = substr_replace($env['PATH_INFO'], '', strpos($env['PATH_INFO'], '?')); //query string is not removed automatically
            }
            $env['SCRIPT_NAME'] = rtrim($env['SCRIPT_NAME'], '/');
            $env['PATH_INFO'] = '/' . ltrim($env['PATH_INFO'], '/');

            // The portion of the request URI following the '?'
            $env['QUERY_STRING'] = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

            // Name of server host that is running the script
            $env['SERVER_NAME'] = $_SERVER['SERVER_NAME'];

            // Number of server port that is running the script
            $env['SERVER_PORT'] = $_SERVER['SERVER_PORT'];

            // Request protocol (e.g. "HTTP/1.1")
            $env['SERVER_PROTOCOL'] = $_SERVER['SERVER_PROTOCOL'];

            // HTTP request headers (retains HTTP_ prefix to match $_SERVER)
            $headers = \Slim\Http\Headers::extract($_SERVER);
            foreach ($headers as $key => $value) {
                $env[$key] = $value;
            }

            // Is the application running under HTTPS or HTTP protocol?
            $env['slim.url_scheme'] = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';

            // Raw request body (readable one time only; not available for multipart/form-data requests)
            $rawInput = file_get_contents('php://input');
            if ($rawInput === false) {
                $rawInput = '';
            }
            $env['slim.input'] = $rawInput;

            $this->properties = $env;
        }
    }

    /**
     * Array Access: Offset Exists
     * @param  mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->properties[$offset]);
    }

    /**
     * Array Access: Offset Get
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if (isset($this->properties[$offset])) {
            return $this->properties[$offset];
        } else {
            return null;
        }
    }

    /**
     * Array Access: Offset Set
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->properties[$offset] = $value;
    }

    /**
     * Array Access: Offset Unset
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }

    /**
     * IteratorAggregate
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->properties);
    }
}
