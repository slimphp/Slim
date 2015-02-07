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
        parent::__construct();

        $this['settings'] = function ($c) use ($userSettings) {
            $config = new \Slim\Configuration(new \Slim\ConfigurationHandler);
            $config->setArray($userSettings);

            return $config;
        };

        $this['environment'] = function ($c) {
            return new Environment($_SERVER);
        };

        $this['request'] = $this->factory(function ($c) {
            $environment = $c['environment'];
            $headers = new Http\Headers($environment);
            $cookies = new Http\Cookies($headers);
            if ($c['settings']['cookies.encrypt'] === true) {
                $cookies->decrypt($c['crypt']);
            }

            return new Http\Request($environment, $headers, $cookies);
        });

        $this['response'] = $this->factory(function ($c) {
            $headers = new Http\Headers();
            $cookies = new Http\Cookies();
            $cookies->setDefaults([
                'expires' => $c['settings']['cookies.lifetime'],
                'path' => $c['settings']['cookies.path'],
                'domain' => $c['settings']['cookies.domain'],
                'secure' => $c['settings']['cookies.secure'],
                'httponly' => $c['settings']['cookies.httponly']
            ]);
            $response = new Http\Response($headers, $cookies);
            $response->setProtocolVersion('HTTP/' . $c['settings']['http.version']);

            return $response;
        });

        $this['router'] = function ($c) {
            return new Router();
        };

        $this['view'] = function ($c) {
            return new View($c['settings']['view.templates']);
        };

        $this['crypt'] = function ($c) {
            return new Crypt($c['settings']['crypt.key'], $c['settings']['crypt.cipher'], $c['settings']['crypt.mode']);
        };

        $this['session'] = function ($c) {
            $session = new Session($c['settings']['session.handler']);
            $session->start();
            if ($c['settings']['session.encrypt'] === true) {
                $session->decrypt($c['crypt']);
            }

            return $session;
        };

        $this['flash'] = function ($c) {
            $flash = new Flash($c['session'], $c['settings']['session.flash_key']);
            if ($c['settings']['view'] instanceof Interfaces\ViewInterface) {
                $c['view']->set('flash', $flash);
            }

            return $flash;
        };

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

        $this['errorHandler'] = function ($c) {
            return new ErrorHandler();
        };

        $this['notFoundHandler'] = function ($c) {
            return new NotFoundHandler();
        };

        $this['middleware'] = array($this);
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
        $route = new \Slim\Route($pattern, $callable, $this['settings']['routes.case_sensitive']);
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
            $this['response']->setHeader('Last-Modified', gmdate('D, d M Y H:i:s T', $time));
            if ($time === strtotime($this['request']->getHeader('IF_MODIFIED_SINCE'))) {
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
        $this['response']->setHeader('ETag', $value);

        // Check conditional GET
        if ($etagsHeader = $this['request']->getHeader('IF_NONE_MATCH')) {
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
        $this['response']->setHeader('Expires', gmdate('D, d M Y H:i:s T', $time));
    }

    /********************************************************************************
    * Helper Methods
    *******************************************************************************/

    /**
     * Stop
     *
     * This method stops the application and sends the provided
     * Response object to the HTTP client.
     *
     * @param  \Slim\Interfaces\Http\ResponseInterface $response
     * @throws \Slim\Exception\Stop
     * @api
     */
    public function stop(Interfaces\Http\ResponseInterface $response)
    {
        throw new Exception\Stop($response);
    }

    /**
     * Halt
     *
     * This method prepares a new HTTP response with a specific
     * status and message. The method immediately halts the
     * application and returns a new response with a specific
     * status and message.
     *
     * @param int    $status  The desired HTTP status
     * @param string $message The desired HTTP message
     * @api
     */
    public function halt($status, $message = '')
    {
        $response = $this['response'];
        $response->setStatus($status);
        $response->write($message);
        $this->stop($response);
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
        throw new Exception\Pass();
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
     * @param string $url    The destination URL
     * @param int    $status The HTTP redirect status code (optional)
     * @api
     */
    public function redirect($url, $status = 302)
    {
        $response = $this['response'];
        $response->setStatus($status);
        $response->setHeader('Location', $url);

        $this->stop($response);
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
    * Middleware
    *******************************************************************************/

    /**
     * Add middleware
     *
     * This method prepends new middleware to the application middleware stack.
     *
     * @param Interfaces\MiddlewareInterface $newMiddleware
     * @api
     */
    public function add(Interfaces\MiddlewareInterface $newMiddleware)
    {
        $middleware = $this['middleware'];
        if (in_array($newMiddleware, $middleware) === true) {
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
     * Run application
     *
     * This method traverses the middleware stack, including the core Slim application,
     * and captures the resultant HTTP response object. It then sends the response
     * back to the HTTP client.
     *
     * @api
     */
    public function run()
    {
        // Define application error handler
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (!($errno & error_reporting())) {
                return;
            }
            throw new \ErrorException($errstr, $errno, 1, $errfile, $errline);
        });

        // Get new request and response objects from container factory
        $request = $this['request'];
        $response = $this['response'];

        // Traverse middleware stack and fetch updated response
        try {
            $response = $this['middleware'][0]->call($request, $response);
        } catch (Exception\Stop $e) {
            $response = $e->getResponse();
        } catch (\Exception $e) {
            $response = $this['errorHandler']($request, $response, $e);
        }

        // Finalize and send HTTP response
        $this->finalize($request, $response);

        restore_error_handler();
    }

    /**
     * Call
     *
     * This method implements the middleware interface. It receives
     * Request and Response objects, and it returns a Response object
     * after dispatching the Request object to the appropriate Route
     * callback routine.
     *
     * @param  Interfaces\Http\RequestInterface  $request  The request object
     * @param  Interfaces\Http\ResponseInterface $response The response object
     * @return Interfaces\Http\ResponseInterface
     */
    public function call(Interfaces\Http\RequestInterface $request, Interfaces\Http\ResponseInterface $response)
    {
        // TODO: Inject request and response objects into hooks?
        try {
            $this->applyHook('slim.before');
            $this->applyHook('slim.before.router'); // Legacy
            $dispatched = false;
            $matchedRoutes = $this['router']->getMatchedRoutes($request->getMethod(), $request->getPathInfo(), false);
            foreach ($matchedRoutes as $route) {
                try {
                    $this->applyHook('slim.before.dispatch');
                    $response = $route->dispatch($request, $response);
                    $this->applyHook('slim.after.dispatch');
                    $dispatched = true;
                    break;
                } catch (Exception\Pass $e) {
                    continue;
                }
            }
            if (!$dispatched) {
                $response = $this['notFoundHandler']($request, $response);
            }
        } catch (Exception\Stop $e) {
            $response = $e->getResponse();
        }
        $this->applyHook('slim.after.router'); // Legacy
        $this->applyHook('slim.after');

        return $response;
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
     * @return Interfaces\Http\ResponseInterface
     */
    public function subRequest($url, $method = 'GET', array $headers = array(), array $cookies = array(), $body = '', array $serverVariables = array())
    {
        $environment = new Environment(array_merge(array(
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $url,
            'SCRIPT_NAME' => '/index.php'
        ), $serverVariables));
        $headers = new Http\Headers($environment);
        $cookies = new Http\Cookies($headers);
        $subRequest = new Http\Request($environment, $headers, $cookies, $body);
        $subResponse = new Http\Response(new Http\Headers(), new Http\Cookies());

        return $this->call($subRequest, $subResponse);
    }

    /**
     * Finalize and send the HTTP response
     *
     * @param Interfaces\Http\RequestInterface  $request
     * @param Interfaces\Http\ResponseInterface $response
     */
    public function finalize(Interfaces\Http\RequestInterface $request, Interfaces\Http\ResponseInterface $response) {
        if (!$this->responded) {
            $this->responded = true;

            // Finalise session if it has been used
            if (isset($_SESSION)) {
                // Save flash messages to session
                $this['flash']->save();

                // Encrypt, save, close session
                if ($this['settings']['session.encrypt'] === true) {
                    $this['session']->encrypt($this['crypt']);
                }
                $this['session']->save();
            }

            // Encrypt cookies
            if ($this['settings']['cookies.encrypt']) {
                $response->encryptCookies($this['crypt']);
            }

            // Send response
            $response->finalize($request)->send();
        }
    }
}
