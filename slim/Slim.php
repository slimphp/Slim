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

spl_autoload_register(array('Slim', 'autoload'));
set_error_handler(array('Slim', 'handleErrors'));
set_exception_handler(array('Slim', 'handleExceptions'));

class Slim {
	
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
		$this->settings = array();
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
		//Log error here with error_log() if in DEVELOPMENT mode and logging turned on
		ob_clean();
		$r = new Response();
		$r->status(500);
		$r->body(self::generateErrorMarkup($errstr, $errfile, $errline));
		$r->send();
		exit;
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
		//Log error here with error_log() if in DEVELOPMENT mode and logging turned on
		ob_clean();
		$r = new Response();
		$r->status(500);
		$r->body(self::generateErrorMarkup($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
		$r->send();
		exit;
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
	public static function generateErrorMarkup($message, $file = '', $line = '', $trace = ''){
		$html = "<html><head><title>Slim Application Error</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>";
		$html .= "<h1>Slim Application Error</h1>";
		$html .= "<p>The application could not run because of the following error:</p>";
		$html .= "<h2>Details:</h2><strong>Message:</strong> $message<br/>";
		if( $file !== '' ) $html .= "<strong>File:</strong> $file<br/>";
		if( $line !== '' ) $html .= "<strong>Line:</strong> $line<br/>";
		if( $trace !== '' ) $html .= "<h2>Stack Trace:</h2>" . nl2br($trace);
		$html .= "</body></html>";
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
		$view = is_null($viewClass) ? 'View' : $viewClass;
		self::view($view);
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
			self::response()->body(ob_get_clean());
			self::response()->status(404);
			self::response()->send();
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
		//TODO: Abstract setting the templates directory into Slim::set('templates', '/path') in Phase 3
		self::view()->templatesDirectory(Slim::root() . 'templates');
		if( !is_null($status) ) {
			self::response()->status($status);
		}
		self::view()->data($data);
		self::view()->render($template);
	}
	
	/***** HELPERS *****/
	
	/**
	 * Root directory
	 */
	public static function root() {
		return $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . self::request()->root;
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
		ob_clean();
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
		echo "We couldn't find what you are looking for. There's a slim chance you typed in the wrong URL.";
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
		} catch( Exception $e ) {
			ob_clean();
			if( $e instanceof SlimException ) {
				$status = $e->getCode();
				$body = $e->getMessage();
			} else {
				$status = 500;
				$body = self::generateErrorMarkup($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
			}
			self::response()->status($status);
			self::response()->body($body);
			self::response()->send();
		}
	}
	
}

?>