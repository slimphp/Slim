<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use Slim\Interfaces\Http\CookiesInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Pimple\ServiceProviderInterface;

/**
 * App
 *
 * This is the primary class with which you instantiate,
 * configure, and run a Slim Framework application. This
 * is also a \Pimple\Container instance, meaning you can
 * register custom Pimple service providers on each
 * \Slim\App instance. The \Slim\App class also accepts
 * Slim Framework middleware.
 */
class App extends \Pimple\Container
{
    use ResolveCallable;
    use MiddlewareAware;

    /**
     * Current version
     *
     * @var string
     */
    const VERSION = '3.0.0';

    /**
     * Default settings
     *
     * @var array
     */
    protected $defaultSettings = [
        'cookieLifetime' => '20 minutes',
        'cookiePath' => '/',
        'cookieDomain' => null,
        'cookieSecure' => false,
        'cookieHttpOnly' => false,
        'httpVersion' => '1.1',
        'responseChunkSize' => 4096
    ];

    /********************************************************************************
     * Constructor and default Pimple services
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
         * This Pimple service MUST return an array or an
         * instance of \ArrayAccess.
         */
        $this['settings'] = function ($c) use ($userSettings) {
            return array_merge($c->defaultSettings, $userSettings);
        };

        /**
         * This Pimple service MUST return a shared instance
         * of \Slim\Interfaces\EnvironmentInterface.
         */
        $this['environment'] = function ($c) {
            return new Http\Environment($_SERVER);
        };

        /**
         * This Pimple service MUST return a NEW instance
         * of \Psr\Http\Message\RequestInterface.
         */
        $this['request'] = $this->factory(function ($c) {
            $env = $c['environment'];
            $method = $env['REQUEST_METHOD'];
            $uri = Http\Uri::createFromEnvironment($env);
            $headers = Http\Headers::createFromEnvironment($env);
            $cookies = new Http\Collection(Http\Cookies::parseHeader($headers->get('Cookie', [])));
            $serverParams = new Http\Collection($env->all());
            $body = new Http\Body(fopen('php://input', 'r'));

            return new Http\Request($method, $uri, $headers, $cookies, $serverParams, $body);
        });

        /**
         * This Pimple service MUST return a NEW instance
         * of \Psr\Http\Message\ResponseInterface.
         */
        $this['response'] = $this->factory(function ($c) {
            $headers = new Http\Headers(['Content-Type' => 'text/html']);
            $response = new Http\Response(200, $headers);

            return $response->withProtocolVersion($c['settings']['httpVersion']);
        });

        /**
         * This Pimple service MUST return a SHARED instance
         * of \Slim\Interfaces\Http\CookiesInterface.
         */
        $this['cookies'] = function ($c) {
            $cookies = new Http\Cookies($c['request']->getCookieParams());
            $cookies->setDefaults([
                'expires' => $c['settings']['cookieLifetime'],
                'path' => $c['settings']['cookiePath'],
                'domain' => $c['settings']['cookieDomain'],
                'secure' => $c['settings']['cookieSecure'],
                'httponly' => $c['settings']['cookieHttpOnly']
            ]);

            return $cookies;
        };

        /**
         * This Pimple service MUST return a SHARED instance
         * of \Slim\Interfaces\RouterInterface.
         */
        $this['router'] = function ($c) {
            return new Router();
        };

        /**
         * This Pimple service MUST return a callable
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
         * This Pimple service MUST return a callable
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
         * This Pimple service MUST return a callable
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
     * Application flow methods
     *******************************************************************************/

    /**
     * Stop
     *
     * This method stops the application and sends the provided
     * Response object to the HTTP client.
     *
     * @param  ResponseInterface $response
     *
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

    /********************************************************************************
     * Runner
     *******************************************************************************/

    /**
     * Run application
     *
     * This method traverses the application middleware stack,
     * and it returns the resultant Response object to the HTTP client.
     */
    public function run()
    {
        static $responded = false;
        $request = $this['request'];
        $response = $this['response'];

        // Traverse middleware stack
        try {
            $response = $this->callMiddlewareStack($request, $response);
        } catch (\Slim\Exception $e) {
            $response = $e->getResponse();
        } catch (\Exception $e) {
            $response = $this['errorHandler']($request, $response, $e);
        }

        // Serialize cookies into Response
        if (!$this['cookies'] instanceof CookiesInterface) {
            throw new \RuntimeException('cookies service must return an instance of \Slim\Interfaces\Http\CookiesInterface');
        }

        $cookieHeaders = $this['cookies']->toHeaders();
        if ($cookieHeaders) {
            $response = $response->withAddedHeader('Set-Cookie', $cookieHeaders);
        }

        // Finalize response
        if (in_array($response->getStatusCode(), [204, 304])) {
            $response = $response->withoutHeader('Content-Type')->withoutHeader('Content-Length');
        } else {
            $size = $response->getBody()->getSize();
            if ($size !== null) {
                $response = $response->withHeader('Content-Length', $size);
            }
        }

        // Send response
        if (!$responded) {
            if (!headers_sent()) {
                // Status
                header(sprintf(
                    'HTTP/%s %s %s',
                    $response->getProtocolVersion(),
                    $response->getStatusCode(),
                    $response->getReasonPhrase()
                ));

                // Headers
                foreach ($response->getHeaders() as $name => $values) {
                    foreach ($values as $value) {
                        header(sprintf('%s: %s', $name, $value), false);
                    }
                }
            }

            // Body
            if (!in_array($response->getStatusCode(), [204, 304])) {
                $body = $response->getBody();
                if ($body->isAttached()) {
                    $body->rewind();
                    while (!$body->eof()) {
                        echo $body->read($this['settings']['responseChunkSize']);
                    }
                }
            }
            $responded = true;
        }

        return $response;
    }

    /**
     * Invoke application
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
        $serverParams = new Collection($env->all());
        $body = new Http\Body(fopen('php://temp', 'r+'));
        $body->write($bodyContent);
        $body->rewind();
        $request = new Http\Request($method, $uri, $headers, $cookies, $serverParams, $body);
        $response = $this['response'];

        return $this($request, $response);
    }
}
