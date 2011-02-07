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

//Slim's Encryted Cookies rely on libmcyrpt and these two constants.
//If libmycrpt is unavailable, we ensure the expected constants
//are available to avoid errors.
if ( !defined('MCRYPT_RIJNDAEL_256') ) {
    define('MCRYPT_RIJNDAEL_256', 0);
}
if ( !defined('MCRYPT_MODE_CBC') ) {
    define('MCRYPT_MODE_CBC', 0);
}

//This determines which errors are reported by PHP. By default, all
//errors (including E_STRICT) are reported.
error_reporting(E_ALL | E_STRICT);

//This tells PHP that Slim will handle errors.
set_error_handler(array('Slim', 'handleErrors'));

//This tells PHP that Slim will handle exceptions.
set_exception_handler(array('Slim', 'handleExceptions'));

//This tells PHP to auto-load classes using Slim's autoloader; this will
//only auto-load a class file located in the same directory as Slim.php
//whose file name (excluding the final dot and extension) is the same
//as its class name (case-sensitive). For example, "View.php" will be
//loaded when Slim uses the "View" class for the first time.
spl_autoload_register(array('Slim', 'autoload'));

//PHP 5.3 will complain (loudly) if you don't set a timezone. If you do not
//specify your own timezone before requiring Slim, this tells PHP to use UTC.
if ( @date_default_timezone_set(date_default_timezone_get()) === false ) {
    date_default_timezone_set('UTC');
}

/**
 * Slim
 *
 * This is the primary class for the Slim framework. This class provides
 * the following functionality:
 *
 * - Instantiates and runs the Slim application
 * - Manages application settings
 * - Prepares the Request
 * - Prepares the Response
 * - Prepares the Router
 * - Prepares the View
 * - Prepares the Logger
 * - Provides error handling
 * - Provides helper methods (caching, redirects, etc.)
 *
 * @package Slim
 * @author  Josh Lockhart <info@joshlockhart.com>
 * @since   Version 1.0
 */
class Slim {

    /**
     * @var Slim The application instance
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
     * @var array Key-value array of application settings
     */
    private $settings = array();

    /**
     * @var array Plugin hooks
     */
    private $hooks = array(
        'slim.before' => array(),
        'slim.before.router' => array(),
        'slim.before.dispatch' => array(),
        'slim.after.dispatch' => array(),
        'slim.after.router' => array(),
        'slim.after' => array()
    );

    /**
     * Slim auto-loader
     *
     * This method lazy-loads class files when a given class if first used.
     * Class files must exist in the same directory as this file and be named
     * the same as its class definition (excluding the dot and extension).
     *
     * @return void
     */
    public static function autoload( $className ) {
        if ( file_exists($file = dirname(__FILE__) . '/' . $className . '.php') ) {
            require_once($file);
        }
    }

    /**
     * Handle errors
     *
     * This is the global Error handler that will catch uncaught errors
     * and display a nice-looking page with details about the error.
     *
     * @param   int     $errno      The numeric type of the Error
     * @param   string  $errstr     The error message
     * @param   string  $errfile    The absolute path to the affected file
     * @param   int     $errline    The line number of the error in the affected file
     * @return  void
     */
    public static function handleErrors( $errno, $errstr = '', $errfile = '', $errline = '' ) {
        if ( !(error_reporting() & $errno) ) {
            return;
        }
        Log::error(sprintf("Message: %s | File: %s | Line: %d | Level: %d", $errstr, $errfile, $errline, $errno));
        if ( self::config('debug') === true ) {
            Slim::halt(500, self::generateErrorMarkup($errstr, $errfile, $errline));
        } else {
            self::error();
        }
        die();
    }

