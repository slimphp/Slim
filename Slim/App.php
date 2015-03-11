<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * App
 *
 * This is the "application". It lets you define routes. It runs
 * your application. And it returns the serialized HTTP response
 * back to the HTTP client.
 */
class App extends \Pimple\Container
{
    use MiddlewareAware;

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

    /********************************************************************************
    * Instantiation and Configuration
    *******************************************************************************/

    /**
     * Create new application
     *
     * @param array $userSettings Associative array of application settings
     */
    public function __construct(array $userSettings = [])
    {
        parent::__construct();

        /**
         * Settings factory
         *
         * This factory method MUST return a SHARED singleton instance
         * of \Slim\Interfaces\ConfigurationInterface.
         */
        $this['settings'] = function ($c) use ($userSettings) {
            $config = new Configuration(new ConfigurationHandler);
            $config->setArray($userSettings);

            return $config;
        };

        /**
         * Environment factory
         *
         * This factory method MUST return a SHARED singleton instance
         * of \Slim\Interfaces\EnvironmentInterface.
         */
        $this['environment'] = function ($c) {
            return new Http\Environment($_SERVER);
        };

        /**
         * Request factory
         *
         * This factory method MUST return a NEW instance
         * of \Psr\Http\Message\RequestInterface.
         */
        $this['request'] = $this->factory(function ($c) {
            $env = $c['environment'];
            $method = $env['REQUEST_METHOD'];
            $uri = Http\Uri::createFromEnvironment($env);
            $headers = Http\Headers::createFromEnvironment($env);
            $cookies = new Collection(Http\Cookies::parseHeader($headers->get('Cookie')));
            $body = new Http\Body(fopen('php://input', 'r'));

            return new Http\Request($method, $uri, $headers, $cookies, $body);
        });

        /**
         * Response factory
         *
         * This factory method MUST return a NEW instance
         * of \Psr\Http\Message\ResponseInterface.
         */
        $this['response'] = $this->factory(function ($c) {
            $headers = new Http\Headers(['Content-Type' => 'text/html']);
            $cookies = new Http\Cookies([], [
                'expires' => $c['settings']['cookies.lifetime'],
                'path' => $c['settings']['cookies.path'],
                'domain' => $c['settings']['cookies.domain'],
                'secure' => $c['settings']['cookies.secure'],
                'httponly' => $c['settings']['cookies.httponly'],
            ]);
            $response = new Http\Response(200, $headers, $cookies);

            return $response->withProtocolVersion($c['settings']['http.version']);
        });

        /**
         * Router factory
         *
         * This factory method MUST return a SHARED singleton instance
         * of \Slim\Interfaces\RouterInterface.
         */
        $this['router'] = function ($c) {
            return new Router();
        };

        /**
         * View factory
         *
         * This factory method MUST return a SHARED singleton instance
         * of \Slim\Interfaces\ViewInterface.
         */
        $this['view'] = function ($c) {
            return new View($c['settings']['view.templates']);
        };

        /**
         * Session factory
         *
         * This factory method MUSt return a SHARED singleton instance
         * of \Slim\Interfaces\SessionInterface.
         */
        $this['session'] = function ($c) {
            $session = new Session($c['settings']['session.handler']);
            $session->start();

            return $session;
        };

        /**
         * Flash factory
         *
         * This factory method MUST return a SHARED singleton instance
         * of \Slim\Interfaces\FlashInterface.
         */
        $this['flash'] = function ($c) {
            $flash = new Flash($c['session'], $c['settings']['session.flash_key']);
            $c['view']->set('flash', $flash);

            return $flash;
        };

        /**
         * Error handler factory
         *
         * This factory method MUST return a callable
         * that accepts three arguments:
         *
         * 1. Instance of \Psr\Http\Message\RequestInterface
         * 2. Instance of \Psr\Http\Message\ResponseInterface
         * 3. Instance of \Exception
         *
         * The callable MUST return an instance of
         * \Psr\Http\Message\ResponseInterface.
         */
        $this['errorHandler'] = function ($c) {
            return new Handlers\Error;
        };

        /**
         * Not Found handler factory
         *
         * This factory method MUST return a callable
         * that accepts two arguments:
         *
         * 1. Instance of \Psr\Http\Message\RequestInterface
         * 2. Instance of \Psr\Http\Message\ResponseInterface
         *
         * The callable MUST return an instance of
         * \Psr\Http\Message\ResponseInterface.
         */
        $this['notFoundHandler'] = function ($c) {
            return new Handlers\NotFound;
        };

        /**
         * Not Allowed handler factory
         *
         * This factory method MUST return a callable
         * that accepts three arguments:
         *
         * 1. Instance of \Psr\Http\Message\RequestInterface
         * 2. Instance of \Psr\Http\Message\ResponseInterface
         * 3. Array of allowed HTTP methods
         *
         * The callable MUST return an instance of
         * \Psr\Http\Message\ResponseInterface.
         */
        $this['notAllowedHandler'] = function ($c) {
            return new Handlers\NotAllowed;
        };
    }

