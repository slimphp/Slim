<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.4.2
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
class Environment implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @var array
     */
    protected $properties;

    /**
     * @var \Slim\Environment
     */
    protected static $environment;

    /**
     * Get environment instance (singleton)
     *
     * This creates and/or returns an environment instance (singleton)
     * derived from $_SERVER variables. You may override the global server
     * variables by using `\Slim\Environment::mock()` instead.
     *
     * @param  bool             $refresh Refresh properties using global server variables?
     * @return \Slim\Environment
     */
    public static function getInstance($refresh = false, $userSettings = array())
    {
        if (is_null(self::$environment) || $refresh) {
            self::$environment = new self($userSettings);
        }

        return self::$environment;
    }

    /**
     * Get mock environment instance
     *
     * @param  array       $userSettings
     * @return \Slim\Environment
     */
    public static function mock($userSettings = array())
    {
        $defaults = array(
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
        );
        self::$environment = new self(array_merge($defaults, $userSettings));

        return self::$environment;
    }

    /**
     * Constructor (private access)
     *
     * @param  array $settings If present, these are merged with the global server variables
     */
    private function __construct($settings = array())
	{
		$env = array();

		//The HTTP request method
		$env['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'];

		//The IP
		$env['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];

		// Server params
		$scriptName = $_SERVER['SCRIPT_NAME']; // <-- "/foo/index.php"
		$requestUri = $_SERVER['REQUEST_URI']; // <-- "/foo/bar?test=abc" or "/foo/index.php/bar?test=abc"
		$queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : ''; // <-- "test=abc" or ""

		// Physical path
		if (strpos($requestUri, $scriptName) !== false) {
			$physicalPath = $scriptName; // <-- Without rewriting
		} else {
			$physicalPath = str_replace('\\', '', dirname($scriptName)); // <-- With rewriting
		}
		$env['SCRIPT_NAME'] = rtrim($physicalPath, '/'); // <-- Remove trailing slashes

		// Virtual path
		if (isset($settings['route.base']))
			$env['PATH_INFO'] = substr($requestUri, strlen($settings['route.base'])-1); // <-- remove route base from requestUri
		else
		{
			if (strpos($requestUri, $physicalPath) === 0) {
				$env['PATH_INFO'] = substr($requestUri, strlen($physicalPath)); // <-- Remove physical path
			} else {
				$env['PATH_INFO'] = $requestUri; // <-- Physical path is not a part of request uri
			}
			$env['PATH_INFO'] = str_replace('?' . $queryString, '', $env['PATH_INFO']); // <-- Remove query string
			$env['PATH_INFO'] = '/' . ltrim($env['PATH_INFO'], '/'); // <-- Ensure leading slash
		}

		// Query string (without leading "?")
		$env['QUERY_STRING'] = $queryString;

		//Name of server host that is running the script
		$env['SERVER_NAME'] = $_SERVER['SERVER_NAME'];

		//Number of server port that is running the script
		$env['SERVER_PORT'] = $_SERVER['SERVER_PORT'];

		//HTTP request headers (retains HTTP_ prefix to match $_SERVER)
		$headers = \Slim\Http\Headers::extract($_SERVER);
		foreach ($headers as $key => $value) {
			$env[$key] = $value;
		}

		//Is the application running under HTTPS or HTTP protocol?
		$env['slim.url_scheme'] = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';

		//Input stream (readable one time only; not available for multipart/form-data requests)
		$rawInput = @file_get_contents('php://input');
		if (!$rawInput) {
			$rawInput = '';
		}
		$env['slim.input'] = $rawInput;

		//Error stream
		$env['slim.errors'] = @fopen('php://stderr', 'w');

		// Merge in settings overwriting any default server variables
		$env = array_merge($env, $settings);

		$this->properties = $env;
	}

    /**
     * Array Access: Offset Exists
     */
    public function offsetExists($offset)
    {
        return isset($this->properties[$offset]);
    }

    /**
     * Array Access: Offset Get
     */
    public function offsetGet($offset)
    {
        if (isset($this->properties[$offset])) {
            return $this->properties[$offset];
        }

        return null;
    }

    /**
     * Array Access: Offset Set
     */
    public function offsetSet($offset, $value)
    {
        $this->properties[$offset] = $value;
    }

    /**
     * Array Access: Offset Unset
     */
    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }

    /**
     * IteratorAggregate
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->properties);
    }
}
