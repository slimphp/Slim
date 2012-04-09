<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author	  Josh Lockhart <info@joshlockhart.com>
 * @copyright   2011 Josh Lockhart
 * @link		http://www.slimframework.com
 * @license	 http://www.slimframework.com/license
 * @version	 1.5.0
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
use Slim\Http\Request,
Slim\Http\Response,
Slim\Route,
Slim\Router,
Slim\View,
Slim\Log,
Slim\Logger,
Slim\Session\Handler\Cookies,
Slim\Session\Flash,
Slim\Http\CookieJar,
Slim\Session\Handler,
Slim\Exception\Stop,
Slim\Exception\Pass,
Slim\Exception\RequestSlash;

//Ensure PHP session IDs only use the characters [a-z0-9]
ini_set('session.hash_bits_per_character', 4);
ini_set('session.hash_function', 0);

//Slim's Encryted Cookies rely on libmcyrpt and these two constants.
//If libmycrpt is unavailable, we ensure the expected constants
//are available to avoid errors.
if (!defined('MCRYPT_RIJNDAEL_256')) {
	define('MCRYPT_RIJNDAEL_256', 0);
}
if (!defined('MCRYPT_MODE_CBC')) {
	define('MCRYPT_MODE_CBC', 0);
}

//This determines which errors are reported by PHP. By default, all
//errors (including E_STRICT) are reported.
error_reporting(E_ALL | E_STRICT);

//This tells PHP to auto-load classes using Slim's autoloader; this will
//only auto-load a class file located in the same directory as Slim.php
//whose file name (excluding the final dot and extension) is the same
//as its class name (case-sensitive). For example, "View.php" will be
//loaded when Slim uses the "View" class for the first time.
spl_autoload_register(array('Slim', 'autoload'));

//PHP 5.3 will complain if you don't set a timezone. If you do not
//specify your own timezone before requiring Slim, this tells PHP to use UTC.
if (@date_default_timezone_set(date_default_timezone_get()) === false) {
	date_default_timezone_set('UTC');
}

/**
 * Slim
 *
 * @package Slim
 * @author  Josh Lockhart <info@joshlockhart.com>
 * @since   Version 1.0
 */
class Slim
{

	/**
	 * @var array[Slim]
	 */
	protected static $apps = array();

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Response
	 */
	protected $response;

	/**
	 * @var Router
	 */
	protected $router;

	/**
	 * @var View
	 */
	protected $view;

	/**
	 * @var Log
	 */
	protected $log;

	/**
	 * @var array Key-value array of application settings
	 */
	protected $settings;

	/**
	 * @var string The application mode
	 */
	protected $mode;

	/**
	 * @var array Plugin hooks
	 */
	protected $hooks = array(
		'slim.before' => array(array()),
		'slim.before.router' => array(array()),
		'slim.before.dispatch' => array(array()),
		'slim.after.dispatch' => array(array()),
		'slim.after.router' => array(array()),
		'slim.after' => array(array())
	);