    /**
     * Handle exceptions
     *
     * This is the global Exception handler that will catch uncaught exceptions
     * and display a nice-looking page with details about the exception.
     *
     * @param   Exception $e
     * @return  void
     */
    public static function handleExceptions( Exception $e ) {
        if ( $e instanceof SlimStopException === false ) {
            Log::error($e);
            if ( self::config('debug') === true ) {
                Slim::halt(500, self::generateErrorMarkup($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
            } else {
                self::error();
            }
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
    private static function generateErrorMarkup( $message, $file = '', $line = '', $trace = '' ) {
        $body = '<p>The application could not run because of the following error:</p>';
        $body .= "<h2>Details:</h2><strong>Message:</strong> $message<br/>";
        if ( $file !== '' ) $body .= "<strong>File:</strong> $file<br/>";
        if ( $line !== '' ) $body .= "<strong>Line:</strong> $line<br/>";
        if ( $trace !== '' ) $body .= '<h2>Stack Trace:</h2>' . nl2br($trace);
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
     * @return  string
     */
    private static function generateTemplateMarkup( $title, $body ) {
        $html = "<html><head><title>$title</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>";
        $html .= "<h1>$title</h1>";
        $html .= $body;
        $html .= '</body></html>';
        return $html;
    }

    /**
     * Default Not Found handler
     *
     * @return void
     */
    public static function defaultNotFound() {
        echo self::generateTemplateMarkup('404 Page Not Found', '<p>The page you are looking for could not be found. Check the address bar to ensure your URL is spelled correctly. If all else fails, you can visit our home page at the link below.</p><a href="' . Slim::request()->root . '">Visit the Home Page</a>');
    }

    /**
     * Default Error handler
     *
     * @return void
     */
    public static function defaultError() {
        echo self::generateTemplateMarkup('Error', '<p>A website error has occured. The website administrator has been notified of the issue. Sorry for the temporary inconvenience.</p>');
    }


    /***** INITIALIZER *****/

    /**
     * Constructor
     *
     * @param   array $userSettings
     * @return  void
     */
    private function __construct( $userSettings = array() ) {
        $this->settings = array_merge(array(
            //Logging
            'log.enable' => false,
            'log.logger' => null,
            'log.path' => './logs',
            'log.level' => 4,
            //Debugging
            'debug' => true,
            //View
            'templates_dir' => './templates',
            'view' => 'View',
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
            'cookies.user_id' => 'DEFAULT'
        ), $userSettings);
        $this->request = new Request();
        $this->response = new Response($this->request);
        $this->router = new Router($this->request);
        $this->response->setCookieJar(new CookieJar($this->settings['cookies.secret_key'], array(
            'high_confidentiality' => $this->settings['cookies.encrypt'],
            'mcrypt_algorithm' => $this->settings['cookies.cipher'],
            'mcrypt_mode' => $this->settings['cookies.cipher_mode'],
            'enable_ssl' => $this->settings['cookies.secure']
        )));
    }

    /**
     * Initialize Slim
     *
     * This instantiates the Slim application using the provided
     * application settings if available. This also:
     *
     * - Sets a default Not Found handler
     * - Sets a default Error handler
     * - Sets the View class
     *
     * Legacy Support:
     *
     * To support applications built with an older version of Slim,
     * this method's argument may also be a string (the name of a View class)
     * or an instance of a View class or subclass.
     *
     * @param   array|string|View  $viewClass   An array of settings;
     *                                          The name of a View class;
     *                                          A View class or subclass instance;
     * @return  void
     */
    public static function init( $userSettings = array() ) {
        //Legacy support
        if ( is_string($userSettings) || $userSettings instanceof View ) {
            $settings = array('view' => $userSettings);
        } else {
            $settings = (array)$userSettings;
        }
        self::$app = new Slim($settings);
        self::notFound(array('Slim', 'defaultNotFound'));
        self::error(array('Slim', 'defaultError'));
        self::view(Slim::config('view'));
        if ( Slim::config('log.enable') === true ) {
            $logger = Slim::config('log.logger');
            if ( empty($logger) ) {
                Log::setLogger(new Logger(Slim::config('log.path'), Slim::config('log.level')));
            } else {
                Log::setLogger($logger);
            }
        }
    }

    /**
     * Get the Slim application instance
     *
     * @return Slim
     */
    public static function getInstance() {
        if ( self::$app instanceof Slim === false ) {
            self::init();
        }
        return self::$app;
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
     * If two arguments are provided, the first argument is the name of the setting
     * to be created or updated, and the second argument is the setting value.
     *
     * @param   string|array    $name   If a string, the name of the setting to set or retrieve. Else an associated array of setting names and values
     * @param   mixed           $value  If name is a string, the value of the setting identified by $name
     * @return  mixed           The value of a setting if only one argument is a string
     */
    public static function config( $name, $value = null ) {
        if ( func_num_args() === 1 ) {
            if ( is_array($name) ) {
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
     * Adds a new GET route to the router with associated callable. This
     * route may only be matched with an HTTP GET request.
     *
     * @param   string  $pattern    The URL pattern, ie. "/books/:id/edit"
     * @param   mixed   $callable   Anything that returns true for is_callable()
     * @return  Route
     */
    public static function get( $pattern, $callable ) {
        return self::router()->map($pattern, $callable, Request::METHOD_GET);
    }

    /**
     * Add POST route
     *
     * Adds a new POST route to the router with associated callable. This
     * route may only be matched with an HTTP POST request.
     *
     * @param   string  $pattern    The URL pattern, ie. "/books/:id/edit"
     * @param   mixed   $callable   Anything that returns true for is_callable()
     * @return  Route
     */
    public static function post( $pattern, $callable ) {
        return self::router()->map($pattern, $callable, Request::METHOD_POST);
    }

    /**
     * Add PUT route
     *
     * Adds a new PUT route to the router with associated callable. This
     * route may only be matched with an HTTP PUT request.
     *
     * @param   string  $pattern    The URL pattern, ie. "/books/:id/edit"
     * @param   mixed   $callable   Anything that returns true for is_callable()
     * @return  Route
     */
    public static function put( $pattern, $callable ) {
        return self::router()->map($pattern, $callable, Request::METHOD_PUT);
    }

    /**
     * Add DELETE route
     *
     * Adds a new DELETE route to the router with associated callable. This
     * route may only be matched with an HTTP DELETE request.
     *
     * @param   string  $pattern    The URL pattern, ie. "/books/:id/edit"
     * @param   mixed   $callable   Anything that returns true for is_callable()
     * @return  Route
     */
    public static function delete( $pattern, $callable ) {
        return self::router()->map($pattern, $callable, Request::METHOD_DELETE);
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
     * @return  void
     */
    public static function notFound( $callable = null ) {
        if ( !is_null($callable) ) {
            self::router()->notFound($callable);
        } else {
            ob_start();
            call_user_func(self::router()->notFound());
            Slim::halt(404, ob_get_clean());
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
     * If the $callable parameter is not null and is callable, this
     * method will register the callable to be invoked when an uncaught
     * Exception or Error is detected. It WILL NOT invoke the handler.
     *
     * 2. When invoking the handler:
     *
     * If the $callable parameter is null, Slim assumes you want
     * to invoke an already-registered handler. If the handler has been
     * registered and is callable, it is invoked and sends a 500 HTTP Response
     * whose body is the output of the Error handler.
     *
     * @param   mixed $callable Anything that returns true for is_callable()
     * @return  void
     */
    public static function error( $callable = null ) {
        if ( !is_null($callable) ) {
            self::router()->error($callable);
        } else {
            ob_start();
            call_user_func(self::router()->error());
            Slim::halt(500, ob_get_clean());
        }
    }

    /***** CALLBACKS *****/

    /**
     * Before Callback (DEPRECATION WARNING!)
     *
     * This queues a callable to be invoked before the Slim application
     * is run. Queued callables are invoked in the order they are added.
     *
     * THIS METHOD WILL BE DEPRECATED IN THE NEXT VERSION. USE `Slim::hook()` INSTEAD.
     *
     * @param   mixed $callable Anything that returns true for is_callable()
     * @return  void
     */
    public static function before( $callable ) {
        self::hook('slim.before.router', $callable);
    }

    /**
     * After Callback (DEPRECATION WARNING!)
     *
     * This queues a callable to be invoked after the Slim application
     * is run. Queued callables are invoked in the order they are added.
     *
     * THIS METHOD WILL BE DEPRECATED IN THE NEXT VERSION. USE `Slim::hook()` INSTEAD.
     *
     * @param   mixed $callable Anything that returns true for is_callable()
     * @return  void
     */
    public static function after( $callable ) {
        self::hook('slim.after.router', $callable);
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
     * Get the Router object
     *
     * @return Router
     */
    public static function router() {
        return self::$app->router;
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
     *                                  An instance of View;
     * @return  View
     */
    public static function view( $viewClass = null ) {
        if ( !is_null($viewClass) ) {
            $existingData = is_null(self::$app->view) ? array() : self::$app->view->getData();
            if ( $viewClass instanceOf View ) {
                self::$app->view = $viewClass;
            } else {
                self::$app->view = new $viewClass();
            }
            self::$app->view->appendData($existingData);
        }
        return self::$app->view;
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
     * @param   array   $data       Associative array of data made available to the View
     * @param   int     $status     The HTTP response status code to use (Optional)
     * @return  void
     */
    public static function render( $template, $data = array(), $status = null ) {
        self::view()->setTemplatesDirectory(self::config('templates_dir'));
        if ( !is_null($status) ) {
            self::response()->status($status);
        }
        self::view()->appendData($data);
        self::view()->display($template);
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
     * @param   int                         $time   The last modified UNIX timestamp
     * @throws  SlimException                       Returns HTTP 304 Not Modified response if resource last modified time matches `If-Modified-Since` header
     * @throws  InvalidArgumentException            If provided timestamp is not an integer
     * @return  void
     */
    public static function lastModified( $time ) {
        if ( is_integer($time) ) {
            Slim::response()->header('Last-Modified', date(DATE_RFC1123, $time));
            if ( $time === strtotime(Slim::request()->header('IF_MODIFIED_SINCE')) ) {
                Slim::halt(304);
            }
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
     * @param   string                      $value  The etag value
     * @param   string                      $type   The type of etag to create; either "strong" or "weak"
     * @throws  InvalidArgumentException            If provided type is invalid
     * @return  void
     */
    public static function etag( $value, $type = 'strong' ) {

        //Ensure type is correct
        if ( !in_array($type, array('strong', 'weak')) ) {
            throw new InvalidArgumentException('Invalid Slim::etag type. Expected "strong" or "weak".');
        }

        //Set etag value
        $value = '"' . $value . '"';
        if ( $type === 'weak' ) $value = 'W/'.$value;
        Slim::response()->header('ETag', $value);

        //Check conditional GET
        if ( $etagsHeader = Slim::request()->header('IF_NONE_MATCH')) {
            $etags = preg_split('@\s*,\s*@', $etagsHeader);
            if ( in_array($value, $etags) || in_array('*', $etags) ) Slim::halt(304);
        }

    }

    /***** COOKIES *****/

    /**
     * Set a normal, unencrypted Cookie
     *
     * @param   string  $name       The cookie name
     * @param   mixed   $value      The cookie value
     * @param   mixed   $time       The duration of the cookie;
     *                              If integer, should be UNIX timestamp;
     *                              If string, converted to UNIX timestamp with `strtotime`;
     * @param   string  $path       The path on the server in which the cookie will be available on
     * @param   string  $domain     The domain that the cookie is available to
     * @param   bool    $secure     Indicates that the cookie should only be transmitted over a secure
     *                              HTTPS connection to/from the client
     * @param   bool    $httponly   When TRUE the cookie will be made accessible only through the HTTP protocol
     * @return  void
     */
    public static function setCookie( $name, $value, $time = null, $path = null, $domain = null, $secure = null, $httponly = null ) {
        $time = is_null($time) ? Slim::config('cookies.lifetime') : $time;
        $path = is_null($path) ? Slim::config('cookies.path') : $path;
        $domain = is_null($domain) ? Slim::config('cookies.domain') : $domain;
        $secure = is_null($secure) ? Slim::config('cookies.secure') : $secure;
        $httponly = is_null($httponly) ? Slim::config('cookies.httponly') : $httponly;
        self::response()->getCookieJar()->setClassicCookie($name, $value, $time, $path, $domain, $secure, $httponly);
    }

    /**
     * Get the value of a Cookie from the current HTTP Request
     *
     * Return the value of a cookie from the current HTTP request,
     * or return NULL if cookie does not exist. Cookies created during
     * the current request will not be available until the next request.
     *
     * @param   string $name
     * @return  string|null
     */
    public static function getCookie( $name ) {
        return self::request()->cookie($name);
    }

    /**
     * Set an encrypted Cookie
     *
     * @param   string  $name       The cookie name
     * @param   mixed   $value      The cookie value
     * @param   mixed   $time       The duration of the cookie;
     *                              If integer, should be UNIX timestamp;
     *                              If string, converted to UNIX timestamp with `strtotime`;
     * @param   string  $path       The path on the server in which the cookie will be available on
     * @param   string  $domain     The domain that the cookie is available to
     * @param   bool    $secure     Indicates that the cookie should only be transmitted over a secure
     *                              HTTPS connection from the client
     * @param   bool    $httponly   When TRUE the cookie will be made accessible only through the HTTP protocol
     * @return  void
     */
    public static function setEncryptedCookie( $name, $value, $time = null, $path = null, $domain = null, $secure = null, $httponly = null ) {
        $time = is_null($time) ? Slim::config('cookies.lifetime') : $time;
        $path = is_null($path) ? Slim::config('cookies.path') : $path;
        $domain = is_null($domain) ? Slim::config('cookies.domain') : $domain;
        $secure = is_null($secure) ? Slim::config('cookies.secure') : $secure;
        $httponly = is_null($httponly) ? Slim::config('cookies.httponly') : $httponly;
        $userId = Slim::config('cookies.user_id');
        self::response()->getCookieJar()->setCookie($name, $value, $userId, $time, $path, $domain, $secure, $httponly);
    }

    /**
     * Get the value of an encrypted Cookie from the current HTTP request
     *
     * Return the value of an encrypted cookie from the current HTTP request,
     * or return NULL if cookie does not exist. Encrypted cookies created during
     * the current request will not be available until the next request.
     *
     * @param   string $name
     * @return  string|null
     */
    public static function getEncryptedCookie( $name ) {
        $value = self::response()->getCookieJar()->getCookieValue($name);
        return ($value === false) ? null : $value;
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
    public static function root() {
        return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . self::request()->root;
    }

    /**
     * Stop
     *
     * Send the current Response as is and stop executing the Slim
     * application. The thrown exception will be caught by the Slim
     * custom exception handler which exits this script.
     *
     * @throws  SlimStopException
     * @return  void
     */
    public static function stop() {
        self::response()->send();
        throw new SlimStopException();
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
     * @param   int                 $status     The HTTP response status
     * @param   string              $message    The HTTP response body
     * @throws  SlimHaltException
     * @return  void
     */
    public static function halt( $status, $message = '' ) {
        if ( ob_get_level() !== 0 ) {
            ob_clean();
        }
        self::response()->status($status);
        self::response()->body($message);
        self::stop();
    }

    /**
     * Pass
     *
     * This method will cause the Router::dispatch method to ignore
     * the current route and continue to the next matching route in the
     * dispatch loop. If no subsequent mathing routes are found,
     * a 404 Not Found response will be sent to the client.
     *
     * @throws  PassException
     * @return  void
     */
    public static function pass() {
        if ( ob_get_level() !== 0 ) {
            ob_clean();
        }
        throw new SlimPassException();
    }

    /**
     * Set the HTTP response Content-Type
     *
     * @param   string $type The Content-Type for the Response (ie. text/html)
     * @return  void
     */
    public static function contentType( $type ) {
        self::response()->header('Content-Type', $type);
    }

    /**
     * Set the HTTP response status code
     *
     * @param   int $status The HTTP response status code
     * @return  void
     */
    public static function status( $code ) {
        self::response()->status($code);
    }

    /**
     * Get the URL for a named Route
     *
     * @param   string          $name       The route name
     * @param   array           $params     Key-value array of URL parameters
     * @throws  RuntimeException            If named route does not exist
     * @return  string
     */
    public static function urlFor( $name, $params = array() ) {
        return self::router()->urlFor($name, $params);
    }

    /**
     * Redirect
     *
     * This method immediately redirects to a new URL. By default,
     * this issues a 307 Temporary Redirect. You may also specify another valid
     * 3xx status code if you want. This method will automatically set the
     * HTTP Location header for you using the URL parameter and place the
     * destination URL into the response body.
     *
     * @param   string                      $url        The destination URL
     * @param   int                         $status     The HTTP redirect status code (Optional)
     * @throws  InvalidArgumentException                If status parameter is not a valid 3xx status code
     * @return  void
     */
    public static function redirect( $url, $status = 307 ) {
        if ( $status >= 300 && $status <= 307 ) {
            self::response()->header('Location', (string)$url);
            self::halt($status, (string)$url);
        } else {
            throw new InvalidArgumentException('Slim::redirect only accepts HTTP 300-307 status codes.');
        }
    }

    /***** HOOKS *****/

    /**
     * Invoke or assign hook
     *
     * @param   string  $name       The hook name
     * @param   mixed   $callable   A callable object
     * @return  void
     */
    public static function hook( $name, $callable = null ) {
        if ( !isset(self::$app->hooks[$name]) ) {
            self::$app->hooks[$name] = array();
        }
        if ( !is_null($callable) ) {
            if ( is_callable($callable) ) {
                self::$app->hooks[$name][] = $callable;
            }
        } else {
            foreach( self::$app->hooks[$name] as $listener ) {
                $listener(self::$app);
            }
        }
    }

    /**
     * Get hook listeners
     *
     * Return an array of registered hooks. If `$name` is a valid
     * hook name, only the listeners attached to that hook are returned.
     * Else, all listeners are returned as an associative array whose
     * keys are hook names and whose values are arrays of listeners.
     *
     * @param   string      $name A hook name (Optional)
     * @return  array|null
     */
    public static function getHooks( $name = null ) {
        if ( !is_null($name) ) {
            return isset(self::$app->hooks[(string)$name]) ? self::$app->hooks[(string)$name] : null;
        } else {
            return self::$app->hooks;
        }
    }

    /**
     * Clear hook listeners
     *
     * Clear all listeners for all hooks. If `$name` is
     * a valid hook name, only the listeners attached
     * to that hook will be cleared.
     *
     * @param   string  $name   Optional. A hook name.
     * @return  void
     */
    public static function clearHooks( $name = null ) {
        if ( !is_null($name) ) {
            if ( isset(self::$app->hooks[(string)$name]) ) {
                self::$app->hooks[(string)$name] = array();
            }
        } else {
            foreach( self::$app->hooks as $key => $value ) {
                self::$app->hooks[$key] = array();
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
     * @return void
     */
    public static function run() {
        try {
            self::hook('slim.before');
            ob_start();
            self::hook('slim.before.router');
            $dispatched = false;
            foreach( self::router()->getMatchedRoutes() as $route ) {
                try {
                    Slim::hook('slim.before.dispatch');
                    $dispatched = $route->dispatch();
                    Slim::hook('slim.after.dispatch');
                    if ( $dispatched ) {
                        break;
                    }
                } catch ( SlimPassException $e ) {
                    continue;
                }
            }
            if ( !$dispatched ) {
                Slim::notFound();
            }
            self::response()->write(ob_get_clean());
            self::hook('slim.after.router');
            self::response()->send();
            self::hook('slim.after');
        } catch ( SlimRequestSlashException $e ) {
            self::redirect(self::request()->root . self::request()->resource . '/', 301);
        }
    }

}

?>