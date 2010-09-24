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

set_include_path(realpath(dirname(__FILE__)) . PATH_SEPARATOR . get_include_path());

require('Request.php');
require('Response.php');
require('Router.php');
require('Route.php');
require('View.php');

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
	
	/***** INITIALIZERS (Choose One) *****/
	
	/**
	 * Initialize Slim
	 *
	 * This instantiates the actual Slim app and sets a default
	 * 404 Not Found handler.
	 */
	public static function init() {
		self::$app = new Slim();
		self::notFound(array('Slim', 'defaultNotFound'));
	}
	
	/**
	 * Initialize Slim with View
	 *
	 * Along with initializing Slim (see above), this also sets the View
	 * class used to render templates.
	 *
	 * @param string $viewClass The name of the view class Slim will use
	 */
	public static function initWithView($viewClass) {
		self::init();
		self::view($viewClass);
	}
	
	/***** ROUTING *****/
	
	/**
	 * Add GET route
	 *
	 * Adds a new GET route to the router with associated callback. This
	 * route may only be matched with a HTTP GET request.
	 *
	 * @param string $pattern The URL pattern, ie. "/books/:id/edit"
	 * @param mixed $callable Anything that returns true for is_callable()
	 */
	public static function get($pattern, $callable) {
		self::router()->map($pattern, $callable, Request::METHOD_GET);
	}
	
	/**
	 * Add POST route
	 *
	 * Adds a new POST route to the router with associated callback. This
	 * route may only be matched with a HTTP POST request.
	 *
	 * @param string $pattern The URL pattern, ie. "/books/:id/edit"
	 * @param mixed $callable Anything that returns true for is_callable()
	 */
	public static function post($pattern, $callable) {
		self::router()->map($pattern, $callable, Request::METHOD_POST);
	}
	
	/**
	 * Add PUT route
	 *
	 * Adds a new PUT route to the router with associated callback. This
	 * route may only be matched with a HTTP PUT request.
	 *
	 * @param string $pattern The URL pattern, ie. "/books/:id/edit"
	 * @param mixed $callable Anything that returns true for is_callable()
	 */
	public static function put($pattern, $callable) {
		self::router()->map($pattern, $callable, Request::METHOD_PUT);
	}
	
	/**
	 * Add DELETE route
	 *
	 * Adds a new DELETE route to the router with associated callback. This
	 * route may only be matched with a HTTP DELETE request.
	 *
	 * @param string $pattern The URL pattern, ie. "/books/:id/edit"
	 * @param mixed $callable Anything that returns true for is_callable()
	 */
	public static function delete($pattern, $callable) {
		self::router()->map($pattern, $callable, Request::METHOD_DELETE);
	}
	
	/**
	 * Specify NOT FOUND handler
	 *
	 * Specify a callback that will be called when a route cannot be
	 * matched to the current HTTP request.
	 *
	 * @param mixed $callable Anything that returns true for is_callable()
	 */
	public static function notFound($callable) {
		self::router()->notFound($callable);
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
	 * Get (and optionally set) the app's View class
	 *
	 * @param string $viewClass The name of the View class that renders templates
	 * @return View
	 */
	public static function view( $viewClass = null ) {
		if( !is_null($viewClass) ) {
			self::$app->view = new $viewClass();
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
	 * delegated to the current View class.
	 */
	public static function render( $template, $data = array(), $status = null ) {
		if( is_null(self::view()) ) {
			self::view('View');
		}
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
	 * Error!
	 *
	 * Set the error status and response body, then stop the Slim app and
	 * send the response to the client.
	 *
	 * @param int $status The HTTP response code
	 * @param string $body Optional response body
	 */
	public static function error( $status = 500, $body = '' ) {
		self::response()->status($status);
		self::response()->body($body);
		self::stop();
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
	 */
	public static function run() {
		
		//Run before callbacks, tweak the request if you so choose
		self::runCallables(self::$app->before);
		
		//Start primary output buffer
		ob_start();
		
		//Dispatch current request, catch output from View
		if( !self::router()->dispatch() ) {
			
			//If route is not found, use a secondary output buffer
			//to capture the "Not Found" handler's output. Then
			//stop the application and send a 404 response.
			ob_start();
			$notFoundCallable = self::router()->notFound();
			call_user_func($notFoundCallable);
			$notFound = ob_get_clean();
			self::error(404, $notFound);
			
		}
		
		//Write primary output buffer to Response body
		self::response()->write(ob_get_contents());
		
		//End primary output buffer
		ob_end_clean();
		
		//Run after callbacks, tweak the response if you so choose
		self::runCallables(self::$app->after);
		
		//Send response to client
		self::response()->send();
		
	}
	
}

register_shutdown_function('Slim::run');

?>