	/**
	 * Slim auto-loader
	 *
	 * Autoloader following the PSR-0 standard
	 * https://gist.github.com/1234504
	 *
	 * @static
	 *
	 * @param $className
	 *
	 * @return void
	 */
	public static function autoload($className)
	{
		$className = ltrim($className, '\\');
		$fileName = '';
		if ($lastNsPos = strripos($className, '\\')) {
			$namespace = substr($className, 0, $lastNsPos);
			$className = substr($className, $lastNsPos + 1);
			$fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

		require_once $fileName;
	}

	/**
	 * Slim auto-loader
	 *
	 * This method lazy-loads class files when a given class if first used.
	 * Class files must exist in the same directory as this file and be named
	 * the same as its class definition (excluding the dot and extension).
	 *
	 * @return void
	 */
	/*
    public static function autoload( $class ) {
        if ( strpos($class, 'Slim') !== 0 ) {
            return;
        }
        $file = dirname(__FILE__) . '/' . str_replace('_', DIRECTORY_SEPARATOR, substr($class,5)) . '.php';
        if ( file_exists($file) ) {
            require $file;
        }
    }
	*/

	/***** INITIALIZATION *****/

	/**
	 * Constructor
	 *
	 * @param   array $userSettings
	 *
	 * @return  void
	 */
	public function __construct($userSettings = array())
	{
		//Merge application settings
		$this->settings = array_merge(array(
			//Mode
			'mode' => 'development',
			//Logging
			'log.enable' => false,
			'log.logger' => null,
			'log.path' => './logs',
			'log.level' => 4,
			//Debugging
			'debug' => true,
			//View
			'templates.path' => './templates',
			'view' => new View,
			//Settings for all cookies
			'cookies.lifetime' => '20 minutes',
			'cookies.path' => '/',
			'cookies.domain' => '',
			'cookies.secure' => false,
			'cookies.httponly' => false,
			//Settings for encrypted cookies
			'cookies.secret_key' => 'CHANGE_ME',
			'cookies.cipher' => MCRYPT_RIJNDAEL_256,
			'cookies.cipher_mode' => MCRYPT_MODE_CBC,
			'cookies.encrypt' => true,
			'cookies.user_id' => 'DEFAULT',
			//Session handler
			'session.handler' => new Cookies(),
			'session.flash_key' => 'flash',
			//HTTP
			'http.version' => null
		), $userSettings);

		//Determine application mode
		$this->getMode();

		//Setup HTTP request and response handling
		$this->request = new Request();
		$this->response = new Response($this->request);
		$this->response->setCookieJar(new CookieJar($this->settings['cookies.secret_key'], array(
			'high_confidentiality' => $this->settings['cookies.encrypt'],
			'mcrypt_algorithm' => $this->settings['cookies.cipher'],
			'mcrypt_mode' => $this->settings['cookies.cipher_mode'],
			'enable_ssl' => $this->settings['cookies.secure']
		)));
		$this->response->httpVersion($this->settings['http.version']);
		$this->router = new Router($this->request);

		//Start session if not already started
		if (session_id() === '') {
			$sessionHandler = $this->config('session.handler');
			if ($sessionHandler instanceof Handler) {
				/** @var $sessionHandler Handler */
				$sessionHandler->register($this);
			}
			session_cache_limiter(false);
			session_start();
		}

		//Setup view with flash messaging
		$this->view($this->config('view'))->setData('flash', new Flash($this->config('session.flash_key')));

		//Set app name
		if (!isset(self::$apps['default'])) {
			$this->setName('default');
		}

		//Set global Error handler after Slim app instantiated
		set_error_handler(array('Slim', 'handleErrors'));
	}

	/**
	 * Get application mode
	 * @return string
	 */
	public function getMode()
	{
		if (!isset($this->mode)) {
			if (isset($_ENV['SLIM_MODE'])) {
				$this->mode = (string)$_ENV['SLIM_MODE'];
			} else {
				$envMode = getenv('SLIM_MODE');
				if ($envMode !== false) {
					$this->mode = $envMode;
				} else {
					$this->mode = (string)$this->config('mode');
				}
			}
		}
		return $this->mode;
	}

	/***** NAMING *****/

	/**
	 * Get Slim application with name
	 *
	 * @param   string	  $name The name of the Slim application to fetch
	 *
	 * @return  Slim|null
	 */
	public static function getInstance($name = 'default')
	{
		return isset(self::$apps[(string)$name]) ? self::$apps[(string)$name] : null;
	}

	/**
	 * Set Slim application name
	 *
	 * @param string $name The name of this Slim application
	 *
	 * @return void
	 */
	public function setName($name)
	{
		$this->name = $name;
		self::$apps[$name] = $this;
	}

	/**
	 * Get Slim application name
	 * @return string|null
	 */
	public function getName()
	{
		return $this->name;
	}

	/***** LOGGING *****/

	/**
	 * Get application Log (lazy-loaded)
	 * @return Log
	 */
	public function getLog()
	{
		if (!isset($this->log)) {
			$this->log = new Log();
			$this->log->setEnabled($this->config('log.enable'));
			$logger = $this->config('log.logger');
			if ($logger) {
				$this->log->setLogger($logger);
			} else {
				$this->log->setLogger(new Logger($this->config('log.path'), $this->config('log.level')));
			}
		}
		return $this->log;
	}

	/***** CONFIGURATION *****/

	/**
	 * Configure Slim for a given mode
	 *
	 * This method will immediately invoke the callable if
	 * the specified mode matches the current application mode.
	 * Otherwise, the callable is ignored. This should be called
	 * only _after_ you initialize your Slim app.
	 *
	 * @param   string  $mode
	 * @param   mixed   $callable
	 *
	 * @return  void
	 */
	public function configureMode($mode, $callable)
	{
		if ($mode === $this->getMode() && is_callable($callable)) {
			call_user_func($callable);
		}
	}

	/**
	 * Configure Slim Settings
	 *
	 * This method defines application settings and acts as a setter and a getter.
	 *
	 * If only one argument is specified and that argument is a string, the value
	 * of the setting identified by the first argument will be returned, or NULL if
	 * that setting does not exist.
	 *
	 * If only one argument is specified and that argument is an associative array,
	 * the array will be merged into the existing application settings.
	 *
	 * If two arguments are provided, the first argument is the name of the setting
	 * to be created or updated, and the second argument is the setting value.
	 *
	 * @param   string|array	$name   If a string, the name of the setting to set or retrieve. Else an associated array of setting names and values
	 * @param   mixed		   $value  If name is a string, the value of the setting identified by $name
	 *
	 * @return  mixed		   The value of a setting if only one argument is a string
	 */
	public function config($name, $value = null)
	{
		if (func_num_args() === 1) {
			if (is_array($name)) {
				$this->settings = array_merge($this->settings, $name);
			} else {
				return in_array($name, array_keys($this->settings)) ? $this->settings[$name] : null;
			}
		} else {
			$this->settings[$name] = $value;
		}
		return '';
	}

	/***** ROUTING *****/

	/**
	 * Add GET|POST|PUT|DELETE route
	 *
	 * Adds a new route to the router with associated callable. This
	 * route will only be invoked when the HTTP request's method matches
	 * this route's method.
	 *
	 * ARGUMENTS:
	 *
	 * First:	   string  The URL pattern (REQUIRED)
	 * In-Between:  mixed   Anything that returns TRUE for `is_callable` (OPTIONAL)
	 * Last:		mixed   Anything that returns TRUE for `is_callable` (REQUIRED)
	 *
	 * The first argument is required and must always be the
	 * route pattern (ie. '/books/:id').
	 *
	 * The last argument is required and must always be the callable object
	 * to be invoked when the route matches an HTTP request.
	 *
	 * You may also provide an unlimited number of in-between arguments;
	 * each interior argument must be callable and will be invoked in the
	 * order specified before the route's callable is invoked.
	 *
	 * USAGE:
	 *
	 * Slim::get('/foo'[, middleware, middleware, ...], callable);
	 *
	 * @param   array (See notes above)
	 *
	 * @return  Route
	 */
	protected function mapRoute($args)
	{
		$pattern = array_shift($args);
		$callable = array_pop($args);
		$route = $this->router->map($pattern, $callable);
		if (count($args) > 0) {
			$route->setMiddleware($args);
		}
		return $route;
	}

	/**
	 * Add generic route without associated HTTP method
	 * @see Slim::mapRoute
	 * @return Route
	 */
	public function map()
	{
		$args = func_get_args();
		return $this->mapRoute($args);
	}

	/**
	 * Add GET route
	 * @see	 Slim::mapRoute
	 * @return  Route
	 */
	public function get()
	{
		$args = func_get_args();
		return $this->mapRoute($args)->via(Request::METHOD_GET, Request::METHOD_HEAD);
	}

	/**
	 * Add POST route
	 * @see	 Slim::mapRoute
	 * @return  Route
	 */
	public function post()
	{
		$args = func_get_args();
		return $this->mapRoute($args)->via(Request::METHOD_POST);
	}

	/**
	 * Add PUT route
	 * @see	 Slim::mapRoute
	 * @return  Route
	 */
	public function put()
	{
		$args = func_get_args();
		return $this->mapRoute($args)->via(Request::METHOD_PUT);
	}

	/**
	 * Add DELETE route
	 * @see	 Slim::mapRoute
	 * @return  Route
	 */
	public function delete()
	{
		$args = func_get_args();
		return $this->mapRoute($args)->via(Request::METHOD_DELETE);
	}

	/**
	 * Add OPTIONS route
	 * @see	 Slim::mapRoute
	 * @return  Route
	 */
	public function options()
	{
		$args = func_get_args();
		return $this->mapRoute($args)->via(Request::METHOD_OPTIONS);
	}

	/**
	 * Not Found Handler
	 *
	 * This method defines or invokes the application-wide Not Found handler.
	 * There are two contexts in which this method may be invoked:
	 *
	 * 1. When declaring the handler:
	 *
	 * If the $callable parameter is not null and is callable, this
	 * method will register the callable to be invoked when no
	 * routes match the current HTTP request. It WILL NOT invoke the callable.
	 *
	 * 2. When invoking the handler:
	 *
	 * If the $callable parameter is null, Slim assumes you want
	 * to invoke an already-registered handler. If the handler has been
	 * registered and is callable, it is invoked and sends a 404 HTTP Response
	 * whose body is the output of the Not Found handler.
	 *
	 * @param   mixed $callable Anything that returns true for is_callable()
	 *
	 * @return  void
	 */
	public function notFound($callable = null)
	{
		if (!is_null($callable)) {
			$this->router->notFound($callable);
		} else {
			ob_start();
			$customNotFoundHandler = $this->router->notFound();
			if (is_callable($customNotFoundHandler)) {
				call_user_func($customNotFoundHandler);
			} else {
				call_user_func(array($this, 'defaultNotFound'));
			}
			$this->halt(404, ob_get_clean());
		}
	}

	/**
	 * Error Handler
	 *
	 * This method defines or invokes the application-wide Error handler.
	 * There are two contexts in which this method may be invoked:
	 *
	 * 1. When declaring the handler:
	 *
	 * If the $argument parameter is callable, this
	 * method will register the callable to be invoked when an uncaught
	 * Exception is detected, or when otherwise explicitly invoked.
	 * The handler WILL NOT be invoked in this context.
	 *
	 * 2. When invoking the handler:
	 *
	 * If the $argument parameter is not callable, Slim assumes you want
	 * to invoke an already-registered handler. If the handler has been
	 * registered and is callable, it is invoked and passed the caught Exception
	 * as its one and only argument. The error handler's output is captured
	 * into an output buffer and sent as the body of a 500 HTTP Response.
	 *
	 * @param   mixed $argument Callable|Exception
	 *
	 * @return  void
	 */
	public function error($argument = null)
	{
		if (is_callable($argument)) {
			//Register error handler
			$this->router->error($argument);
		} else {
			//Invoke error handler
			ob_start();
			$customErrorHandler = $this->router->error();
			if (is_callable($customErrorHandler)) {
				call_user_func_array($customErrorHandler, array($argument));
			} else {
				call_user_func_array(array($this, 'defaultError'), array($argument));
			}
			$this->halt(500, ob_get_clean());
		}
	}

	/***** ACCESSORS *****/

	/**
	 * Get the Request object
	 * @return Request
	 */
	public function request()
	{
		return $this->request;
	}

	/**
	 * Get the Response object
	 * @return Response
	 */
	public function response()
	{
		return $this->response;
	}

	/**
	 * Get the Router object
	 * @return Router
	 */
	public function router()
	{
		return $this->router;
	}

	/**
	 * Get and/or set the View
	 *
	 * This method declares the View to be used by the Slim application.
	 * If the argument is a string, Slim will instantiate a new object
	 * of the same class. If the argument is an instance of View or a subclass
	 * of View, Slim will use the argument as the View.
	 *
	 * If a View already exists and this method is called to create a
	 * new View, data already set in the existing View will be
	 * transferred to the new View.
	 *
	 * @param   string|View $viewClass  The name of a View class;
	 *									   An instance of View;
	 *
	 * @return  View
	 */
	public function view($viewClass = null)
	{
		if (!is_null($viewClass)) {
			$existingData = is_null($this->view) ? array() : $this->view->getData();
			if ($viewClass instanceOf View) {
				$this->view = $viewClass;
			} else {
				$this->view = new $viewClass();
			}
			$this->view->appendData($existingData);
			$this->view->setTemplatesDirectory($this->config('templates.path'));
		}
		return $this->view;
	}

	/***** RENDERING *****/

	/**
	 * Render a template
	 *
	 * Call this method within a GET, POST, PUT, DELETE, NOT FOUND, or ERROR
	 * callable to render a template whose output is appended to the
	 * current HTTP response body. How the template is rendered is
	 * delegated to the current View.
	 *
	 * @param   string  $template   The name of the template passed into the View::render method
	 * @param   array   $data	   Associative array of data made available to the View
	 * @param   int	 $status	 The HTTP response status code to use (Optional)
	 *
	 * @return  void
	 */
	public function render($template, $data = array(), $status = null)
	{
		if (!is_null($status)) {
			$this->response->status($status);
		}
		$this->view->appendData($data);
		$this->view->display($template);
	}

	/***** HTTP CACHING *****/

	/**
	 * Set Last-Modified HTTP Response Header
	 *
	 * Set the HTTP 'Last-Modified' header and stop if a conditional
	 * GET request's `If-Modified-Since` header matches the last modified time
	 * of the resource. The `time` argument is a UNIX timestamp integer value.
	 * When the current request includes an 'If-Modified-Since' header that
	 * matches the specified last modified time, the application will stop
	 * and send a '304 Not Modified' response to the client.
	 *
	 * @param   int						 $time   The last modified UNIX timestamp
	 *
	 * @throws  Exception					   Returns HTTP 304 Not Modified response if resource last modified time matches `If-Modified-Since` header
	 * @throws  InvalidArgumentException			If provided timestamp is not an integer
	 * @return  void
	 */
	public function lastModified($time)
	{
		if (is_integer($time)) {
			$this->response->header('Last-Modified', date(DATE_RFC1123, $time));
			if ($time === strtotime($this->request->headers('IF_MODIFIED_SINCE'))) $this->halt(304);
		} else {
			throw new InvalidArgumentException('Slim::lastModified only accepts an integer UNIX timestamp value.');
		}
	}

	/**
	 * Set ETag HTTP Response Header
	 *
	 * Set the etag header and stop if the conditional GET request matches.
	 * The `value` argument is a unique identifier for the current resource.
	 * The `type` argument indicates whether the etag should be used as a strong or
	 * weak cache validator.
	 *
	 * When the current request includes an 'If-None-Match' header with
	 * a matching etag, execution is immediately stopped. If the request
	 * method is GET or HEAD, a '304 Not Modified' response is sent.
	 *
	 * @param   string					  $value  The etag value
	 * @param   string					  $type   The type of etag to create; either "strong" or "weak"
	 *
	 * @throws  InvalidArgumentException			If provided type is invalid
	 * @return  void
	 */
	public function etag($value, $type = 'strong')
	{

		//Ensure type is correct
		if (!in_array($type, array('strong', 'weak'))) {
			throw new InvalidArgumentException('Invalid Slim::etag type. Expected "strong" or "weak".');
		}

		//Set etag value
		$value = '"' . $value . '"';
		if ($type === 'weak') $value = 'W/' . $value;
		$this->response->header('ETag', $value);

		//Check conditional GET
		if ($etagsHeader = $this->request->headers('IF_NONE_MATCH')) {
			$etags = preg_split('@\s*,\s*@', $etagsHeader);
			if (in_array($value, $etags) || in_array('*', $etags)) $this->halt(304);
		}
	}

	/***** COOKIES *****/

	/**
	 * Set a normal, unencrypted Cookie
	 *
	 * @param   string  $name	   The cookie name
	 * @param   mixed   $value	  The cookie value
	 * @param   mixed   $time	   The duration of the cookie;
	 *							  If integer, should be UNIX timestamp;
	 *							  If string, converted to UNIX timestamp with `strtotime`;
	 * @param   string  $path	   The path on the server in which the cookie will be available on
	 * @param   string  $domain	 The domain that the cookie is available to
	 * @param   bool	$secure	 Indicates that the cookie should only be transmitted over a secure
	 *							  HTTPS connection to/from the client
	 * @param   bool	$httponly   When TRUE the cookie will be made accessible only through the HTTP protocol
	 *
	 * @return  void
	 */
	public function setCookie($name, $value, $time = null, $path = null, $domain = null, $secure = null, $httponly = null)
	{
		$time = is_null($time) ? $this->config('cookies.lifetime') : $time;
		$path = is_null($path) ? $this->config('cookies.path') : $path;
		$domain = is_null($domain) ? $this->config('cookies.domain') : $domain;
		$secure = is_null($secure) ? $this->config('cookies.secure') : $secure;
		$httponly = is_null($httponly) ? $this->config('cookies.httponly') : $httponly;
		$this->response->getCookieJar()->setClassicCookie($name, $value, $time, $path, $domain, $secure, $httponly);
	}

	/**
	 * Get the value of a Cookie from the current HTTP Request
	 *
	 * Return the value of a cookie from the current HTTP request,
	 * or return NULL if cookie does not exist. Cookies created during
	 * the current request will not be available until the next request.
	 *
	 * @param   string $name
	 *
	 * @return  string|null
	 */
	public function getCookie($name)
	{
		return $this->request->cookies($name);
	}

	/**
	 * Set an encrypted Cookie
	 *
	 * @param   string  $name	   The cookie name
	 * @param   mixed   $value	  The cookie value
	 * @param   mixed   $time	   The duration of the cookie;
	 *							  If integer, should be UNIX timestamp;
	 *							  If string, converted to UNIX timestamp with `strtotime`;
	 * @param   string  $path	   The path on the server in which the cookie will be available on
	 * @param   string  $domain	 The domain that the cookie is available to
	 * @param   bool	$secure	 Indicates that the cookie should only be transmitted over a secure
	 *							  HTTPS connection from the client
	 * @param   bool	$httponly   When TRUE the cookie will be made accessible only through the HTTP protocol
	 *
	 * @return  void
	 */
	public function setEncryptedCookie($name, $value, $time = null, $path = null, $domain = null, $secure = null, $httponly = null)
	{
		$time = is_null($time) ? $this->config('cookies.lifetime') : $time;
		$path = is_null($path) ? $this->config('cookies.path') : $path;
		$domain = is_null($domain) ? $this->config('cookies.domain') : $domain;
		$secure = is_null($secure) ? $this->config('cookies.secure') : $secure;
		$httponly = is_null($httponly) ? $this->config('cookies.httponly') : $httponly;
		$userId = $this->config('cookies.user_id');
		$this->response->getCookieJar()->setCookie($name, $value, $userId, $time, $path, $domain, $secure, $httponly);
	}

	/**
	 * Get the value of an encrypted Cookie from the current HTTP request
	 *
	 * Return the value of an encrypted cookie from the current HTTP request,
	 * or return NULL if cookie does not exist. Encrypted cookies created during
	 * the current request will not be available until the next request.
	 *
	 * @param   string $name
	 *
	 * @return  string|null
	 */
	public function getEncryptedCookie($name)
	{
		$value = $this->response->getCookieJar()->getCookieValue($name);
		return ($value === false) ? null : $value;
	}

	/**
	 * Delete a Cookie (for both normal or encrypted Cookies)
	 *
	 * Remove a Cookie from the client. This method will overwrite an existing Cookie
	 * with a new, empty, auto-expiring Cookie. This method's arguments must match
	 * the original Cookie's respective arguments for the original Cookie to be
	 * removed. If any of this method's arguments are omitted or set to NULL, the
	 * default Cookie setting values (set during Slim::init) will be used instead.
	 *
	 * @param   string  $name	   The cookie name
	 * @param   string  $path	   The path on the server in which the cookie will be available on
	 * @param   string  $domain	 The domain that the cookie is available to
	 * @param   bool	$secure	 Indicates that the cookie should only be transmitted over a secure
	 *							  HTTPS connection from the client
	 * @param   bool	$httponly   When TRUE the cookie will be made accessible only through the HTTP protocol
	 *
	 * @return  void
	 */
	public function deleteCookie($name, $path = null, $domain = null, $secure = null, $httponly = null)
	{
		$path = is_null($path) ? $this->config('cookies.path') : $path;
		$domain = is_null($domain) ? $this->config('cookies.domain') : $domain;
		$secure = is_null($secure) ? $this->config('cookies.secure') : $secure;
		$httponly = is_null($httponly) ? $this->config('cookies.httponly') : $httponly;
		$this->response->getCookieJar()->deleteCookie($name, $path, $domain, $secure, $httponly);
	}

	/***** HELPERS *****/

	/**
	 * Get the Slim application's absolute directory path
	 *
	 * This method returns the absolute path to the Slim application's
	 * directory. If the Slim application is installed in a public-accessible
	 * sub-directory, the sub-directory path will be included. This method
	 * will always return an absolute path WITH a trailing slash.
	 *
	 * @return string
	 */
	public function root()
	{
		return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . rtrim($this->request->getRootUri(), '/') . '/';
	}

	/**
	 * Stop
	 *
	 * Send the current Response as is and stop executing the Slim
	 * application. The thrown exception will be caught by the Slim
	 * custom exception handler which exits this script.
	 *
	 * @throws  Stop
	 * @return  void
	 */
	public function stop()
	{
		$flash = $this->view->getData('flash');
		/** @var $flash Flash */
		if ($flash) {
			$flash->save();
		}
		session_write_close();
		$this->response->send();
		throw new Stop();
	}

	/**
	 * Halt
	 *
	 * Halt the application and immediately send an HTTP response with a
	 * specific status code and body. This may be used to send any type of
	 * response: info, success, redirect, client error, or server error.
	 * If you need to render a template AND customize the response status,
	 * you should use Slim::render() instead.
	 *
	 * @param   int				 $status	 The HTTP response status
	 * @param   string			  $message	The HTTP response body
	 *
	 * @return  void
	 */
	public function halt($status, $message = '')
	{
		if (ob_get_level() !== 0) {
			ob_clean();
		}
		$this->response->status($status);
		$this->response->body($message);
		$this->stop();
	}

	/**
	 * Pass
	 *
	 * This method will cause the Router::dispatch method to ignore
	 * the current route and continue to the next matching route in the
	 * dispatch loop. If no subsequent mathing routes are found,
	 * a 404 Not Found response will be sent to the client.
	 *
	 * @throws  Pass
	 * @return  void
	 */
	public function pass()
	{
		if (ob_get_level() !== 0) {
			ob_clean();
		}
		throw new Pass();
	}

	/**
	 * Set the HTTP response Content-Type
	 *
	 * @param   string $type The Content-Type for the Response (ie. text/html)
	 *
	 * @return  void
	 */
	public function contentType($type)
	{
		$this->response->header('Content-Type', $type);
	}

	/**
	 * Set the HTTP response status code
	 *
	 * @param $code
	 *
	 * @internal param int $status The HTTP response status code
	 * @return  void
	 */
	public function status($code)
	{
		$this->response->status($code);
	}

	/**
	 * Get the URL for a named Route
	 *
	 * @param   string		  $name	   The route name
	 * @param   array		   $params	 Key-value array of URL parameters
	 *
	 * @throws  RuntimeException			If named route does not exist
	 * @return  string
	 */
	public function urlFor($name, $params = array())
	{
		return $this->router->urlFor($name, $params);
	}

	/**
	 * Redirect
	 *
	 * This method immediately redirects to a new URL. By default,
	 * this issues a 302 Found response; this is considered the default
	 * generic redirect response. You may also specify another valid
	 * 3xx status code if you want. This method will automatically set the
	 * HTTP Location header for you using the URL parameter and place the
	 * destination URL into the response body.
	 *
	 * @param   string					  $url		The destination URL
	 * @param   int						 $status	 The HTTP redirect status code (Optional)
	 *
	 * @throws  InvalidArgumentException				If status parameter is not a valid 3xx status code
	 * @return  void
	 */
	public function redirect($url, $status = 302)
	{
		if ($status >= 300 && $status <= 307) {
			$this->response->header('Location', (string)$url);
			$this->halt($status, (string)$url);
		} else {
			throw new InvalidArgumentException('Slim::redirect only accepts HTTP 300-307 status codes.');
		}
	}

	/***** FLASH *****/

	/**
	 * Set flash message for subsequent request
	 *
	 * @param   string	$key
	 * @param   mixed	 $value
	 *
	 * @return  void
	 */
	public function flash($key, $value)
	{
		$flash = $this->view->getData('flash');
		/** @var $flash Flash */
		$flash->set($key, $value);
	}

	/**
	 * Set flash message for current request
	 *
	 * @param   string	$key
	 * @param   mixed	 $value
	 *
	 * @return  void
	 */
	public function flashNow($key, $value)
	{
		$flash = $this->view->getData('flash');
		/** @var $flash Flash */
		$flash->now($key, $value);
	}

	/**
	 * Keep flash messages from previous request for subsequent request
	 * @return void
	 */
	public function flashKeep()
	{
		$flash = $this->view->getData('flash');
		/** @var $flash Flash */
		$flash->keep();
	}

	/***** HOOKS *****/

	/**
	 * Assign hook
	 *
	 * @param   string  $name	   The hook name
	 * @param   mixed   $callable   A callable object
	 * @param   int	 $priority   The hook priority; 0 = high, 10 = low
	 *
	 * @return  void
	 */
	public function hook($name, $callable, $priority = 10)
	{
		if (!isset($this->hooks[$name])) {
			$this->hooks[$name] = array(array());
		}
		if (is_callable($callable)) {
			$this->hooks[$name][(int)$priority][] = $callable;
		}
	}

	/**
	 * Invoke hook
	 *
	 * @param   string  $name	   The hook name
	 * @param null $hookArg
	 *
	 * @internal param mixed $hookArgs (Optional) Argument for hooked functions
	 * @return  mixed
	 */
	public function applyHook($name, $hookArg = null)
	{
		if (!isset($this->hooks[$name])) {
			$this->hooks[$name] = array(array());
		}
		if (!empty($this->hooks[$name])) {
			// Sort by priority, low to high, if there's more than one priority
			if (count($this->hooks[$name]) > 1) {
				ksort($this->hooks[$name]);
			}
			foreach ($this->hooks[$name] as $priority) {
				if (!empty($priority)) {
					foreach ($priority as $callable) {
						$hookArg = call_user_func($callable, $hookArg);
					}
				}
			}
			return $hookArg;
		}
		return '';
	}

	/**
	 * Get hook listeners
	 *
	 * Return an array of registered hooks. If `$name` is a valid
	 * hook name, only the listeners attached to that hook are returned.
	 * Else, all listeners are returned as an associative array whose
	 * keys are hook names and whose values are arrays of listeners.
	 *
	 * @param   string	  $name A hook name (Optional)
	 *
	 * @return  array|null
	 */
	public function getHooks($name = null)
	{
		if (!is_null($name)) {
			return isset($this->hooks[(string)$name]) ? $this->hooks[(string)$name] : null;
		} else {
			return $this->hooks;
		}
	}

	/**
	 * Clear hook listeners
	 *
	 * Clear all listeners for all hooks. If `$name` is
	 * a valid hook name, only the listeners attached
	 * to that hook will be cleared.
	 *
	 * @param   string  $name   A hook name (Optional)
	 *
	 * @return  void
	 */
	public function clearHooks($name = null)
	{
		if (!is_null($name) && isset($this->hooks[(string)$name])) {
			$this->hooks[(string)$name] = array(array());
		} else {
			foreach ($this->hooks as $key => $value) {
				$this->hooks[$key] = array(array());
			}
		}
	}

	/***** RUN SLIM *****/

	/**
	 * Run the Slim application
	 *
	 * This method is the "meat and potatoes" of Slim and should be the last
	 * method called. This fires up Slim, invokes the Route that matches
	 * the current request, and returns the response to the client.
	 *
	 * This method will invoke the Not Found handler if no matching
	 * routes are found.
	 *
	 * This method will also catch any unexpected Exceptions thrown by this
	 * application; the Exceptions will be logged to this application's log
	 * and rethrown to the global Exception handler.
	 *
	 * @return void
	 */
	public function run()
	{
		try {
			try {
				$this->applyHook('slim.before');
				ob_start();
				$this->applyHook('slim.before.router');
				$dispatched = false;
				$httpMethod = $this->request()->getMethod();
				$httpMethodsAllowed = array();
				foreach ($this->router as $route) {
					/** @var $route Route */
					if ($route->supportsHttpMethod($httpMethod)) {
						try {
							$this->applyHook('slim.before.dispatch');
							$dispatched = $route->dispatch();
							$this->applyHook('slim.after.dispatch');
							if ($dispatched) {
								break;
							}
						} catch (Pass $e) {
							continue;
						}
					} else {
						$httpMethodsAllowed = array_merge($httpMethodsAllowed, $route->getHttpMethods());
					}
				}
				if (!$dispatched) {
					if ($httpMethodsAllowed) {
						$this->response()->header('Allow', implode(' ', $httpMethodsAllowed));
						$this->halt(405);
					} else {
						$this->notFound();
					}
				}
				$this->response()->write(ob_get_clean());
				$this->applyHook('slim.after.router');
				$flash = $this->view->getData('flash');
				/** @var $flash Flash */
				$flash->save();
				session_write_close();
				$this->response->send();
				$this->applyHook('slim.after');
			} catch (RequestSlash $e) {
				$this->redirect($this->request->getRootUri() . $this->request->getResourceUri() . '/', 301);
			} catch (Exception $e) {
				if ($e instanceof Stop) throw $e;
				$this->getLog()->error($e);
				if ($this->config('debug') === true) {
					$this->halt(500, self::generateErrorMarkup($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
				} else {
					$this->error($e);
				}
			}
		} catch (Stop $e) {
			//Exit application context
		}
	}

	/***** EXCEPTION AND ERROR HANDLING *****/

	/**
	 * Handle errors
	 *
	 * This is the global Error handler that will catch reportable Errors
	 * and convert them into ErrorExceptions that are caught and handled
	 * by each Slim application.
	 *
	 * @param   int	 $errno	  The numeric type of the Error
	 * @param   string  $errstr	 The error message
	 * @param   string  $errfile	The absolute path to the affected file
	 * @param int|string $errline The line number of the error in the affected file
	 *
	 * @return  bool
	 * @throws  ErrorException
	 */
	public static function handleErrors($errno, $errstr = '', $errfile = '', $errline = '')
	{
		if (error_reporting() & $errno) {
			throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
		}
		return true;
	}

	/**
	 * Generate markup for error message
	 *
	 * This method accepts details about an error or exception and
	 * generates HTML markup for the 500 response body that will
	 * be sent to the client.
	 *
	 * @param   string  $message	The error message
	 * @param   string  $file	   The absolute file path to the affected file
	 * @param int|string $line The line number in the affected file
	 * @param   string  $trace	  A stack trace of the error
	 *
	 * @return  string
	 */
	protected static function generateErrorMarkup($message, $file = '', $line = '', $trace = '')
	{
		$body = '<p>The application could not run because of the following error:</p>';
		$body .= "<h2>Details:</h2><strong>Message:</strong> $message<br/>";
		if ($file !== '') $body .= "<strong>File:</strong> $file<br/>";
		if ($line !== '') $body .= "<strong>Line:</strong> $line<br/>";
		if ($trace !== '') $body .= '<h2>Stack Trace:</h2>' . nl2br($trace);
		return self::generateTemplateMarkup('Slim Application Error', $body);
	}

	/**
	 * Generate default template markup
	 *
	 * This method accepts a title and body content to generate
	 * an HTML page. This is primarily used to generate the layout markup
	 * for Error handlers and Not Found handlers.
	 *
	 * @param   string  $title The title of the HTML template
	 * @param   string  $body The body content of the HTML template
	 *
	 * @return  string
	 */
	protected static function generateTemplateMarkup($title, $body)
	{
		$html = "<html><head><title>$title</title><style type='text/css'>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>";
		$html .= "<h1>$title</h1>";
		$html .= $body;
		$html .= '</body></html>';
		return $html;
	}

	/**
	 * Default Not Found handler
	 * @return void
	 */
	protected function defaultNotFound()
	{
		echo self::generateTemplateMarkup('404 Page Not Found', '<p>The page you are looking for could not be found. Check the address bar to ensure your URL is spelled correctly. If all else fails, you can visit our home page at the link below.</p><a href="' . $this->request->getRootUri() . '">Visit the Home Page</a>');
	}

	/**
	 * Default Error handler
	 * @return void
	 */
	protected function defaultError()
	{
		echo self::generateTemplateMarkup('Error', '<p>A website error has occured. The website administrator has been notified of the issue. Sorry for the temporary inconvenience.</p>');
	}
}