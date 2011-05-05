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

//Ensure PHP session IDs only use the characters [a-z0-9]
ini_set('session.hash_bits_per_character', 4);
ini_set('session.hash_function', 0);

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
     * @var Slim_Http_Request
     */
    private $request;

    /**
     * @var Slim_Http_Response
     */
    private $response;

    /**
     * @var Slim_Router
     */
    private $router;

    /**
     * @var Slim_View
     */
    private $view;

    /**
     * @var Slim_Session_Flash
     */
    private $flash;

    /**
     * @var array Key-value array of application settings
     */
    private $settings = array();

    /**
     * @var array Plugin hooks
     */
    private $hooks = array(
        'slim.before' => array(array()),
        'slim.before.router' => array(array()),
        'slim.before.dispatch' => array(array()),
        'slim.after.dispatch' => array(array()),
        'slim.after.router' => array(array()),
        'slim.after' => array(array())
    );

    /**
     * @var string The current application mode
     */
    private $mode;

    /**
     * Slim auto-loader
     *
     * This method lazy-loads class files when a given class if first used.
     * Class files must exist in the same directory as this file and be named
     * the same as its class definition (excluding the dot and extension).
     *
     * @return void
     */
    public static function autoload( $class ) {
        if ( strpos($class, 'Slim') !== 0 ) {
            return;
        }
        $file = dirname(__FILE__) . '/' . str_replace('_', DIRECTORY_SEPARATOR, substr($class,5)) . '.php';
        if ( file_exists($file) ) {
            require $file;
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
        Slim_Log::error(sprintf("Message: %s | File: %s | Line: %d | Level: %d", $errstr, $errfile, $errline, $errno));
        if ( self::config('debug') === true ) {
            self::halt(500, self::generateErrorMarkup($errstr, $errfile, $errline));
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
        if ( $e instanceof Slim_Exception_Stop === false ) {
            Slim_Log::error($e);
            if ( self::config('debug') === true ) {
                self::halt(500, self::generateErrorMarkup($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
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
        echo self::generateTemplateMarkup('404 Page Not Found', '<p>The page you are looking for could not be found. Check the address bar to ensure your URL is spelled correctly. If all else fails, you can visit our home page at the link below.</p><a href="' . Slim::request()->getRootUri() . '">Visit the Home Page</a>');
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
            'templates.path' => './templates',
            'view' => 'Slim_View',
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
            'session.handler' => new Slim_Session_Handler_Cookies(),
            'session.flash_key' => 'flash'
        ), $userSettings);
        $this->request = new Slim_Http_Request();
        $this->response = new Slim_Http_Response($this->request);
        $this->router = new Slim_Router($this->request);
        $this->response->setCookieJar(new Slim_Http_CookieJar($this->settings['cookies.secret_key'], array(
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
     * application settings if available.
     *
     * Legacy Support:
     *
     * To support applications built with an older version of Slim,
     * this method's argument may also be a string (the name of a View class)
     * or an instance of a View class or subclass.
     *
     * @param   array|string|Slim_View  $viewClass   An array of settings;
     *                                               The name of a View class;
     *                                               A View class or subclass instance;
     * @return  void
     */
    public static function init( $userSettings = array() ) {
        //Legacy support
        if ( is_string($userSettings) || $userSettings instanceof Slim_View ) {
            $settings = array('view' => $userSettings);
        } else {
            $settings = (array)$userSettings;
        }

        //Init app
        self::$app = new Slim($settings);

        //Init Not Found and Error handlers
        self::notFound(array('Slim', 'defaultNotFound'));
        self::error(array('Slim', 'defaultError'));

        //Init view
        self::view(Slim::config('view'));

        //Init logging
        if ( Slim::config('log.enable') === true ) {
            $logger = Slim::config('log.logger');
            if ( empty($logger) ) {
                Slim_Log::setLogger(new Slim_Logger(Slim::config('log.path'), Slim::config('log.level')));
            } else {
                Slim_Log::setLogger($logger);
            }
        }

        //Start session if not already started
        if ( session_id() === '' ) {
            $sessionHandler = Slim::config('session.handler');
            if ( $sessionHandler instanceof Slim_Session_Handler ) {
                $sessionHandler->register();
            }
            session_start();
        }

        //Init flash messaging
        self::$app->flash = new Slim_Session_Flash(self::config('session.flash_key'));
        self::view()->setData('flash', self::$app->flash);

        //Determine mode
        if ( isset($_ENV['SLIM_MODE']) ) {
            self::$app->mode = (string)$_ENV['SLIM_MODE'];
        } else {
            $configMode = Slim::config('mode');
            self::$app->mode = ( $configMode ) ? (string)$configMode : 'development';
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
     * Configure Slim for a given mode
     *
     * This method will immediately invoke the callable if
     * the specified mode matches the current application mode.
     * Otherwise, the callable is ignored. This should be called
     * only _after_ you initialize your Slim app.
     *
     * @param   string  $mode
     * @param   mixed   $callable
     * @return  void
     */
    public static function configureMode( $mode, $callable ) {
        if ( self::$app && $mode === self::$app->mode && is_callable($callable) ) {
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
     * Add GET|POST|PUT|DELETE route
     *
     * Adds a new route to the router with associated callable. This
     * route will only be invoked when the HTTP request's method matches
     * this route's method.
     *
     * ARGUMENTS:
     *
     * First:       string  The URL pattern (REQUIRED)
     * In-Between:  mixed   Anything that returns TRUE for `is_callable` (OPTIONAL)
     * Last:        mixed   Anything that returns TRUE for `is_callable` (REQUIRED)
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
     * @param   string                      The HTTP method (ie. GET, POST, PUT, DELETE)
     * @param   array                       See notes above
     * @throws  InvalidArgumentException    If less than two arguments are provided
     * @return  Slim_Route
     */
    protected static function mapRoute($type, $args) {
        if ( count($args) < 2 ) {
            throw new InvalidArgumentException('Pattern and callable are required to create a route');
        }
        $pattern = array_shift($args);
        $callable = array_pop($args);
        $route = self::router()->map($pattern, $callable, $type);
        if ( count($args) > 0 ) {
            $route->setMiddleware($args);
        }
        return $route;
    }

    /**
     * Add GET route
     *
     * @see     Slim::mapRoute
     * @return  Slim_Route
     */
    public static function get() {
        $args = func_get_args();
        return self::mapRoute(Slim_Http_Request::METHOD_GET, $args);
    }

    /**
     * Add POST route
     *
     * @see     Slim::mapRoute
     * @return  Slim_Route
     */
    public static function post() {
        $args = func_get_args();
        return self::mapRoute(Slim_Http_Request::METHOD_POST, $args);
    }

    /**
     * Add PUT route
     *
     * @see     Slim::mapRoute
     * @return  Slim_Route
     */
    public static function put() {
        $args = func_get_args();
        return self::mapRoute(Slim_Http_Request::METHOD_PUT, $args);
    }

    /**
     * Add DELETE route
     *
     * @see     Slim::mapRoute
     * @return  Slim_Route
     */
    public static function delete() {
        $args = func_get_args();
        return self::mapRoute(Slim_Http_Request::METHOD_DELETE, $args);
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
            self::halt(404, ob_get_clean());
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
            self::halt(500, ob_get_clean());
        }
    }

    /***** ACCESSORS *****/

    /**
     * Get the Request object
     *
     * @return Slim_Http_Request
     */
    public static function request() {
        return self::$app->request;
    }

    /**
     * Get the Response object
     *
     * @return Slim_Http_Response
     */
    public static function response() {
        return self::$app->response;
    }

    /**
     * Get the Router object
     *
     * @return Slim_Router
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
     * @param   string|Slim_View $viewClass  The name of a Slim_View class;
     *                                       An instance of Slim_View;
     * @return  Slim_View
     */
    public static function view( $viewClass = null ) {
        if ( !is_null($viewClass) ) {
            $existingData = is_null(self::$app->view) ? array() : self::$app->view->getData();
            if ( $viewClass instanceOf Slim_View ) {
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
        $templatesPath = Slim::config('templates.path');
        //Legacy support
        if ( is_null($templatesPath) ) {
            $templatesPath = Slim::config('templates_dir');
        }
        self::view()->setTemplatesDirectory($templatesPath);
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
            self::response()->header('Last-Modified', date(DATE_RFC1123, $time));
            if ( $time === strtotime(self::request()->headers('IF_MODIFIED_SINCE')) ) {
                self::halt(304);
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
        self::response()->header('ETag', $value);

        //Check conditional GET
        if ( $etagsHeader = self::request()->headers('IF_NONE_MATCH')) {
            $etags = preg_split('@\s*,\s*@', $etagsHeader);
            if ( in_array($value, $etags) || in_array('*', $etags) ) self::halt(304);
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
        $time = is_null($time) ? self::config('cookies.lifetime') : $time;
        $path = is_null($path) ? self::config('cookies.path') : $path;
        $domain = is_null($domain) ? self::config('cookies.domain') : $domain;
        $secure = is_null($secure) ? self::config('cookies.secure') : $secure;
        $httponly = is_null($httponly) ? self::config('cookies.httponly') : $httponly;
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
        return self::request()->cookies($name);
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
        $time = is_null($time) ? self::config('cookies.lifetime') : $time;
        $path = is_null($path) ? self::config('cookies.path') : $path;
        $domain = is_null($domain) ? self::config('cookies.domain') : $domain;
        $secure = is_null($secure) ? self::config('cookies.secure') : $secure;
        $httponly = is_null($httponly) ? self::config('cookies.httponly') : $httponly;
        $userId = self::config('cookies.user_id');
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
    
    /**
     * Delete a Cookie (for both normal or encrypted Cookies)
     *
     * Remove a Cookie from the client. This method will overwrite an existing Cookie
     * with a new, empty, auto-expiring Cookie. This method's arguments must match
     * the original Cookie's respective arguments for the original Cookie to be
     * removed. If any of this method's arguments are omitted or set to NULL, the
     * default Cookie setting values (set during Slim::init) will be used instead.
     *
     * @param   string  $name       The cookie name
     * @param   string  $path       The path on the server in which the cookie will be available on
     * @param   string  $domain     The domain that the cookie is available to
     * @param   bool    $secure     Indicates that the cookie should only be transmitted over a secure
     *                              HTTPS connection from the client
     * @param   bool    $httponly   When TRUE the cookie will be made accessible only through the HTTP protocol
     * @return  void
     */
    public static function deleteCookie( $name, $path = null, $domain = null, $secure = null, $httponly = null ) {
        $path = is_null($path) ? self::config('cookies.path') : $path;
        $domain = is_null($domain) ? self::config('cookies.domain') : $domain;
        $secure = is_null($secure) ? self::config('cookies.secure') : $secure;
        $httponly = is_null($httponly) ? self::config('cookies.httponly') : $httponly;
        self::response()->getCookieJar()->deleteCookie( $name, $path, $domain, $secure, $httponly );
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
     * @throws  Slim_Exception_Stop
     * @return  void
     */
    public static function stop() {
        if ( self::$app->flash ) {
            self::$app->flash->save();
        }
        session_write_close();
        self::response()->send();
        throw new Slim_Exception_Stop();
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
     * @throws  Slim_Exception_Pass
     * @return  void
     */
    public static function pass() {
        if ( ob_get_level() !== 0 ) {
            ob_clean();
        }
        throw new Slim_Exception_Pass();
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
     * this issues a 302 Found response; this is considered the default
     * generic redirect response. You may also specify another valid
     * 3xx status code if you want. This method will automatically set the
     * HTTP Location header for you using the URL parameter and place the
     * destination URL into the response body.
     *
     * @param   string                      $url        The destination URL
     * @param   int                         $status     The HTTP redirect status code (Optional)
     * @throws  InvalidArgumentException                If status parameter is not a valid 3xx status code
     * @return  void
     */
    public static function redirect( $url, $status = 302 ) {
        if ( $status >= 300 && $status <= 307 ) {
            self::response()->header('Location', (string)$url);
            self::halt($status, (string)$url);
        } else {
            throw new InvalidArgumentException('Slim::redirect only accepts HTTP 300-307 status codes.');
        }
    }

    /***** FLASH *****/

    public static function flash( $key, $value ) {
        self::$app->flash->set($key, $value);
    }

    public static function flashNow( $key, $value ) {
        self::$app->flash->now($key, $value);
    }

    public static function flashKeep() {
        self::$app->flash->keep();
    }

    /***** HOOKS *****/

    /**
     * Assign hook
     *
     * @param   string  $name       The hook name
     * @param   mixed   $callable   A callable object
     * @param   int     $priority   The hook priority; 0 = high, 10 = low
     * @return  void
     */
    public static function hook( $name, $callable, $priority = 10 ) {
        if ( !isset(self::$app->hooks[$name]) ) {
            self::$app->hooks[$name] = array(array());
        }
        if ( is_callable($callable) ) {
            self::$app->hooks[$name][(int)$priority][] = $callable;
        }
    }

    /**
     * Invoke hook
     *
     * @param   string  $name       The hook name
     * @param   mixed   $hookArgs   (Optional) Argument for hooked functions
     * @return  mixed
     */
    public static function applyHook( $name, $hookArg = null ) {
        if ( !isset(self::$app->hooks[$name]) ) {
            self::$app->hooks[$name] = array(array());
        }
        if( !empty(self::$app->hooks[$name]) ) {
            // Sort by priority, low to high, if there's more than one priority
            if ( count(self::$app->hooks[$name]) > 1 ) {
                ksort(self::$app->hooks[$name]);
            }
            foreach( self::$app->hooks[$name] as $priority ) {
                if( !empty($priority) ) {
                    foreach($priority as $callable) {
                        $hookArg = call_user_func($callable, $hookArg);
                    }
                }
            }
            return $hookArg;
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
     * @param   string  $name   A hook name (Optional)
     * @return  void
     */
    public static function clearHooks( $name = null ) {
        if ( !is_null($name) && isset(self::$app->hooks[(string)$name]) ) {
            self::$app->hooks[(string)$name] = array(array());
        } else {
            foreach( self::$app->hooks as $key => $value ) {
                self::$app->hooks[$key] = array(array());
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
            self::applyHook('slim.before');
            ob_start();
            self::applyHook('slim.before.router');
            $dispatched = false;
            foreach( self::router()->getMatchedRoutes() as $route ) {
                try {
                    Slim::applyHook('slim.before.dispatch');
                    $dispatched = $route->dispatch();
                    Slim::applyHook('slim.after.dispatch');
                    if ( $dispatched ) {
                        break;
                    }
                } catch ( Slim_Exception_Pass $e ) {
                    continue;
                }
            }
            if ( !$dispatched ) {
                self::notFound();
            }
            self::response()->write(ob_get_clean());
            self::applyHook('slim.after.router');
            self::$app->flash->save();
            session_write_close();
            self::response()->send();
            self::applyHook('slim.after');
        } catch ( Slim_Exception_RequestSlash $e ) {
            self::redirect(self::request()->getRootUri() . self::request()->getResourceUri() . '/', 301);
        }
    }

}

