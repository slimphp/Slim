<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use Exception;
use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Container\ContainerInterface;
use FastRoute\Dispatcher;
use Slim\Container;
use Slim\Exception\Exception as SlimException;
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\Body;
use Slim\Http\Request;

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
    use CallableResolverAwareTrait;
    use MiddlewareAwareTrait {
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
     * @throws Exception when no container is provided that implements ContainerInterface
     */
    public function __construct($container = [])
    {
        if (is_array($container)) {
            $container = new Container($container);
        }
        if (!$container instanceof ContainerInterface) {
            throw new Exception("Expected a ContainerInterface");
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
     * @return RouteInterface
     */
    public function add($callable)
    {
        $callable = $this->resolveCallable($callable);
        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this->container);
        }

        return $this->addMiddleware($callable);
    }

    /********************************************************************************
     * Container proxy methods
     *******************************************************************************/

    public function __get($name)
    {
        return $this->container->get($name);
    }

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
        if ($callable instanceof Closure) {
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
     * Send the response the client
     *
     * @param ResponseInterface $response
     */
    public function respond(ResponseInterface $response)
    {
        static $responded = false;

        if (!$responded) {
            // Finalize response
            $statusCode = $response->getStatusCode();
            $hasBody = ($statusCode !== 204 && $statusCode !== 304);
            if ($hasBody) {
                $size = $response->getBody()->getSize();
                if ($size !== null) {
                    $response = $response->withHeader('Content-Length', $size);
                }
            } else {
                $response = $response->withoutHeader('Content-Type')->withoutHeader('Content-Length');
            }

            // Send response
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
                    while (!$body->eof()) {
                        $settings = $this->container->get('settings');
                        echo $body->read($settings['responseChunkSize']);
                    }
                }
            }
            $responded = true;
        }
    }

    /**
     * Run application
     *
     * This method traverses the application middleware stack and then sends the
     * resultant Response object to the HTTP client.
     */
    public function run()
    {
        $request = $this->container->get('request');
        $response = $this->container->get('response');

        // Traverse middleware stack
        try {
            $response = $this->callMiddlewareStack($request, $response);
        } catch (SlimException $e) {
            $response = $e->getResponse();
        } catch (Exception $e) {
            $errorHandler = $this->container->get('errorHandler');
            $response = $errorHandler($request, $response, $e);
        }

        $this->respond($response);
        
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
        if ($routeInfo[0] === Dispatcher::FOUND) {
            // URL decode the named arguments from the router
            $attributes = $routeInfo[2];
            foreach ($attributes as $k => $v) {
                $request = $request->withAttribute($k, urldecode($v));
            }
            return $routeInfo[1]($request, $response);
        } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            $notAllowedHandler = $this->container->get('notAllowedHandler');
            return $notAllowedHandler($request, $response, $routeInfo[1]);
        }

        $notFoundHandler = $this->container->get('notFoundHandler');
        return $notFoundHandler($request, $response);
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
     * @param  string            $query       The request URI query string
     * @param  array             $headers     The request headers (key-value array)
     * @param  array             $cookies     The request cookies (key-value array)
     * @param  string            $bodyContent The request body
     * @param  ResponseInterface $request     The response object (optional)
     * @return ResponseInterface
     */
    public function subRequest($method, $path, $query = '', array $headers = [], array $cookies = [], $bodyContent = '', ResponseInterface $response = null)
    {
        $env = $this->container->get('environment');
        $uri = Uri::createFromEnvironment($env)->withPath($path)->withQuery($query);
        $headers = new Headers($headers);
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($bodyContent);
        $body->rewind();
        $request = new Request($method, $uri, $headers, $cookies, $serverParams, $body);

        if (!$response) {
            $response = $this->container->get('response');
        }

        return $this($request, $response);
    }
}
