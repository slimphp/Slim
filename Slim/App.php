<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Container\ContainerInterface;

/**
 * App
 *
 * This is the primary class with which you instantiate,
 * configure, and run a Slim Framework application.
 * The \Slim\App class also accepts Slim Framework middleware.
 *
 * @property-read array $settings App settings
 * @property-read \Slim\Interfaces\Http\EnvironmentInterface $environment
 * @property-read \Psr\Http\Message\RequestInterface $request
 * @property-read \Psr\Http\Message\ResponseInterface $response
 * @property-read \Slim\Interfaces\RouterInterface $router
 * @property-read callable $errorHandler
 * @property-read callable function($request, $response) $notFoundHandler
 * @property-read callable function($request, $response, $allowedHttpMethods) $notAllowedHandler
 */
class App
{
    use ResolveCallable;
    use MiddlewareAware {
        add as addMiddleware;
    }

    /**
     * Current version
     *
     * @var string
     */
    const VERSION = '3.0.0';

    /**
     * Container
     *
     * @var ContainerInterface
     */
    private $container;

    /********************************************************************************
     * Constructor
     *******************************************************************************/

    /**
     * Create new application
     *
     * @param ContainerInterface|array $container Either a ContainerInterface or an associative array of application settings
     * @throws \Exception
     */
    public function __construct($container = [])
    {
        if (is_array($container)) {
            $container = new Container($container);
        }
        if (!$container instanceof ContainerInterface) {
            throw new \Exception("Expected a ContainerInterface");
        }
        $this->container = $container;
    }

    /**
     * Enable access to the DI container by consumers of $app
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Add middleware
     *
     * This method prepends new middleware to the route's middleware stack.
     *
     * @param  mixed    $callable The callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function add($callable)
    {
        $callable = $this->resolveCallable($callable);
        if ($callable instanceof \Closure) {
            $callable = $callable->bindTo($this->container);
        }

        return $this->addMiddleware($callable);
    }

    /********************************************************************************
     * Container proxy methods
     *******************************************************************************/

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->container->get($name);
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->container->has($name);
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
     * Add route for any HTTP method
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function any($pattern, $callable)
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callable);
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
        $callable = is_string($callable) ? $this->resolveCallable($callable) : $callable;
        if ($callable instanceof \Closure) {
            $callable = $callable->bindTo($this);
        }

        $route = $this->container->get('router')->map($methods, $pattern, $callable);
        if (method_exists($route, 'setContainer')) {
            $route->setContainer($this->container);
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
        $this->container->get('router')->pushGroup($pattern, $args);
        if (is_callable($callable)) {
            call_user_func($callable);
        }
        $this->container->get('router')->popGroup();
    }

    /********************************************************************************
     * Runner
     *******************************************************************************/

    /**
     * Run application
     *
     * This method traverses the application middleware stack,
     * and it returns the resultant Response object to the HTTP client.
     *
     * @return mixed|\Psr\Http\Message\MessageInterface|ResponseInterface
     */
    public function run()
    {
        static $responded = false;
        $request = $this->container->get('request');
        $response = $this->container->get('response');

        // Traverse middleware stack
        try {
            $response = $this->callMiddlewareStack($request, $response);
        } catch (\Slim\Exception $e) {
            $response = $e->getResponse();
        } catch (\Exception $e) {
            $errorHandler = $this->container->get('errorHandler');
            /* @var $errorHandler \Slim\Handlers\Error */
            $response = $errorHandler($request, $response, $e);
        }

        // Finalize response
        $statusCode = $response->getStatusCode();
        $hasBody = ($statusCode !== 204 && $statusCode !== 304);
        if (!$hasBody) {
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
            if ($hasBody) {
                $body = $response->getBody();
                if ($body->isAttached()) {
                    $body->rewind();
                    $settings = $this->container->get('settings');
                    while (!$body->eof()) {
                        echo $body->read($settings['responseChunkSize']);
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
     * @param  ServerRequestInterface $request  The most recent Request object
     * @param  ResponseInterface      $response The most recent Response object
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $routeInfo = $this->container->get('router')->dispatch($request);
        if ($routeInfo[0] === \FastRoute\Dispatcher::FOUND) {
            // URL decode the named arguments from the router
            $attributes = $routeInfo[2];
            foreach ($attributes as $k => $v) {
                $request = $request->withAttribute($k, urldecode($v));
            }
            return $routeInfo[1]($request, $response);
        }
        if ($routeInfo[0] === \FastRoute\Dispatcher::NOT_FOUND) {
            $notFoundHandler = $this->container->get('notFoundHandler');
            /* @var $notFoundHandler \Slim\Handlers\NotFound */
            return $notFoundHandler($request, $response);
        }
        if ($routeInfo[0] === \FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
            $notAllowedHandler = $this->container->get('notAllowedHandler');
            /* @var $notAllowedHandler \Slim\Handlers\NotAllowed */
            return $notAllowedHandler($request, $response, $routeInfo[1]);
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
     * @param  string            $path        The request URI path
     * @param  array             $headers     The request headers (key-value array)
     * @param  array             $cookies     The request cookies (key-value array)
     * @param  string            $bodyContent The request body
     * @return ResponseInterface
     */
    public function subRequest($method, $path, array $headers = [], array $cookies = [], $bodyContent = '')
    {

        $env = $this->container->get('environment');
        $uri = Http\Uri::createFromEnvironment($env)->withPath($path);
        $headers = new Http\Headers($headers);
        $serverParams = new Http\Collection($env->all());
        $body = new Http\Body(fopen('php://temp', 'r+'));
        $body->write($bodyContent);
        $body->rewind();
        $request = new Http\Request($method, $uri, $headers, $cookies, $serverParams, $body); //Collection implements the ArrayInterface
        $response = $this->container->get('response');

        return $this($request, $response);
    }
}
