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

//Set which errors are reported by PHP and logged by Slim
error_reporting(E_ALL | E_STRICT);

//This will handle errors
set_error_handler(array('Slim', 'handleErrors'));

//This will handle uncaught exceptions
set_exception_handler(array('Slim', 'handleExceptions'));

//Slim will auto-load class files in the same directory as Slim.php
spl_autoload_register(array('Slim', 'autoload'));

class Slim {

	//Constants helpful when triggering errors or calling Slim::log()
	const ERROR = 256;
	const WARNING = 512;
	const NOTICE = 1024;

	/**
	 * @var Slim The actual Slim instance
	 */
	protected static $app;

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var Response
	 */
	private $response;

	/**
	 * @var Router
	 */
	private $router;

	/**
	 * @var View
	 */
	private $view;

	/**
	 * @var array Before callback functions
	 */
	private $before;

	/**
	 * @var array After callback functions
	 */
	private $after;

	/**
	 * @var array Application settings
	 */
	private $settings;

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->request = new Request();
		$this->response = new Response();
		$this->router = new Router( $this->request );
		$this->before = array();
		$this->after = array();
		$this->settings = array(
			'log' => true,
			'log_dir' => './logs',
			'debug' => true,
			'templates_dir' => './templates'
		);
	}

	/**
	 * Slim auto-loader
	 */
	public static function autoload($className) {
		if( file_exists($file = dirname(__FILE__).'/'.$className.'.php')) {
	        require_once($file);
	    }
	}

	/**
	 * Handle user errors
	 *
	 * This is the global Error handler that will catch an uncaught Error
	 * and display a nice-looking error page with details about the Error.
	 *
	 * @param   int     $errno      The numeric type of the Error
	 * @param   string  $errstr     The error message
	 * @param   string  $errfile    The absolute path to the affected file
	 * @param   int     $errline    The line number of the error in the affected file
	 * @return  void
	 */
	public static function handleErrors($errno, $errstr = '', $errfile = '', $errline = '') {
		if( !(error_reporting() & $errno) ) {
			return; // This error code is not included in error_reporting
		}
		if( ob_get_level() !== 0 ) ob_clean();
		Slim::log(sprintf("Message: %s | File: %s | Line: %d", $errstr, $errfile, $errline), $errno);
		if( self::config('debug') === true ) {
			$r = new Response();
			$r->status(500);
			$r->body(self::generateErrorMarkup($errstr, $errfile, $errline));
			$r->send();
		} else {
			self::error();
		}
		die();
	}

	/**
	 * Handle user exceptions
	 *
	 * This is the global Exception handler that will catch an uncaught Exception
	 * and display a nice-looking error page with details about the Exception.
	 *
	 * @param   Exception $e
	 * @return  void
	 */
	public static function handleExceptions( Exception $e ) {
		if( ob_get_level() !== 0 ) ob_clean();
		Slim::log(sprintf("Message: %s | File: %s | Line: %d", $e->getMessage(), $e->getFile(), $e->getLine()));
		if( self::config('debug') === true ) {
			$r = new Response();
			$r->status(500);
			$r->body(self::generateErrorMarkup($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
			$r->send();
		} else {
			self::error();
		}
		die();
	}

	/**
	 * Generate markup for error message
	 *
	 * This method accepts details about an error or exception and
	 * generates HTML markup for the 500 response body that will
	 * be sent to the client.
	 *
	 * @param   string  $message    The error message
	 * @param   string  $file       The absolute file path to the affected file
	 * @param   int     $line       The line number in the affected file
	 * @param   string  $trace      A stack trace of the error
	 * @return  string
	 */
	private static function generateErrorMarkup($message, $file = '', $line = '', $trace = ''){
		$body = '<p>The application could not run because of the following error:</p>';
		$body .= "<h2>Details:</h2><strong>Message:</strong> $message<br/>";
		if( $file !== '' ) $body .= "<strong>File:</strong> $file<br/>";
		if( $line !== '' ) $body .= "<strong>Line:</strong> $line<br/>";
		if( $trace !== '' ) $body .= '<h2>Stack Trace:</h2>' . nl2br($trace);
		return self::generateTemplateMarkup('Slim Application Error', $body);
	}
	
	/**
	 * Generate default template markup
	 *
	 * This method accepts a title and body content to generate
	 * an HTML page. This is primarily used to generate the markup
	 * for Error handlers and Not Found handlers.
	 *
	 * @param	string	$title The title of the HTML template
	 * @param	string	$body The body content of the HTML template
	 * @return 	string
	 */
	private static function generateTemplateMarkup($title, $body){
		$html = "<html><head><title>$title</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>";
		$html .= "<h1>$title</h1>";
		$html .= $body;
		$html .= '</body></html>';
		return $html;
	}


	/***** INITIALIZER *****/

	/**
	 * Initialize Slim
	 *
	 * This instantiates the Slim application, sets a default NotFound
	 * handler, and sets the View class used to render templates. If the
	 * view class parameter is null, a default View will be created.
	 *
	 * @param   string  $viewClass  The name of the view class Slim will use
	 * @return  void
	 */
	public static function init($viewClass = null) {
		self::$app = new Slim();
		self::notFound(array('Slim', 'defaultNotFound'));
		self::error(array('Slim', 'defaultError'));
		$view = is_null($viewClass) ? 'View' : $viewClass;
		self::view($view);
	}

	/***** CONFIGURATION *****/

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
	 * If two arguments are provided, the first argument is the setting name
	 * and the second the setting value.
	 *
	 * @param mixed $name If a string, the name of the setting to set or retrieve. Else an associated array of setting names and values
	 * @param mixed $value If name is a string, the value of the setting identified by $name
	 * @return mixed The value of a setting if only one argument and argument is a string
	 */
	public static function config($name, $value = null) {
		if( func_num_args() === 1 ) {
			if( is_array($name) ) {
				self::$app->settings = array_merge(self::$app->settings, $name);
			} else {
				return in_array($name, array_keys(self::$app->settings)) ? self::$app->settings[$name] : null;
			}
		} else {
			self::$app->settings[$name] = $value;
		}
	}

	/***** ROUTING *****/

	/**
	 * Add GET route
	 *
	 * Adds a new GET route to the router with associated callback. This
	 * route may only be matched with a HTTP GET request.
	 *
	 * @param   string  $pattern    The URL pattern, ie. "/books/:id/edit"
	 * @param   mixed   $callable   Anything that returns true for is_callable()
	 * @return  Route
	 */
	public static function get($pattern, $callable) {
		return self::router()->map($pattern, $callable, Request::METHOD_GET);
	}

	/**
	 * Add POST route
	 *
	 * Adds a new POST route to the router with associated callback. This
	 * route may only be matched with a HTTP POST request.
	 *
	 * @param   string  $pattern    The URL pattern, ie. "/books/:id/edit"
	 * @param   mixed   $callable   Anything that returns true for is_callable()
	 * @return  Route
	 */
	public static function post($pattern, $callable) {
		return self::router()->map($pattern, $callable, Request::METHOD_POST);
	}

	/**
	 * Add PUT route
	 *
	 * Adds a new PUT route to the router with associated callback. This
	 * route may only be matched with a HTTP PUT request.
	 *
	 * @param   string  $pattern    The URL pattern, ie. "/books/:id/edit"
	 * @param   mixed   $callable   Anything that returns true for is_callable()
	 * @return  Route
	 */
	public static function put($pattern, $callable) {
		return self::router()->map($pattern, $callable, Request::METHOD_PUT);
	}

	/**
	 * Add DELETE route
	 *
	 * Adds a new DELETE route to the router with associated callback. This
	 * route may only be matched with a HTTP DELETE request.
	 *
	 * @param   string  $pattern    The URL pattern, ie. "/books/:id/edit"
	 * @param   mixed   $callable   Anything that returns true for is_callable()
	 * @return  Route
	 */
	public static function delete($pattern, $callable) {
		return self::router()->map($pattern, $callable, Request::METHOD_DELETE);
	}

	/**
	 * Specify or call NotFound Handler
	 *
	 * This method specifies or calls the application-wide NotFound
	 * handler. There are two contexts in which this method may be invoked:
	 *
	 * 1. When specifying the callback method:
	 *
	 * If the $callable parameter is not null and is callable, then this
	 * method will register the callback method to be called when no
	 * routes match the current HTTP request. It will not actually invoke
	 * the NotFound callback, though.
	 *
	 * 2. When invoking the NotFound callback method:
	 *
	 * If the $callable parameter is null, then Slim assumes you want
	 * to invoke an already-registered callback method. If the callback
	 * method has been registered and is callable, then it is invoked
	 * and sends a 404 HTTP Response whose body is the output of
	 * the NotFound handler.
	 *
	 * @param mixed $callable Anything that returns true for is_callable()
	 */
	public static function notFound($callable = null) {
		if( !is_null($callable) ) {
			self::router()->notFound($callable);
		} else {
			ob_start();
			call_user_func(self::router()->notFound());
			Slim::raise(404, ob_get_clean());
		}
	}

	/**
	 * Specify or call Error Handler
	 *
	 * This method specifies or calls the application-wide Error
	 * handler. There are two contexts in which this method may be invoked:
	 *
	 * 1. When specifying the Error callback method:
	 *
	 * If the $callable parameter is not null and is callable, then this
	 * method will register the callback method to be called when there
	 * is an uncaught application Exception or Error. It will not actually invoke
	 * the Error callback, though.
	 *
	 * 2. When invoking the Error callback method:
	 *
	 * If the $callable parameter is null, then Slim assumes you want
	 * to invoke an already-registered callback method. If the callback
	 * method has been registered and is callable, then it is invoked
	 * and sends a 500 HTTP Response whose body is the output of
	 * the Error handler. This method will ONLY be invoked when
	 * $config['show_errors'] is FALSE.
	 *
	 * @param mixed $callable Anything that returns true for is_callable()
	 */
	public static function error($callable = null) {
		if( !is_null($callable) ) {
			self::router()->error($callable);
		} else {
			ob_start();
			call_user_func(self::router()->error());
			Slim::raise(500, ob_get_clean());
		}
	}

	/***** LOGGING *****/

	/**
	 * Logger
	 *
	 * This is an application-wide Logger which will write messages to
	 * a log file in the log directory (as defined in the app configuration).
	 * Logging must be enabled for this method to work. A separate log file 
	 * is created for each day. Each log file is prepended with the type of
	 * message (ie. Error, Warning, or Notice).
	 *
	 * @param string $message The message to send to the Logger
	 */
	public static function log($message, $errno = null) {
		if( self::config('log') ) {
			$type = '';
			switch ($errno) {
			    case E_USER_ERROR :
				case E_ERROR :
					$type = '[ERROR]';
					break;
				case E_WARNING :
				case E_USER_WARNING :
					$type = '[WARNING]';
					break;
				case E_NOTICE :
				case E_USER_NOTICE : 
					$type = '[NOTICE]';
					break;
				default :
					$type = '[UNKNOWN]';
					break;
			}
			error_log(sprintf("%s %s %s\r\n", $type, date('c'), $message), 3, rtrim(self::config('log_dir'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log');
		}
	}

	/***** CALLBACKS *****/

	/**
	 * Add BEFORE callback
	 *
	 * This queues a callable object to be called before the Slim app
	 * is run. Use this to manipulate the request, session, etc. Queued
	 * callbacks are called in the order they are added.
	 *
	 * @param mixed $callable Anything that returns true for is_callable()
	 */
	public static function before($callable) {
		self::$app->before[] = $callable;
	}

	/**
	 * Add AFTER callback
	 *
	 * This queues a callable object to be called after the Slim app
	 * is run. Use this to manipulate the response, session, etc. Queued
	 * callbacks are called in the order they are added.
	 *
	 * @param mixed $callable Anything that returns true for is_callable()
	 */
	public static function after($callable) {
		self::$app->after[] = $callable;
	}

	/**
	 * Run callbacks
	 *
	 * This calls each callable object in the $callables array. This
	 * is used internally to run the Slim app's BEFORE and AFTER callbacks.
	 * 
	 * @param array $callables An array of callable objects
	 */
	private static function runCallables($callables) {
		foreach( $callables as $callable ) {
			if( is_callable($callable) ) {
				call_user_func($callable);
			}
		}
	}

	/***** ACCESSORS *****/

	/**
	 * Get the Request object
	 *
	 * @return Request
	 */
	public static function request() {
		return self::$app->request;
	}

	/**
	 * Get the Response object
	 *
	 * @return Response
	 */
	public static function response() {
		return self::$app->response;
	}

	/**
	 * Get the application Router
	 *
	 * @return Router
	 */
	public static function router() {
		return self::$app->router;
	}

	/**
	 * Get and/or set the View
	 *
	 * This method will instantiate a new View if the $viewClass
	 * parameter is not null. If a View already exists and this
	 * method is called to create a new View, data already set
	 * in the existing View will be transferred to the new View.
	 *
	 * @param   string $viewClass The name of the View class
	 * @return  View
	 */
	public static function view( $viewClass = null ) {
		if( !is_null($viewClass) ) {
			$existingData = is_null(self::$app->view) ? array() : self::$app->view->data();
			self::$app->view = new $viewClass();
			self::$app->view->data($existingData);
		}
		return self::$app->view;
	}

	/***** RENDERING *****/

	/**
	 * Render a template
	 *
	 * Call this method within a GET, POST, PUT, DELETE, or NOT FOUND
	 * callback to render a template, whose output is appended to the
	 * current HTTP response body. How the template is rendered is
	 * delegated to the current View.
	 *
	 * @param string 	$template 	The name of the template passed into the View::render method
	 * @param array 	$data 		Associative array of data passed for the View
	 * @param int 		$status 	The HTTP response status code to use (Optional)
	 */
	public static function render( $template, $data = array(), $status = null ) {
		self::view()->templatesDirectory(self::config('templates_dir'));
		if( !is_null($status) ) {
			self::response()->status($status);
		}
		self::view()->data($data);
		self::view()->render($template);
	}

	/***** HTTP CACHING *****/

	/**
	 * Set the last modified time of the resource
	 *
	 * Set the HTTP 'Last-Modified' header and stop if a conditional
	 * GET request's `If-Modified-Since` header matches the last modified time
	 * of the resource. The `time` argument is a UNIX timestamp integer value. 
	 * When the current request includes an 'If-Modified-Since' header that 
	 * matches the specified last modified time, the application will stop 
	 * and send a '304 Not Modified' response.
	 *
	 * @param 	int 						$time 	The last modified UNIX timestamp
	 * @throws 	SlimException 						Returns HTTP 304 Not Modified response if resource last modified time matches `If-Modified-Since` header
	 * @throws 	InvalidArgumentException 			If provided timestamp is not an integer
	 */
	public static function lastModified($time) {
		if( is_integer($time) ) {
			Slim::response()->header('Last-Modified', date(DATE_RFC1123, $time));
			if( $time === strtotime(Slim::request()->header('IF_MODIFIED_SINCE'))) Slim::raise(304);
		} else {
			throw new InvalidArgumentException("Slim::lastModified only accepts an integer UNIX timestamp value.");
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
	 * @param 	string 						$value 	The etag value
	 * @param 	string 						$type 	The type of etag to create; either "strong" or "weak"
	 * @throws 	InvalidArgumentException 			If provided type is invalid
	 */
	public static function etag($value, $type = 'strong'){

		//Ensure type is correct
		if( !in_array($type, array('strong', 'weak'))) {
			throw new InvalidArgumentException('Invalid Slim::etag type. Expected "strong" or "weak".');
		}

		//Set etag value
		$value = '"' . $value . '"';
		if( $type === 'weak' ) $value = 'W/'.$value;
		Slim::response()->header('ETag', $value);

		//Check conditional GET
		if( $etagsHeader = Slim::request()->header('IF_NONE_MATCH')) {
			$etags = preg_split('@\s*,\s*@', $etagsHeader);
			if( in_array($value, $etags) || in_array('*', $etags) ) Slim::raise(304);
		}

	}

	/***** SESSIONS (COOKIE-BASED) *****/

	/**
	 * Set session variable
	 *
	 * This method will store a persistent session variable that will be accessible
	 * across multiple HTTP requests until the variable expires or is cleared. This
	 * implementation uses browser cookies; because Slim sessions are implemented on 
	 * top of browser cookies, each session variable's value may not be larger then 
	 * 4KB in size. Soon I will add support for encrypted session variables.
	 *
	 * This method acts as both a getter and setter. If you invoke this method
	 * and specify only the first parameter, this will return the value of the cookie
	 * with the given name, or NULL if said cookie does not exist. If you invoke
	 * this method with two or more parameters, this method will create a new Cookie
	 * with the given name, value, and other options. If a Cookie with the same name
	 * already exists, that cookie will be overwritten when the Response is sent
	 * to the client.
	 *
	 * To "delete" a Session variable, create a new Session variable with the same
	 * name and set its value to FALSE, NULL, or an empty string.
	 *
	 * USAGE:
	 *
	 * //Get cookie value
	 * $value = Slim::session('name');
	 *
	 * //Set cookie value
	 * Slim::session('name', 'value'[, ... ]);
	 *
	 * @param   string  $name       The session variable name
	 * @param   string  $value      The session variable value
	 * @param   int     $expires    The time this session variable expires (UNIX timestamp)
	 * @param   string  $path       The path on the server in which this variable will be available
	 * @param   string  $domain     The domain on which this variable will be available
	 * @param   bool    $secure     When TRUE the variable will be sent over HTTPS only
	 * @param   bool    $httponly   When TRUE the variable will be made accessible only through the HTTP protocol
	 * @return  mixed
	 */
	public static function session($name, $value = null, $expires = 0, $path = null, $domain = null, $secure = false, $httponly = false) {
		if( func_num_args() === 1 ) {
			return self::request()->cookie($name);
		} else {
			self::response()->addCookie(new Cookie($name, $value, $expires, $path, $domain, $secure, $httponly));
			//TODO: Encrypt all Slim::session variables with application salt
		}
	}

	/***** HELPERS *****/

	/**
	 * Root directory
	 */
	public static function root() {
		$url = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		return ( self::request()->root !== DIRECTORY_SEPARATOR ) ? $url . self::request()->root : $url;
	}

	/**
	 * Stop!
	 *
	 * This stops the Slim app in its tracks and sends the response as is 
	 * to the client.
	 */
	public static function stop() {
		self::response()->send();
		exit;
	}

	/**
	 * Raise Slim Exception
	 *
	 * Trigger an immediate HTTP response with a specific status code and body. 
	 * This may be used to send any type of response: info, success, redirect, 
	 * client error, or server error. If you need to render a template AND
	 * customize the response status, you should use Slim::render() instead.
	 *
	 * @param   int             $status     The HTTP response status
	 * @param   string          $message    The HTTP response body
	 * @throws  SlimException
	 */
	public static function raise( $status, $message = '' ) {
		throw new SlimException($message, $status);
	}

	/**
	 * Pass
	 *
	 * This method will cause the Router::dispatch method to ignore
	 * this route and continue to the next matching route in the dispatch
	 * loop. If no subsequent mathing routes are found, a 404 response
	 * will be sent to the client.
	 */
	public static function pass() {
		if( ob_get_level() !== 0 ) ob_clean();
		throw new PassException();
	}

	/**
	 * Set Content-Type
	 *
	 * @param string $type The Content-Type for the Response (ie text/html, application/json, etc)
	 */
	public static function contentType($type) {
		self::response()->header('Content-Type', $type);
	}

	/**
	 * Set Response status
	 *
	 * @param int $status The HTTP response status code
	 */
	public static function status($code) {
		self::response()->status($code);
	}

	/**
	 * Get URL for Route
	 *
	 * @param 	string 			$name 		The route name
	 * @param 	array 			$params		Associative array of URL parameter values; [ name => value, ... ]
	 * @throws 	SlimException 				If named route does not exist
	 * @return 	string
	 */
	public static function urlFor($name, $params = array()) {
		return self::router()->urlFor($name, $params);
	}

	/**
	 * Redirect
	 *
	 * This method immediately redirects the client to a new URL. By default,
	 * this issues a 307 Temporary Redirect. You may also specify a
	 * 301 Permanent Redirect if you want. This method will automatically
	 * set the HTTP Location header for you using the URL parameter.
	 *
	 * @param   string                      $url        The destination URL
	 * @param   int                         $status     The HTTP redirect status code (Optional)
	 * @throws  InvalidArgumentException                If status parameter is not 301 or 307
	 */
	public static function redirect($url, $status = 307) {
		if( $status === 301 || $status === 307 ) {
			self::response()->status($status);
			self::response()->header('Location', (string)$url);
			self::response()->send();
		} else {
			throw new InvalidArgumentException("Slim::redirect only accepts HTTP 301 and HTTP 307 status codes.");
		}
	}

	/**
	 * Default NOT FOUND handler
	 *
	 * Default callback that will be called when a route cannot be
	 * matched to the current HTTP request.
	 */
	public static function defaultNotFound() {
		echo self::generateTemplateMarkup('404 Page Not Found', '<p>The page you are looking for could not be found. Check the address bar to ensure your URL is spelled correctly. If all else fails, you can visit our home page at the link below.</p><a href="'.Slim::root().'">Visit the Home Page</a>');
	}

	/**
	 * Default custom error handler
	 *
	 * This is the default error handler invoked when the `show_errors` setting
	 * is set to FALSE.
	 */
	public static function defaultError() {
		echo self::generateTemplateMarkup('Error', '<p>A website error has occured. The website administrator has been notified of the issue. Sorry for the temporary inconvenience.</p>');
	}

	/***** RUN SLIM *****/

	/**
	 * Run the Slim app
	 *
	 * This method is the "meat and potatoes" of Slim and should be the last
	 * method you call. This fires up Slim, routes the request to the
	 * appropriate callback, then returns a response to the client.
	 *
	 * This method will also invoke the NotFound handler if no matching
	 * routes are found. This method will also catch any Exceptions
	 * thrown by Slim::raise or by application errors.
	 */
	public static function run() {
		try {
			self::runCallables(self::$app->before);
			ob_start();
			if( !self::router()->dispatch() ) { Slim::notFound(); }
			self::response()->write(ob_get_clean());
			self::runCallables(self::$app->after);
			self::response()->send();
		} catch( SlimException $e ) {
			if( ob_get_level() !== 0 ) ob_clean();
			self::response()->status($e->getCode());
			self::response()->body($e->getMessage());
			self::response()->send();
		}
	}

}

?>
