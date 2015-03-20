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
use Pimple\ServiceProviderInterface;

/**
 * App
 *
 * This is the "application". It lets you define routes. It runs
 * your application. And it returns the serialized HTTP response
 * back to the HTTP client.
 */
class App extends \Pimple\Container
{
    use ResolveCallable;
    use MiddlewareAware;

    /**
     * The current Slim Framework version
     *
     * @var string
     */
    const VERSION = '3.0.0';

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
            $serverParams = new Collection($env->all());
            $body = new Http\Body(fopen('php://input', 'r'));

            return new Http\Request($method, $uri, $headers, $cookies, $serverParams, $body);
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
     * Add GET route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function get($pattern, $callable)
    {
        return $this->map(['GET'], $pattern, $callable);
    }

    /**
     * Add POST route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function post($pattern, $callable)
    {
        return $this->map(['POST'], $pattern, $callable);
    }

    /**
     * Add PUT route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function put($pattern, $callable)
    {
        return $this->map(['PUT'], $pattern, $callable);
    }

    /**
     * Add PATCH route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function patch($pattern, $callable)
    {
        return $this->map(['PATCH'], $pattern, $callable);
    }

    /**
     * Add DELETE route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function delete($pattern, $callable)
    {
        return $this->map(['DELETE'], $pattern, $callable);
    }

    /**
     * Add OPTIONS route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function options($pattern, $callable)
    {
        return $this->map(['OPTIONS'], $pattern, $callable);
    }

    /**
     * Add route with multiple methods
     *
     * @param  string[] $methods  Numeric array of HTTP method names
     * @param  string   $pattern  The route URI pattern
     * @param  mixed    $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function map(array $methods, $pattern, $callable)
    {
        if (!is_string($pattern)) {
            throw new \InvalidArgumentException('Route pattern must be a string');
        }

        $callable = $this->resolveCallable($callable);
        if ($callable instanceof \Closure) {
            $callable = $callable->bindTo($this);
        }

        $route = $this['router']->map($methods, $pattern, $callable);
        if ($route instanceof ServiceProviderInterface) {
            $route->register($this);
        }
        return $route;
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
     * Client Details / Reverse Proxy Handling
     *******************************************************************************/

    /**
     * Get the HTTP Client IP address, taking reverse proxies into account
     *
     * @param bool $returnAll If multiple XFF headers are given, return all addresses in an array.
     * @return array|string
     */
    public function getClientIp($returnAll = false)
    {
        $trustedProxies = Http\TrustedProxies::create($this['settings']['http.trusted_proxies'],
            $this['settings']['http.trusted_headers']);

        if(isset($this['environment'][$trustedProxies->getTrustedHeaderName(Http\TrustedProxies::HEADER_CLIENT_IP)]) and
            $trustedProxies->check($this['environment']['REMOTE_ADDR']))
        {
            $XFF = $this['environment'][$trustedProxies->getTrustedHeaderName(Http\TrustedProxies::HEADER_CLIENT_IP)];

            if(strpos($XFF, ", ") !== false)
            {
                $XFF = explode(", ", $XFF);

                if($returnAll === false)
                {
                    return trim(array_pop($XFF)); // If user only wants one IP, use the latest one - most secure since
                                                  // directly from the trusted proxy
                }
                else
                {
                    return array_map("trim", $XFF);
                }
            }
            else
            {
                // The X-Forwarded-For header only has a single IP address, and is immediately usable
                return trim($XFF);
            }
        }
        else
        {
            // Either there is no reverse proxy or it is fake or spoofed
            return $this['environment']['REMOTE_ADDR'];
        }
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
     * This method returns a new 3XX HTTP response object to specific URL.
     *
     * @param string $url    The destination URL
     * @param int    $status The HTTP redirect status code (optional)
     *
     * @return \Slim\Http\Response
     */
    public function redirect($url, $status = 302)
    {
        return $this['response']->withStatus($status)->withHeader('Location', $url);
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
        static $responded = false;

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
            if ($time === strtotime($request->getHeader('If-Modified-Since'))) {
                $app->halt(304);
            }
        });
        $response->onEtag(function ($latestResponse, $etag) use ($app, $request) {
            if ($etagHeader = $request->getHeader('If-None-Match')) {
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
        if (!$responded) {
            $responded = true;
            $response = $response->finalize();
            $response->sendHeaders();
            if (!$request->isHead()) {
                $response->sendBody();
            }
        }

        return $response;

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
        $routeInfo = $this['router']->dispatch($request);
        if ($routeInfo[0] === \FastRoute\Dispatcher::FOUND) {
            return $routeInfo[1]($request->withAttributes($routeInfo[2]), $response, $routeInfo[2]);
        }
        if ($routeInfo[0] === \FastRoute\Dispatcher::NOT_FOUND) {
            return $this['notFoundHandler']($request, $response);
        }
        if ($routeInfo[0] === \FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
            return $this['notAllowedHandler']($request, $response, $routeInfo[1]);
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
        $serverParams = new Collection($env->all());
        $body = new Http\Body(fopen('php://temp', 'r+'));
        $body->write($bodyContent);
        $body->rewind();
        $request = new Http\Request($method, $uri, $headers, $cookies, $serverParams, $body);
        $response = $this['response'];

        return $this($request, $response);
    }
}
