<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.5
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

// Ensure mcrypt constants are defined even if mcrypt extension is not loaded
if (!extension_loaded('mcrypt')) {
    define('MCRYPT_MODE_CBC', 0);
    define('MCRYPT_RIJNDAEL_256', 0);
}

/**
 * App
 * @package  Slim
 * @author   Josh Lockhart
 * @since    1.0.0
 *
 * @property \Slim\Environment   $environment
 * @property \Slim\Http\Response $response
 * @property \Slim\Http\Request  $request
 * @property \Slim\Router        $router
 */
class App extends \Pimple
{
    /**
     * @const string
     */
    const VERSION = '2.3.5';

    /**
     * Has the app response been sent to the client?
     * @var bool
     */
    protected $responded = false;

    /**
     * Application hooks
     * @var array
     */
    protected $hooks = array(
        'slim.before' => array(array()),
        'slim.before.router' => array(array()),
        'slim.before.dispatch' => array(array()),
        'slim.after.dispatch' => array(array()),
        'slim.after.router' => array(array()),
        'slim.after' => array(array())
    );

    /********************************************************************************
    * Instantiation and Configuration
    *******************************************************************************/

    /**
     * Constructor
     * @param  array $userSettings Associative array of application settings
     * @api
     */
    public function __construct(array $userSettings = array())
    {
        // Settings
        $this['settings'] = $this->share(function ($c) use ($userSettings) {
            $config = new \Slim\Configuration(new \Slim\ConfigurationHandler);
            $config->setArray($userSettings);

            return $config;
        });

        // Environment
        $this['environment'] = $this->share(function ($c) {
            return new \Slim\Environment($_SERVER);
        });

        // Request
        $this['request'] = $this->share(function ($c) {
            $environment = $c['environment'];
            $headers = new \Slim\Http\Headers($environment);
            $cookies = new \Slim\Http\Cookies($headers);
            if ($c['settings']['cookies.encrypt'] ===  true) {
                $cookies->decrypt($c['crypt']);
            }

            return new \Slim\Http\Request($environment, $headers, $cookies);
        });

        // Response
        $this['response'] = $this->share(function ($c) {
            $headers = new \Slim\Http\Headers();
            $cookies = new \Slim\Http\Cookies();
            return new \Slim\Http\Response($headers, $cookies);
        });

        // Router
        $this['router'] = $this->share(function ($c) {
            return new \Slim\Router();
        });

        // Route
        $this['route'] = function ($c) {
            return new \Slim\Route();
        };

        // View
        $this['view'] = $this->share(function ($c) {
            $view = $c['settings']['view'];
            if ($view instanceof \Slim\Interfaces\ViewInterface === false) {
                throw new \Exception('View class must be instance of \Slim\View');
            }

            return $view;
        });

        // Crypt
        $this['crypt'] = $this->share(function ($c) {
            return new \Slim\Crypt($c['settings']['crypt.key'], $c['settings']['crypt.cipher'], $c['settings']['crypt.mode']);
        });

        // Session
        $this['session'] = $this->share(function ($c) {
            $session = new \Slim\Session($c['settings']['session.handler']);
            $session->start();
            if ($c['settings']['session.encrypt'] === true) {
                $session->decrypt($c['crypt']);
            }

            return $session;
        });

        // Flash
        $this['flash'] = $this->share(function ($c) {
            $flash = new \Slim\Flash($c['session'], $c['settings']['session.flash_key']);
            // TODO: Build array-access to current request messages for easy view integration
            //
            // if ($c['settings']['view'] instanceof \Slim\View) {
            //     $c['view']->set('flash', $flash->getMessages());
            // }

            return $flash;
        });

        // Mode
        $this['mode'] = function ($c) {
            $mode = $c['settings']['mode'];

            if (isset($_ENV['SLIM_MODE'])) {
                $mode = $_ENV['SLIM_MODE'];
            } else {
                $envMode = getenv('SLIM_MODE');
                if ($envMode !== false) {
                    $mode = $envMode;
                }
            }

            return $mode;
        };

        // Middleware stack
        $this['middleware'] = array($this);
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
     * @param  string|array $name   If a string, the name of the setting to set or retrieve. Else an associated array of setting names and values
     * @param  mixed        $value  If name is a string, the value of the setting identified by $name
     * @return mixed                The value of a setting if only one argument is a string
     * @api
     */
    public function config($name, $value = null)
    {
        if (func_num_args() === 1) {
            if (is_array($name)) {
                foreach ($name as $key => $value) {
                    $this['settings'][$key] = $value;
                }
            } else {
                return isset($this['settings'][$name]) ? $this['settings'][$name] : null;
            }
        } else {
            $this['settings'][$name] = $value;
        }
    }

    /**
     * Configure Slim for a given mode
     *
     * This method will immediately invoke the callable if
     * the specified mode matches the current application mode.
     * Otherwise, the callable is ignored. This should be called
     * only _after_ you initialize your Slim app.
     *
     * @param  string $mode
     * @param  mixed  $callable
     * @api
     */
    public function configureMode($mode, $callable)
    {
        if ($mode === $this['mode'] && is_callable($callable)) {
            call_user_func($callable);
        }
    }

    /********************************************************************************
    * Routing
    *******************************************************************************/

    /**
     * Add GET|POST|PUT|PATCH|DELETE route
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
     * @param  array
     * @return \Slim\Route
     */
    protected function mapRoute($args)
    {
        $pattern = array_shift($args);
        $callable = array_pop($args);
        $route = $this['route'];

        $route->setPattern($pattern);
        $route->setCallable($callable);
        $route->setCaseSensitive($this['settings']['routes.case_sensitive']);
        $this['router']->map($route);
        if (count($args) > 0) {
            $route->setMiddleware($args);
        }

        return $route;
    }

    /**
     * Add route without HTTP method
     * @return \Slim\Route
     */
    public function map()
    {
        $args = func_get_args();

        return $this->mapRoute($args);
    }

    /**
     * Add GET route
     * @return \Slim\Route
     * @api
     */
    public function get()
    {
        $args = func_get_args();

        return $this->mapRoute($args)->via(\Slim\Http\Request::METHOD_GET, \Slim\Http\Request::METHOD_HEAD);
    }

    /**
     * Add POST route
     * @return \Slim\Route
     * @api
     */
    public function post()
    {
        $args = func_get_args();

        return $this->mapRoute($args)->via(\Slim\Http\Request::METHOD_POST);
    }

    /**
     * Add PUT route
     * @return \Slim\Route
     * @api
     */
    public function put()
    {
        $args = func_get_args();

        return $this->mapRoute($args)->via(\Slim\Http\Request::METHOD_PUT);
    }

    /**
     * Add PATCH route
     * @return \Slim\Route
     * @api
     */
    public function patch()
    {
        $args = func_get_args();

        return $this->mapRoute($args)->via(\Slim\Http\Request::METHOD_PATCH);
    }

    /**
     * Add DELETE route
     * @return \Slim\Route
     * @api
     */
    public function delete()
    {
        $args = func_get_args();

        return $this->mapRoute($args)->via(\Slim\Http\Request::METHOD_DELETE);
    }

    /**
     * Add OPTIONS route
     * @return \Slim\Route
     * @api
     */
    public function options()
    {
        $args = func_get_args();

        return $this->mapRoute($args)->via(\Slim\Http\Request::METHOD_OPTIONS);
    }

    /**
     * Route Groups
     *
     * This method accepts a route pattern and a callback. All route
     * declarations in the callback will be prepended by the group(s)
     * that it is in.
     *
     * Accepts the same parameters as a standard route so:
     * (pattern, middleware1, middleware2, ..., $callback)
     *
     * @api
     */
    public function group()
    {
        $args = func_get_args();
        $pattern = array_shift($args);
        $callable = array_pop($args);
        $this['router']->pushGroup($pattern, $args);
        if (is_callable($callable)) {
            call_user_func($callable);
        }
        $this['router']->popGroup();
    }

    /**
     * Add route for any HTTP method
     * @return \Slim\Route
     * @api
     */
    public function any()
    {
        $args = func_get_args();

        return $this->mapRoute($args)->via("ANY");
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
     * @param  mixed $callable Anything that returns true for is_callable()
     * @api
     */
    public function notFound ($callable = null)
    {
        if (is_callable($callable)) {
            $this['notFound'] = $this->share(function () use ($callable) {
                return $callable;
            });
        } else {
            ob_start();
            if (isset($this['notFound']) && is_callable($this['notFound'])) {
                call_user_func($this['notFound']);
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
     * @param  mixed $argument A callable or an exception
     * @api
     */
    public function error($argument = null)
    {
        if (is_callable($argument)) {
            //Register error handler
            $this['error'] = $this->share(function () use ($argument) {
                return $argument;
            });
        } else {
            //Invoke error handler
            $this['response']->setBody($this->callErrorHandler($argument));
            $this->stop();
        }
    }

    /**
     * Call error handler
     *
     * This will invoke the custom or default error handler
     * and RETURN its output.
     *
     * @param  \Exception|null $argument
     * @return string
     */
    protected function callErrorHandler($argument = null)
    {
        $this['response']->setBody('');
        $this['response']->setStatus(500);

        ob_start();
        if (isset($this['error']) && is_callable($this['error'])) {
            call_user_func_array($this['error'], array($argument));
        } else {
            call_user_func_array(array($this, 'defaultError'), array($argument));
        }

        return ob_get_clean();
    }

    /********************************************************************************
    * Rendering
    *******************************************************************************/

    /**
     * Render a template
     *
     * Call this method within a GET, POST, PUT, PATCH, DELETE, NOT FOUND, or ERROR
     * callable to render a template whose output is appended to the
     * current HTTP response body. How the template is rendered is
     * delegated to the current View.
     *
     * @param  string $template The name of the template passed into the view's render() method
     * @param  array  $data     Associative array of data made available to the view
     * @param  int    $status   The HTTP response status code to use (optional)
     * @api
     */
    public function render($template, $data = array(), $status = null)
    {
        if (!is_null($status)) {
            $this['response']->setStatus($status);
        }
        $this['view']->display($template, $data);
    }

    /********************************************************************************
    * HTTP Caching
    *******************************************************************************/

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
     * @param  int                       $time  The last modified UNIX timestamp
     * @throws \InvalidArgumentException        If provided timestamp is not an integer
     * @api
     */
    public function lastModified($time)
    {
        if (is_integer($time)) {
            $this['response']->getHeaders()->set('Last-Modified', gmdate('D, d M Y H:i:s T', $time));
            if ($time === strtotime($this['request']->getHeaders()->get('IF_MODIFIED_SINCE'))) {
                $this->halt(304);
            }
        } else {
            throw new \InvalidArgumentException('Slim::lastModified only accepts an integer UNIX timestamp value.');
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
     * @param  string                    $value The etag value
     * @param  string                    $type  The type of etag to create; either "strong" or "weak"
     * @throws \InvalidArgumentException        If provided type is invalid
     * @api
     */
    public function etag($value, $type = 'strong')
    {
        // Ensure type is correct
        if (!in_array($type, array('strong', 'weak'))) {
            throw new \InvalidArgumentException('Invalid Slim::etag type. Expected "strong" or "weak".');
        }

        // Set etag value
        $value = '"' . $value . '"';
        if ($type === 'weak') {
            $value = 'W/'.$value;
        }
        $this['response']->getHeaders()->set('ETag', $value);

        // Check conditional GET
        if ($etagsHeader = $this['request']->getHeaders()->get('IF_NONE_MATCH')) {
            $etags = preg_split('@\s*,\s*@', $etagsHeader);
            if (in_array($value, $etags) || in_array('*', $etags)) {
                $this->halt(304);
            }
        }
    }

    /**
     * Set Expires HTTP response header
     *
     * The `Expires` header tells the HTTP client the time at which
     * the current resource should be considered stale. At that time the HTTP
     * client will send a conditional GET request to the server; the server
     * may return a 200 OK if the resource has changed, else a 304 Not Modified
     * if the resource has not changed. The `Expires` header should be used in
     * conjunction with the `etag()` or `lastModified()` methods above.
     *
     * @param string|int    $time   If string, a time to be parsed by `strtotime()`;
     *                              If int, a UNIX timestamp;
     * @api
     */
    public function expires($time)
    {
        if (is_string($time)) {
            $time = strtotime($time);
        }
        $this['response']->getHeaders()->set('Expires', gmdate('D, d M Y H:i:s T', $time));
    }

    /********************************************************************************
    * HTTP Cookies
    *******************************************************************************/

    /**
     * Set HTTP cookie to be sent with the HTTP response
     *
     * @param  string     $name     The cookie name
     * @param  string     $value    The cookie value
     * @param  int|string $time     The duration of the cookie;
     *                                  If integer, should be UNIX timestamp;
     *                                  If string, converted to UNIX timestamp with `strtotime`;
     * @param  string     $path     The path on the server in which the cookie will be available on
     * @param  string     $domain   The domain that the cookie is available to
     * @param  bool       $secure   Indicates that the cookie should only be transmitted over a secure
     *                              HTTPS connection to/from the client
     * @param  bool       $httponly When TRUE the cookie will be made accessible only through the HTTP protocol
     * @api
     */
    public function setCookie($name, $value, $time = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        $settings = array(
            'value' => $value,
            'expires' => is_null($time) ? $this->config('cookies.lifetime') : $time,
            'path' => is_null($path) ? $this->config('cookies.path') : $path,
            'domain' => is_null($domain) ? $this->config('cookies.domain') : $domain,
            'secure' => is_null($secure) ? $this->config('cookies.secure') : $secure,
            'httponly' => is_null($httponly) ? $this->config('cookies.httponly') : $httponly
        );
        $this['response']->getCookies()->set($name, $settings);
    }

    /**
     * Get value of HTTP cookie from the current HTTP request
     *
     * Return the value of a cookie from the current HTTP request,
     * or return NULL if cookie does not exist. Cookies created during
     * the current request will not be available until the next request.
     *
     * @param  string      $name    The cookie name
     * @return string|null
     * @api
     */
    public function getCookie($name)
    {
        return $this['request']->getCookies()->get($name);
    }

    /**
     * Delete HTTP cookie (encrypted or unencrypted)
     *
     * Remove a Cookie from the client. This method will overwrite an existing Cookie
     * with a new, empty, auto-expiring Cookie. This method's arguments must match
     * the original Cookie's respective arguments for the original Cookie to be
     * removed. If any of this method's arguments are omitted or set to NULL, the
     * default Cookie setting values (set during Slim::init) will be used instead.
     *
     * @param  string $name     The cookie name
     * @param  string $path     The path on the server in which the cookie will be available on
     * @param  string $domain   The domain that the cookie is available to
     * @param  bool   $secure   Indicates that the cookie should only be transmitted over a secure
     *                          HTTPS connection from the client
     * @param  bool   $httponly When TRUE the cookie will be made accessible only through the HTTP protocol
     * @api
     */
    public function deleteCookie($name, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        $settings = array(
            'domain' => is_null($domain) ? $this->config('cookies.domain') : $domain,
            'path' => is_null($path) ? $this->config('cookies.path') : $path,
            'secure' => is_null($secure) ? $this->config('cookies.secure') : $secure,
            'httponly' => is_null($httponly) ? $this->config('cookies.httponly') : $httponly
        );
        $this['response']->getCookies()->remove($name, $settings);
    }

    /********************************************************************************
    * Helper Methods
    *******************************************************************************/

    /**
     * Get the absolute path to this Slim application's root directory
     *
     * This method returns the absolute path to the filesystem directory in which
     * the Slim app is instantiated. The return value WILL NOT have a trailing slash.
     *
     * @return string
     * @throws \RuntimeException If $_SERVER[SCRIPT_FILENAME] is not available
     * @api
     */
    public function root()
    {
        if ($this['environment']->has('SCRIPT_FILENAME') === false) {
            throw new \RuntimeException('The `SCRIPT_FILENAME` server variable could not be found. It is required by `\Slim\App::root()`.');
        }

        return dirname($this['environment']->get('SCRIPT_FILENAME'));
    }

    /**
     * Clean current output buffer
     */
    protected function cleanBuffer()
    {
        if (ob_get_level() !== 0) {
            ob_clean();
        }
    }

    /**
     * Stop
     *
     * The thrown exception will be caught in application's `call()` method
     * and the response will be sent as is to the HTTP client.
     *
     * @throws \Slim\Exception\Stop
     * @api
     */
    public function stop()
    {
        throw new \Slim\Exception\Stop();
    }

    /**
     * Halt
     *
     * Stop the application and immediately send the response with a
     * specific status and body to the HTTP client. This may send any
     * type of response: info, success, redirect, client error, or server error.
     * If you need to render a template AND customize the response status,
     * use the application's `render()` method instead.
     *
     * @param  int    $status  The HTTP response status
     * @param  string $message The HTTP response body
     * @api
     */
    public function halt($status, $message = '')
    {
        $this->cleanBuffer();
        $this['response']->setStatus($status);
        $this['response']->setBody($message);
        $this->stop();
    }

    /**
     * Pass
     *
     * The thrown exception is caught in the application's `call()` method causing
     * the router's current iteration to stop and continue to the subsequent route if available.
     * If no subsequent matching routes are found, a 404 response will be sent to the client.
     *
     * @throws \Slim\Exception\Pass
     * @api
     */
    public function pass()
    {
        $this->cleanBuffer();
        throw new \Slim\Exception\Pass();
    }

    /**
     * Set the HTTP response Content-Type
     * @param  string $type The Content-Type for the Response (ie. text/html)
     * @api
     */
    public function contentType($type)
    {
        $this['response']->getHeaders()->set('Content-Type', $type);
    }

    /**
     * Set the HTTP response status code
     * @param  int $code The HTTP response status code
     * @api
     */
    public function status($code)
    {
        $this['response']->setStatus($code);
    }

    /**
     * Get the URL for a named route
     * @param  string            $name   The route name
     * @param  array             $params Associative array of URL parameters and replacement values
     * @throws \RuntimeException         If named route does not exist
     * @return string
     * @api
     */
    public function urlFor($name, $params = array())
    {
        return $this['request']->getScriptName() . $this['router']->urlFor($name, $params);
    }

    /**
     * Redirect
     *
     * This method immediately redirects to a new URL. By default,
     * this issues a 302 Found response; this is considered the default
     * generic redirect response. You may also specify another valid
     * 3xx status code if you want. This method will automatically set the
     * HTTP Location header for you using the URL parameter.
     *
     * @param  string $url    The destination URL
     * @param  int    $status The HTTP redirect status code (optional)
     * @api
     */
    public function redirect($url, $status = 302)
    {
        $this['response']->redirect($url, $status);
        $this->halt($status);
    }

    /********************************************************************************
    * Hooks
    *******************************************************************************/

    /**
     * Assign hook
     * @param  string $name     The hook name
     * @param  mixed  $callable A callable object
     * @param  int    $priority The hook priority; 0 = high, 10 = low
     * @api
     */
    public function hook($name, $callable, $priority = 10)
    {
        if (!isset($this->hooks[$name])) {
            $this->hooks[$name] = array(array());
        }
        if (is_callable($callable)) {
            $this->hooks[$name][(int) $priority][] = $callable;
        }
    }

    /**
     * Invoke hook
     * @param  string $name    The hook name
     * @param  mixed  $hookArg (Optional) Argument for hooked functions
     * @api
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
                        call_user_func($callable, $hookArg);
                    }
                }
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
     * @param  string     $name A hook name (Optional)
     * @return array|null
     * @api
     */
    public function getHooks($name = null)
    {
        if (!is_null($name)) {
            return isset($this->hooks[(string) $name]) ? $this->hooks[(string) $name] : null;
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
     * @param  string $name A hook name (Optional)
     * @api
     */
    public function clearHooks($name = null)
    {
        if (!is_null($name) && isset($this->hooks[(string) $name])) {
            $this->hooks[(string) $name] = array(array());
        } else {
            foreach ($this->hooks as $key => $value) {
                $this->hooks[$key] = array(array());
            }
        }
    }

    /********************************************************************************
     * Streaming Files
     *******************************************************************************/

    /**
     * Send a File
     *
     * This method streams a local or remote file to the client
     *
     * @param  string $file        The URI of the file, can be local or remote
     * @param  string $contentType Optional content type of the stream, if not specified Slim will attempt to get this
     * @api
     */
    public function sendFile($file, $contentType = false) {
        $fp = fopen($file, "r");
        $this['response']->stream($fp);
        if ($contentType) {
            $this['response']->getHeaders()->set("Content-Type", $contentType);
        } else {
            if (file_exists($file)) {
                //Set Content-Type
                if ($contentType) {
                    $this['response']->getHeaders()->set("Content-Type", $contentType);
                } else {
                    if (extension_loaded('fileinfo')) {
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $type = $finfo->file($file);
                        $this['response']->getHeaders()->set("Content-Type", $type);
                    } else {
                        $this['response']->getHeaders()->set("Content-Type", "application/octet-stream");
                    }
                }

                //Set Content-Length
                $stat = fstat($fp);
                $this['response']->getHeaders()->set("Content-Length", $stat['size']);
            } else {
                //Set Content-Type and Content-Length
                $data = stream_get_meta_data($fp);

                foreach ($data['wrapper_data'] as $header) {
                    list($k, $v) = explode(": ", $header, 2);

                    if ($k === "Content-Type") {
                        if ($contentType) {
                            $this['response']->getHeaders()->set("Content-Type", $contentType);
                        } else {
                            $this['response']->getHeaders()->set("Content-Type", $v);
                        }
                    } else if ($k === "Content-Length") {
                        $this['response']->getHeaders()->set("Content-Length", $v);
                    }
                }
            }
        }
        $this->finalize();
    }

    /**
     * Send a Process
     *
     * This method streams a process to a client
     *
     * @param  string $command     The command to run
     * @param  string $contentType Optional content type of the stream
     * @api
     */
    public function sendProcess($command, $contentType = "text/plain") {
        $ph = popen($command, 'r');
        $this['response']->stream($ph);
        $this['response']->getHeaders()->set("Content-Type", $contentType);
        $this->finalize();
    }

    /**
     * Set Download
     *
     * This method triggers a download in the browser
     *
     * @param  string $filename Optional filename for the download
     * @api
     */
    public function setDownload($filename = false) {
        $h = "attachment;";
        if ($filename) {
            $h .= "filename='" . $filename . "'";
        }
        $this['response']->getHeaders()->set("Content-Disposition", $h);
    }

    /********************************************************************************
    * Middleware
    *******************************************************************************/

    /**
     * Add middleware
     *
     * This method prepends new middleware to the application middleware stack.
     * The argument must be an instance that subclasses Slim_Middleware.
     *
     * @param  \Slim\Middleware
     * @api
     */
    public function add(\Slim\Middleware $newMiddleware)
    {
        $middleware = $this['middleware'];
        if(in_array($newMiddleware, $middleware)) {
            $middleware_class = get_class($newMiddleware);
            throw new \RuntimeException("Circular Middleware setup detected. Tried to queue the same Middleware instance ({$middleware_class}) twice.");
        }
        $newMiddleware->setApplication($this);
        $newMiddleware->setNextMiddleware($this['middleware'][0]);
        array_unshift($middleware, $newMiddleware);
        $this['middleware'] = $middleware;
    }

    /********************************************************************************
    * Runner
    *******************************************************************************/

    /**
     * Run
     *
     * This method invokes the middleware stack, including the core Slim application;
     * the result is an array of HTTP status, header, and body. These three items
     * are returned to the HTTP client.
     *
     * @api
     */
    public function run()
    {
        set_error_handler(array('\Slim\App', 'handleErrors'));

        // Invoke middleware and application stack
        try {
            $this['middleware'][0]->call();
        } catch (\Exception $e) {
            $this['response']->write($this->callErrorHandler($e));
        }

        // Finalize and send response
        $this->finalize();

        restore_error_handler();
    }

    /**
     * Dispatch request and build response
     *
     * This method will route the provided Request object against all available
     * application routes. The provided response will reflect the status, header, and body
     * set by the invoked matching route.
     *
     * The provided Request and Response objects are updated by reference. There is no
     * value returned by this method.
     *
     * @param  \Slim\Http\Request  The request instance
     * @param  \Slim\Http\Response The response instance
     */
    protected function dispatchRequest(\Slim\Http\Request $request, \Slim\Http\Response $response)
    {
        try {
            $this->applyHook('slim.before');
            ob_start();
            $this->applyHook('slim.before.router');
            $dispatched = false;
            $matchedRoutes = $this['router']->getMatchedRoutes($request->getMethod(), $request->getPathInfo(), false);
            foreach ($matchedRoutes as $route) {
                try {
                    $this->applyHook('slim.before.dispatch');
                    $dispatched = $route->dispatch();
                    $this->applyHook('slim.after.dispatch');
                    if ($dispatched) {
                        break;
                    }
                } catch (\Slim\Exception\Pass $e) {
                    continue;
                }
            }
            if (!$dispatched) {
                $this->notFound();
            }
            $this->applyHook('slim.after.router');
        } catch (\Slim\Exception\Stop $e) {}
        $response->write(ob_get_clean());
        $this->applyHook('slim.after');
    }

    /**
     * Perform a sub-request from within an application route
     *
     * This method allows you to prepare and initiate a sub-request, run within
     * the context of the current request. This WILL NOT issue a remote HTTP
     * request. Instead, it will route the provided URL, method, headers,
     * cookies, body, and server variables against the set of registered
     * application routes. The result response object is returned.
     *
     * @param  string $url             The request URL
     * @param  string $method          The request method
     * @param  array  $headers         Associative array of request headers
     * @param  array  $cookies         Associative array of request cookies
     * @param  string $body            The request body
     * @param  array  $serverVariables Custom $_SERVER variables
     * @return \Slim\Http\Response
     */
    public function subRequest($url, $method = 'GET', array $headers = array(), array $cookies = array(), $body = '', array $serverVariables = array())
    {
        // Build sub-request and sub-response
        $environment = new \Slim\Environment(array_merge(array(
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $url,
            'SCRIPT_NAME' => '/index.php'
        ), $serverVariables));

        $headers = new \Slim\Http\Headers($environment);
        $headers->parseHeaders($headers);

        $cookies = new \Slim\Http\Cookies($headers);
        $cookies->replace($cookies);

        $subRequest = new \Slim\Http\Request($environment, $headers, $cookies, $body);
        $subResponse = new \Slim\Http\Response(new \Slim\Http\Headers(), new \Slim\Http\Cookies());

        // Cache original request and response
        $oldRequest = $this['request'];
        $oldResponse = $this['response'];

        // Set sub-request and sub-response
        $this['request'] = $subRequest;
        $this['response'] = $subResponse;

        // Dispatch sub-request through application router
        $this->dispatchRequest($subRequest, $subResponse);

        // Restore original request and response
        $this['request'] = $oldRequest;
        $this['response'] = $oldResponse;

        return $subResponse;
    }

    /**
     * Call
     *
     * This method finds and iterates all route objects that match the current request URI.
     */
    public function call()
    {
        $this->dispatchRequest($this['request'], $this['response']);
    }

    /**
     * Finalize send response
     *
     * This method sends the response object
     */
    public function finalize() {
        if (!$this->responded) {
            $this->responded = true;

            // Finalise session if it has been used
            if (isset($_SESSION)) {
                // Save flash messages to session
                $this['flash']->save();

                // Encrypt, save, close session
                if ($this->config('session.encrypt') === true) {
                    $this['session']->encrypt($this['crypt']);
                }
                $this['session']->save();
            }

            //Fetch status, header, and body
            list($status, $headers, $body) = $this['response']->finalize();

            // Encrypt and serialize cookies
            if ($this['settings']['cookies.encrypt']) {
                $this['response']->getCookies()->encrypt($this['crypt']);
            }
            $this['response']->getCookies()->setHeaders($headers);

            //Send headers
            if (headers_sent() === false) {
                //Send status
                if (strpos(PHP_SAPI, 'cgi') === 0) {
                    header(sprintf('Status: %s', \Slim\Http\Response::getMessageForCode($status)));
                } else {
                    header(sprintf('HTTP/%s %s', $this->config('http.version'), \Slim\Http\Response::getMessageForCode($status)));
                }

                //Send headers
                foreach ($headers as $name => $value) {
                    $hValues = explode("\n", $value);
                    foreach ($hValues as $hVal) {
                        header("$name: $hVal", false);
                    }
                }
            }

            //Send body, but only if it isn't a HEAD request
            if (!$this['request']->isHead()) {
                if ($this['response']->isStream()) {
                    // As stream
                    while (!feof($body)) {
                        ob_start();
                        echo fread($body, 1024);
                        echo ob_get_clean();
                        ob_flush();
                    }
                } else {
                    echo $body;
                }
            }
        }
    }

    /********************************************************************************
    * Error Handling and Debugging
    *******************************************************************************/

    /**
     * Convert errors into ErrorException objects
     *
     * This method catches PHP errors and converts them into \ErrorException objects;
     * these \ErrorException objects are then thrown and caught by Slim's
     * built-in or custom error handlers.
     *
     * @param  int            $errno   The numeric type of the Error
     * @param  string         $errstr  The error message
     * @param  string         $errfile The absolute path to the affected file
     * @param  int            $errline The line number of the error in the affected file
     * @return bool
     * @throws \ErrorException
     */
    public static function handleErrors($errno, $errstr = '', $errfile = '', $errline = '')
    {
        if (!($errno & error_reporting())) {
            return;
        }

        throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
    }

    /**
     * Generate diagnostic template markup
     *
     * This method accepts a title and body content to generate an HTML document layout.
     *
     * @param  string $title The title of the HTML template
     * @param  string $body  The body content of the HTML template
     * @return string
     */
    protected static function generateTemplateMarkup($title, $body)
    {
        return sprintf("<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body><h1>%s</h1>%s</body></html>", $title, $title, $body);
    }

    /**
     * Default Not Found handler
     */
    protected function defaultNotFound()
    {
        echo static::generateTemplateMarkup('404 Page Not Found', '<p>The page you are looking for could not be found. Check the address bar to ensure your URL is spelled correctly. If all else fails, you can visit our home page at the link below.</p><a href="' . $this['request']->getScriptName() . '/">Visit the Home Page</a>');
    }

    /**
     * Default Error handler
     */
    protected function defaultError($e)
    {
        $this->contentType('text/html');

        if ($this['mode'] === 'development') {
            $title = 'Slim Application Error';
            $html = '';

            if ($e instanceof \Exception) {
                $code = $e->getCode();
                $message = $e->getMessage();
                $file = $e->getFile();
                $line = $e->getLine();
                $trace = $e->getTraceAsString();

                $html = '<p>The application could not run because of the following error:</p>';
                $html .= '<h2>Details</h2>';
                $html .= sprintf('<div><strong>Type:</strong> %s</div>', get_class($e));
                if ($code) {
                    $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
                }
                if ($message) {
                    $html .= sprintf('<div><strong>Message:</strong> %s</div>', $message);
                }
                if ($file) {
                    $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
                }
                if ($line) {
                    $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
                }
                if ($trace) {
                    $html .= '<h2>Trace</h2>';
                    $html .= sprintf('<pre>%s</pre>', $trace);
                }
            } else {
                $html = sprintf('<p>%s</p>', $e);
            }

            echo self::generateTemplateMarkup($title, $html);
        } else {
            echo self::generateTemplateMarkup(
                'Error',
                '<p>A website error has occurred. The website administrator has been notified of the issue. Sorry'
                . 'for the temporary inconvenience.</p>'
            );
        }
    }
}