    /********************************************************************************
    * Router proxy methods
    *******************************************************************************/

    /**
     * Add route
     *
     * This method's second argument is a numeric array
     * with these elements:
     *
     * 1. (string) Route name
     * 2. (string) Route URI pattern
     * 3. (callable) One or more route middleware
     * 4. (callable) Route handler
     *
     * @param array $methods HTTP methods
     * @param array $args    See notes above
     *
     * @return Route
     */
    protected function mapRoute(array $methods, $args)
    {
        static $routeCount = 0;

        $name = array_pop($args);
        $pattern = array_shift($args);
        $callable = array_pop($args);
        if (empty($callable)) {
            $callable = $name;
            $name = strtolower(implode('.', $methods)) . $routeCount++;
        }

        $callable = $this->resolveCallable($callable);
        if ($callable instanceof \Closure) {
            $callable = $callable->bindTo($this);
        }
        $route = $this['router']->map($name, $methods, $pattern, $callable);
        if ($args) {
            foreach ($args as $arg) {
                $route->add($arg);
            }
        }

        return $route;
    }

    /**
     * Resolve a string of the format 'class:method' into a closure that the
     * router can dispatch.
     *
     * @param  string $callable
     *
     * @return Closure
     */
    protected function resolveCallable($callable)
    {
        if (is_string($callable) && preg_match('!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!', $callable, $matches)) {
            // $callable is a class:method string, so wrap it into a closure, retriving the class from Pimple if registered there
            $class = $matches[1];
            $method = $matches[2];
            $callable = function() use ($class, $method) {
                static $obj = null;
                if ($obj === null) {
                    if (isset($this[$class])) {
                        $obj = $this[$class];
                    } else {
                        if (!class_exists($class)) {
                            throw new \InvalidArgumentException('Route callable class does not exist');
                        }
                        $obj = new $class;
                        if (!method_exists($obj, $method)) {
                            throw new \InvalidArgumentException('Route callable method does not exist');
                        }
                    }
                }
                return call_user_func_array(array($obj, $method), func_get_args());
            };
        }

        return $callable;
    }

    /**
     * Add GET route
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function get()
    {
        $args = func_get_args();

        return $this->mapRoute(['GET'], $args);
    }

    /**
     * Add POST route
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function post()
    {
        $args = func_get_args();

        return $this->mapRoute(['POST'], $args);
    }

    /**
     * Add PUT route
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function put()
    {
        $args = func_get_args();

        return $this->mapRoute(['PUT'], $args);
    }

    /**
     * Add PATCH route
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function patch()
    {
        $args = func_get_args();

        return $this->mapRoute(['PATCH'], $args);
    }

    /**
     * Add DELETE route
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function delete()
    {
        $args = func_get_args();

        return $this->mapRoute(['DELETE'], $args);
    }

    /**
     * Add OPTIONS route
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function options()
    {
        $args = func_get_args();

        return $this->mapRoute(['OPTIONS'], $args);
    }

    /**
     * Add route for multiple methods
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function map()
    {
        $args = func_get_args();
        $methods = array_shift($args);
        if (!is_array($methods)) {
            throw new \InvalidArgumentException('First argument must be an array of HTTP methods');
        }

        return $this->mapRoute($methods, $args);
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

    /********************************************************************************
    * Application Behavior Methods
    *******************************************************************************/

    /**
     * Stop
     *
     * This method stops the application and sends the provided
     * Response object to the HTTP client.
     *
     * @param  ResponseInterface $response
     * @throws \Slim\Exception
     */
    public function stop(ResponseInterface $response)
    {
        throw new \Slim\Exception($response);
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
     *
     * @throws \Slim\Exception
     */
    public function halt($status, $message = '')
    {
        $response = $this['response']->withStatus($status);
        $response->write($message);
        $this->stop($response);
    }

    /**
     * Redirect
     *
     * This method immediately redirects to a new URL by preparing
     * and sending a new 3XX HTTP response object.
     *
     * @param string $url    The destination URL
     * @param int    $status The HTTP redirect status code (optional)
     *
     * @throws \Slim\Exception
     */
    public function redirect($url, $status = 302)
    {
        $this->stop($this['response']->withStatus($status)->withHeader('Location', $url));
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
            $response = $this->callMiddlewareStack($request, $response);
        } catch (\Slim\Exception $e) {
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
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response)
    {
        $routeInfo = $this['router']->dispatch($request, $response);
        if ($routeInfo[0] === \FastRoute\Dispatcher::NOT_FOUND) {
            return $this['notFoundHandler']($request, $response);
        }

        if ($routeInfo[0] === \FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
            return $this['notAllowedHandler']($request, $response, $routeInfo[1]);
        }

        if ($routeInfo[0] === \FastRoute\Dispatcher::FOUND) {
            return $routeInfo[1]($request->withAttributes($routeInfo[2]), $response, $routeInfo[2]);
        }
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
    public function subRequest($method, $path, array $headers = [], array $cookies = [], $bodyContent = '')
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

            // Persist flash and session data
            if (isset($_SESSION)) {
                $this['flash']->save();
                $this['session']->save();
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
