<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.4
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
class Environment extends \Slim\Collection
{
    /**
     * The raw HTTP request body, readable only once from `php://input`
     * @var string
     */
    protected static $requestBody;

    /**
     * Mock environment
     *
     * Use this method to create a set of mock environmental variables
     * instead of relying on the $_SERVER superglobal. This is useful
     * for unit testing.
     *
     * @param  array             $userSettings
     * @return \Slim\Environment
     * @api
     */
    public static function mock(array $userSettings = array())
    {
        return new static(array_merge(array(
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'REQUEST_METHOD'       => 'GET',
            'SCRIPT_NAME'          => '',
            'PATH_INFO'            => '/',
            'QUERY_STRING'         => '',
            'SERVER_NAME'          => 'localhost',
            'SERVER_PORT'          => 80,
            'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'HTTP_ACCEPT_CHARSET'  => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'HTTP_USER_AGENT'      => 'Slim Framework',
            'REMOTE_ADDR'          => '127.0.0.1',
            'slim.url_scheme'      => 'http',
            'slim.input'           => ''
        ), $userSettings));
    }

    /**
     * Constructor
     * @param  array $settings Environmental variables. Leave blank to use $_SERVER superglobal
     * @api
     */
    public function __construct(array $settings = null)
    {
        if (is_null($settings)) {
            $settings = array();

            // Request protocol (e.g. "HTTP/1.1")
            $settings['SERVER_PROTOCOL'] = $_SERVER['SERVER_PROTOCOL'];

            // Request method
            $settings['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'];

	        // Server params
            $scriptName = $_SERVER['SCRIPT_NAME']; // <-- "/foo/index.php"
            $requestUri = $_SERVER['REQUEST_URI']; // <-- "/foo/bar?test=abc" or "/foo/index.php/bar?test=abc"
            $queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : ''; // <-- "test=abc" or ""

            // Physical path
            if (strpos($requestUri, $scriptName) !== false) {
                $physicalPath = $scriptName; // <-- Without rewriting
            } else {
                $physicalPath = ltrim(dirname($scriptName), '\\'); // <-- With rewriting
            }
            $settings['SCRIPT_NAME'] = rtrim($physicalPath, '/'); // <-- Remove trailing slashes

            // Virtual path
            $settings['PATH_INFO'] = substr_replace($requestUri, '', 0, strlen($physicalPath)); // <-- Remove physical path
            $settings['PATH_INFO'] = str_replace('?' . $queryString, '', $settings['PATH_INFO']); // <-- Remove query string
            $settings['PATH_INFO'] = '/' . ltrim($settings['PATH_INFO'], '/'); // <-- Ensure leading slash

            // Query string (without leading "?")
            $settings['QUERY_STRING'] = $queryString;

            $settings['SERVER_NAME'] = $_SERVER['SERVER_NAME'];

            // Server port
            $settings['SERVER_PORT'] = $_SERVER['SERVER_PORT'];

            // Request headers (with "HTTP_" prefix)
            $headers = \Slim\Http\Headers::find($_SERVER);
            foreach ($headers as $key => $value) {
                $settings[$key] = $value;
            }

            // IP address
            $settings['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];

            // Request scheme ("http" or "https")
            $settings['slim.url_scheme'] = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';

            // Request body (readable one time only; not available for multipart/form-data requests)
            if (is_null(static::$requestBody)) {
                $body = file_get_contents('php://input');
                if ($body === false) {
                    $body = '';
                }
                static::$requestBody = $body;
            }
            $settings['slim.input'] = static::$requestBody;
        }

        parent::__construct($settings);
    }
}
