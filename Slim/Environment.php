<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.6.0
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
 * Environment
 *
 * This class creates and returns a key/value array of common
 * environment variables for the current HTTP request.
 *
 * This is a singleton class; derived environment variables will
 * be common across multiple Slim applications.
 *
 * This class matches the Rack (Ruby) specification as closely
 * as possible. More information available below.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.6.0
 */
class Slim_Environment {
    /**
     * @var array
     */
    private static $env;

    /**
     * Constructor
     * @throws RuntimeException
     */
    public function __construct() {
        throw new RuntimeException('Use Slim_Environment::getInstance() instead');
    }

    /**
     * Prepare mock environment
     *
     * Use this method to ensure the application environment uses a predefined array
     * rather than rely on the $_SERVER superglobal array. This is useful for testing.
     * It is your responsibility to ensure your mock array includes all required
     * environment variables.
     *
     * @param   array   $mock
     * @return  array
     */
    public static function mock( $mock = array() ) {
        self::$env = array_merge(array(
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '',
            'PATH_INFO' => '',
            'QUERY_STRING' => '',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'USER_AGENT' => 'Slim Framework',
            'REMOTE_ADDR' => '127.0.0.1',
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ), $mock);
        return self::$env;
    }

    /**
     * Get Instance
     *
     * This method returns the Singleton instance of the app
     * environment; if not already set or if a refreshed
     * environment is requested, parse the environment
     * and return the newly stored results.
     *
     * @param   bool    $refresh
     * @return  array
     */
    public static function getInstance( $refresh = false ) {
        if ( !isset(self::$env) || $refresh ) {
            self::$env = self::prepare();
        }
        return self::$env;
    }

    /**
     * Prepare environment
     *
     * Extract the necessary environment variables for a Slim application.
     * This method adheres to the Rack (Ruby) specification. Descriptions below are
     * paraphrased from <http://rack.rubyforge.org/doc/files/SPEC.html> for consistency.
     *
     * REQUEST_METHOD
     *     The current HTTP request method: GET, POST, PUT, DELETE, OPTIONS, HEAD. Cannot be empty.
     * SCRIPT_NAME
     *     The initial portion of the request URL's "path" that corresponds to the physical directory
     *     in which the Slim application is installed --- so that the application knows its virtual "location".
     *     This may be an empty string if the application is installed in the top-level of the
     *     public document root directory. This will never have a trailing slash.
     * PATH_INFO
     *     The remaining portion of the request URL's "path" that determines the "virtual" location of the
     *     HTTP request's target resource within the Slim application's context. This will always have
     *     a leading slash; it may or may not have a trailing slash.
     * QUERY_STRING
     *     The part of the HTTP request's URL after, but not including, the "?". May be empty, but is always required!
     * SERVER_NAME
     *     When combined with the SCRIPT_NAME and PATH_INFO, this can be used to create a fully qualified URL
     *     to an application resource. However, if HTTP_HOST is present, that should be used instead of this.
     *     This is required and may never be an empty string.
     * SERVER_PORT
     *     When combined with the SCRIPT_NAME and PATH_INFO, this can be used to create a fully qualified URL
     *     to any application resource. This is required and may never be an empty string.
     * HTTP_*
     *     Variables matching the HTTP request headers sent by the client with the HTTP request. The existence
     *     of these variables correspond with those sent in the current HTTP request. These variables' names
     *     will NOT be prefixed with HTTP_.
     * slim.url_scheme
     *     Will be "http" or "https" depending on the HTTP request URL.
     * slim.input
     *     Will be a string representing raw request data sent with the HTTP request. If raw request data is
     *     unavailable (i.e. with a GET request), this will be an empty string.
     * slim.errors
     *     Must always be a writable resource; by default this is a write-only resource handle to php://stderr
     */
    private static function prepare() {
        $env = array();

        //The HTTP request method
        $env['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'];

        //The IP
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
        if ( strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) === 0 ) {
            //Without URL rewrite
            $env['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'];
            if ( isset($_SERVER['PATH_INFO']) ) {
                $env['PATH_INFO'] = $_SERVER['PATH_INFO'];
            } else {
                $env['PATH_INFO'] = substr_replace($_SERVER['REQUEST_URI'], '', 0, strlen($env['SCRIPT_NAME']));
            }
        } else {
            //With URL rewrite
            $env['SCRIPT_NAME'] = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME);
            $env['PATH_INFO'] = substr_replace($_SERVER['REQUEST_URI'], '', 0, strlen($env['SCRIPT_NAME']));
            if ( strpos($env['PATH_INFO'], '?') !== false ) {
                $env['PATH_INFO'] = substr_replace($env['PATH_INFO'], '', strpos($env['PATH_INFO'], '?')); //query string is not removed automatically
            }
        }
        $env['SCRIPT_NAME'] = rtrim($env['SCRIPT_NAME'], '/');
        $env['PATH_INFO'] = '/' . ltrim($env['PATH_INFO'], '/');

        //The portion of the request URI following the '?'
        $env['QUERY_STRING'] = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

        //Name of server host that is running the script
        $env['SERVER_NAME'] = $_SERVER['SERVER_NAME'];

        //Number of server port that is running the script
        $env['SERVER_PORT'] = $_SERVER['SERVER_PORT'];

        //HTTP request headers
        $specialHeaders = array('CONTENT_TYPE', 'CONTENT_LENGTH', 'PHP_AUTH_USER', 'PHP_AUTH_PW', 'PHP_AUTH_DIGEST', 'AUTH_TYPE', 'X_FORWARDED_FOR', 'X_REQUESTED_WITH');
        foreach ( $_SERVER as $key => $value ) {
            if ( strpos($key, 'HTTP_') === 0 ) {
                $env[substr($key, 5)] = $value;
            } else if ( in_array($key, $specialHeaders) ) {
                $env[$key] = $value;
            }
        }

        //Is the application running under HTTPS or HTTP protocol?
        $env['slim.url_scheme'] = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';

        //Input stream (readable one time only; not available for mutipart/form-data requests)
        $rawInput = @file_get_contents('php://input');
        if ( !$rawInput ) {
            $rawInput = '';
        }
        $env['slim.input'] = $rawInput;

        //Error stream
        $env['slim.errors'] = fopen('php://stderr', 'w');

        return $env;
    }
}
