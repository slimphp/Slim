<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\ResponseInterface;

// Ensure mcrypt constants are defined even if
// mcrypt extension is not loaded
if (!extension_loaded('mcrypt')) {
    define('MCRYPT_MODE_CBC', 0);
    define('MCRYPT_RIJNDAEL_256', 0);
}

/**
 * App
 *
 * This is the "application". It lets you define routes. It runs
 * your application. And it returns the serialized HTTP response
 * back to the HTTP client.
 */
class App extends \Pimple\Container
{
    use Middlewared;

    /**
     * The current Slim Framework version
     *
     * @var string
     */
    const VERSION = '3.0.0';

    /**
     * Has the app responsed to the HTTP client?
     *
     * @var bool
     */
    protected $responded = false;

    /**
     * Application hooks
     *
     * @var array
     */
    protected $hooks = array(
        'slim.before' => array(array()),
        'slim.before.dispatch' => array(array()),
        'slim.after.dispatch' => array(array()),
        'slim.after' => array(array())
    );


    /********************************************************************************
    * Instantiation and Configuration
    *******************************************************************************/

    /**
     * Create new application
     *
     * @param array $userSettings Associative array of application settings
     */
    public function __construct(array $userSettings = array())
    {
        parent::__construct();

        $this['settings'] = function ($c) use ($userSettings) {
            $config = new Configuration(new ConfigurationHandler);
            $config->setArray($userSettings);

            return $config;
        };

        $this['environment'] = function ($c) {
            return new Http\Environment($_SERVER);
        };

        $this['request'] = $this->factory(function ($c) {
            $env = $c['environment'];
            $method = $env['REQUEST_METHOD'];
            $uri = Http\Uri::createFromEnvironment($env);
            $headers = Http\Headers::createFromEnvironment($env);
            $cookies = new Collection(Http\Cookies::parseHeader($headers->get('Cookie')));
            if ($c['settings']['cookies.encrypt'] === true) {
                $cookies->decrypt($c['crypt']);
            }
            $body = new Http\Body(fopen('php://input', 'r'));

            return new Http\Request($method, $uri, $headers, $cookies, $body);
        });

        $this['response'] = $this->factory(function ($c) {
            $headers = new Http\Headers(['Content-Type' => 'text/html']);
            $cookies = new Http\Cookies([], [
                'expires' => $c['settings']['cookies.lifetime'],
                'path' => $c['settings']['cookies.path'],
                'domain' => $c['settings']['cookies.domain'],
                'secure' => $c['settings']['cookies.secure'],
                'httponly' => $c['settings']['cookies.httponly']
            ]);
            $response = new Http\Response(200, $headers, $cookies);

            return $response->withProtocolVersion($c['settings']['http.version']);
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

        $this['errorHandler'] = function ($c) {
            return new ErrorHandler();
        };

        $this['notFoundHandler'] = function ($c) {
            return new NotFoundHandler();
        };
    }

    /********************************************************************************
    * Router proxy methods
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
     * @param  array $args Route path, optional middleware, and callback
     * @return \Slim\Interfaces\RouteInterface
     */
    protected function mapRoute($args)
    {
        $pattern = array_shift($args);
        $callable = array_pop($args);
        if ($callable instanceof \Closure) {
            $callable = $callable->bindTo($this);
        }
        $route = new Route($pattern, $callable, $this['settings']['routes.case_sensitive']);
        $this['router']->map($route);
        if (count($args) > 0) {
            //add Middleware
            $route->add($args);
        }

        return $route;
    }

    /**
     * Add route without HTTP method
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function map()
    {
        $args = func_get_args();

        return $this->mapRoute($args);
    }

    /**
     * Add GET route
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function get()
    {
        $args = func_get_args();

        return $this->mapRoute($args)->via(['GET', 'HEAD']);
    }

    /**
     * Add POST route
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function post()
    {
        $args = func_get_args();

        return $this->mapRoute($args)->via(['POST']);
    }

    /**
     * Add PUT route
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function put()
    {
        $args = func_get_args();

        return $this->mapRoute($args)->via(['PUT']);
    }

    /**
     * Add PATCH route
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function patch()
    {
        $args = func_get_args();

        return $this->mapRoute($args)->via(['PATCH']);
    }

    /**
     * Add DELETE route
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function delete()
    {
        $args = func_get_args();

        return $this->mapRoute($args)->via(['DELETE']);
    }

    /**
     * Add OPTIONS route
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function options()
    {
        $args = func_get_args();

        return $this->mapRoute($args)->via(['OPTIONS']);
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
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function any()
    {
        $args = func_get_args();

        return $this->mapRoute($args)->via(['ANY']);
    }

    /********************************************************************************
    * Application Behavior Methods
    *******************************************************************************/

    /**
     * Stop
     *
     * This method stops the application and sends the provided
     * Response object to the HTTP client.
     *
     * @param  ResponseInterface    $response
     * @throws \Slim\Exception\Stop
     */
    public function stop(ResponseInterface $response)
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
     * @param  int    $status  The desired HTTP status
     * @param  string $message The desired HTTP message
     * @throws \Slim\Exception\Stop
     */
    public function halt($status, $message = '')
    {
        $response = $this['response']->withStatus($status);
        $response->write($message);
        $this->stop($response);
    }

    /**
     * Pass
     *
     * Use this method to skip the current route iteration in the App::call() method.
     * The router iteration will skip to the next matching route, else invoke
     * the application Not Found handler.
     *
     * @throws \Slim\Exception\Pass
     */
    public function pass()
    {
        throw new Exception\Pass();
    }

    /**
     * Redirect
     *
     * This method immediately redirects to a new URL by preparing
     * and sending a new 3XX HTTP response object.
     *
     * @param string $url    The destination URL
     * @param int    $status The HTTP redirect status code (optional)
     */
    public function redirect($url, $status = 302)
    {
        $this->stop($this['response']->withStatus($status)->withHeader('Location', $url));
    }

    /********************************************************************************
    * Hooks
    *******************************************************************************/

    /**
     * Assign hook
     *
     * @param string $name     The hook name
     * @param mixed  $callable A callable object
     * @param int    $priority The hook priority; 0 = high, 10 = low
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
     *
     * @param string $name    The hook name
     * @param mixed  $hookArg (Optional) Argument for hooked functions
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
     * @param string $name A hook name (Optional)
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
    * Runner
    *******************************************************************************/

    /**
     * Run application
     *
     * This method traverses the middleware stack, including the core Slim application,
     * and captures the resultant HTTP response object. It then sends the response
     * back to the HTTP client.
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
        $app = $this;
        $request = $this['request'];
        $response = $this['response'];
        $this['router']->setBaseUrl($request->getUri()->getBasePath());

        // Set response HTTP caching callbacks to short-circuit app if necessary
        $response->onLastModified(function ($latestResponse, $time) use ($app, $request) {
            if ($time === strtotime($request->getHeader('IF_MODIFIED_SINCE'))) {
                $app->halt(304);
            }
        });
        $response->onEtag(function ($latestResponse, $etag) use ($app, $request) {
            if ($etagHeader = $request->getHeader('IF_NONE_MATCH')) {
                $etagList = preg_split('@\s*,\s*@', $etagHeader);
                if (in_array($etag, $etagList) || in_array('*', $etagList)) {
                    $app->halt(304);
                }
            }
        });

        // Traverse middleware stack and fetch updated response
        try {
            $response = $this->execMiddlewareStack($request, $response);
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
     * Invoke the app as the inner-most middleware
     *
     * This method implements the middleware interface. It receives
     * Request and Response objects, and it returns a Response object
     * after dispatching the Request object to the appropriate Route
     * callback routine.
     *
     * @param  RequestInterface  $request  The most recent Request object
     * @param  ResponseInterface $response The most recent Response object
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response)
    {
        // TODO: Inject request and response objects into hooks?
        try {
            $this->applyHook('slim.before');
            $dispatched = false;
            $matchedRoutes = $this['router']->getMatchedRoutes($request->getMethod(), $request->getUri()->getPath(), false);
            foreach ($matchedRoutes as $route) {
                try {
                    $this->applyHook('slim.before.dispatch');
                    $newResponse = $route->dispatch($request, $response);
                    $this->applyHook('slim.after.dispatch');
                    $dispatched = true;
                    break;
                } catch (Exception\Pass $e) {
                    continue;
                }
            }
            if (!$dispatched) {
                $newResponse = $this['notFoundHandler']($request, $response);
            }
        } catch (Exception\Stop $e) {
            $newResponse = $e->getResponse();
        }
        $this->applyHook('slim.after');

        return $newResponse;
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
     * @param  string            $method      The request method (e.g., GET, POST, PUT, etc.)
     * @param  string            $uri         The request URI path
     * @param  array             $headers     The request headers (key-value array)
     * @param  array             $cookies     The request cookies (key-value array)
     * @param  string            $bodyContent The request body
     * @return ResponseInterface
     */
    public function subRequest($method, $path, array $headers = array(), array $cookies = array(), $bodyContent = '')
    {
        $env = $this['environment'];
        $uri = Http\Uri::createFromEnvironment($env)->withPath($path);
        $headers = new Http\Headers($headers);
        $cookies = new Collection($cookies);
        $body = new Http\Body(fopen('php://temp', 'r+'));
        $body->write($bodyContent);
        $body->rewind();
        $request = new Http\Request($method, $uri, $headers, $cookies, $body);
        $response = $this['response'];

        return $this($request, $response);
    }

    /**
     * Finalize and send the HTTP response
     *
     * @param RequestInterface  $Request  The most recent Request object
     * @param ResponseInterface $response The most recent Response object
     */
    public function finalize(RequestInterface $request, ResponseInterface $response)
    {
        if (!$this->responded) {
            $this->responded = true;

            // Ecrypt flash and session data
            if (isset($_SESSION)) {
                $this['flash']->save();
                if ($this['settings']['session.encrypt'] === true) {
                    $this['session']->encrypt($this['crypt']);
                }
                $this['session']->save();
            }

            // Encrypt cookies
            if ($this['settings']['cookies.encrypt']) {
                $response = $response->withEncryptedCookies($this['crypt']);
            }

            // Send response
            $response = $response->finalize();
            $response->sendHeaders();
            if ($request->isHead() === false) {
                $response->sendBody();
            }
        }
    }
}
