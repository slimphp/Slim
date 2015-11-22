<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Hawk;

use Exception;
use Closure;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Container\ContainerInterface;
use FastRoute\Dispatcher;
use Hawk\Exception\SlimException;
use Hawk\Exception\MethodNotAllowedException;
use Hawk\Exception\NotFoundException;
use Hawk\Http\Uri;
use Hawk\Http\Headers;
use Hawk\Http\Body;
use Hawk\Http\Request;
use Hawk\Interfaces\Http\EnvironmentInterface;
use Hawk\Interfaces\RouteGroupInterface;
use Hawk\Interfaces\RouteInterface;
use Hawk\Interfaces\RouterInterface;

/**
 * App
 *
 * This is the primary class with which you instantiate,
 * configure, and run a Slim Framework application.
 * The \Hawk\App class also accepts Slim Framework middleware.
 *
 * @property-read array $settings App settings
 * @property-read EnvironmentInterface $environment
 * @property-read RequestInterface $request
 * @property-read ResponseInterface $response
 * @property-read RouterInterface $router
 * @property-read callable $errorHandler
 * @property-read callable $notFoundHandler function($request, $response)
 * @property-read callable $notAllowedHandler function($request, $response, $allowedHttpMethods)
 */
class App
{
    use CallableResolverAwareTrait;
    use MiddlewareAwareTrait;

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
     * @throws InvalidArgumentException when no container is provided that implements ContainerInterface
     */
    public function __construct($container = [])
    {
        if (is_array($container)) {
            $container = new Container($container);
        }
        if (!$container instanceof ContainerInterface) {
            throw new InvalidArgumentException('Expected a ContainerInterface');
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
     * This method prepends new middleware to the app's middleware stack.
     *
     * @param  mixed    $callable The callback routine
     *
     * @return static
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

    /**
     * Calling a non-existant method on App checks to see if there's an item
     * in the container than is callable and if so, calls it.
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if ($this->container->has($method)) {
            $obj = $this->container->get($method);
            if (is_callable($obj)) {
                return call_user_func_array($obj, $args);
            }
        }
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
     * @return \Hawk\Interfaces\RouteInterface
     */
     public function get($pattern, $callable, $authenticated, array $params)
     {
         return $this->map(['GET'], $pattern, $callable, $authenticated, $params);
     }

     /**
      * Add POST route
      *
      * @param  string $pattern  The route URI pattern
      * @param  mixed  $callable The route callback routine
      *
      * @return \Slim\Interfaces\RouteInterface
      */
     public function post($pattern, $callable, $authenticated, array $params)
     {
         return $this->map(['POST'], $pattern, $callable, $authenticated, $params);
     }

     /**
      * Add PUT route
      *
      * @param  string $pattern  The route URI pattern
      * @param  mixed  $callable The route callback routine
      *
      * @return \Slim\Interfaces\RouteInterface
      */
     public function put($pattern, $callable, $authenticated, array $params)
     {
         return $this->map(['PUT'], $pattern, $callable, $authenticated, $params);
     }

     /**
      * Add PATCH route
      *
      * @param  string $pattern  The route URI pattern
      * @param  mixed  $callable The route callback routine
      *
      * @return \Slim\Interfaces\RouteInterface
      */
     public function patch($pattern, $callable, $authenticated, array $params)
     {
         return $this->map(['PATCH'], $pattern, $callable, $authenticated, $params);
     }

     /**
      * Add DELETE route
      *
      * @param  string $pattern  The route URI pattern
      * @param  mixed  $callable The route callback routine
      *
      * @return \Slim\Interfaces\RouteInterface
      */
     public function delete($pattern, $callable, $authenticated, array $params)
     {
         return $this->map(['DELETE'], $pattern, $callable, $authenticated, $params);
     }

     /**
      * Add OPTIONS route
      *
      * @param  string $pattern  The route URI pattern
      * @param  mixed  $callable The route callback routine
      *
      * @return \Slim\Interfaces\RouteInterface
      */
     public function options($pattern, $callable, $authenticated, array $params)
     {
         return $this->map(['OPTIONS'], $pattern, $callable, $authenticated, $params);
     }

     /**
      * Add route for any HTTP method
      *
      * @param  string $pattern  The route URI pattern
      * @param  mixed  $callable The route callback routine
      *
      * @return \Slim\Interfaces\RouteInterface
      */
     public function any($pattern, $callable, $authenticated, array $params)
     {
         return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callable, $authenticated, $params);
     }

     /**
      * Add route with multiple methods
      *
      * @param  string[] $methods  Numeric array of HTTP method names
      * @param  string   $pattern  The route URI pattern
      * @param  mixed    $callable The route callback routine
      *
      * @return RouteInterface
      */
     public function map(array $methods, $pattern, $callable, $authenticated, array $params)
     {
         if ($callable instanceof Closure) {
             $callable = $callable->bindTo($this);
         }

         $route = $this->container->get('router')->map($methods, $pattern, $callable, $authenticated, $params);
         if (is_callable([$route, 'setContainer'])) {
             $route->setContainer($this->container);
         }

         if (is_callable([$route, 'setOutputBuffering'])) {
             $route->setOutputBuffering($this->container->get('settings')['outputBuffering']);
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
     * @param string   $pattern
     * @param callable $callable
     *
     * @return RouteGroupInterface
     */
    public function group($pattern, $callable)
    {
        /** @var RouteGroup $group */
        $group = $this->container->get('router')->pushGroup($pattern, $callable);
        $group->setContainer($this->container);
        $group($this);
        $this->container->get('router')->popGroup();
        return $group;
    }

    /********************************************************************************
     * Runner
     *******************************************************************************/

    /**
     * Run application
     *
     * This method traverses the application middleware stack and then sends the
     * resultant Response object to the HTTP client.
     *
     * @param bool|false $silent
     * @return ResponseInterface
     */
    public function run($silent = false)
    {
        $request = $this->container->get('request');
        $response = $this->container->get('response');

        // Finalize routes here for middleware stack & ensure basePath is set
        $router = $this->container->get('router');
        $router->finalize();
        if (is_callable([$request->getUri(), 'getBasePath']) && is_callable([$router, 'setBasePath'])) {
            $router->setBasePath($request->getUri()->getBasePath());
        }

        // Add both framework middlewares
        $this->add(new AuthMiddleware($this->container->get('authHandler'))); // Only called for authenticated routes
        $this->add(new ArgsMiddleware()); // Called first

        // Dispatch the Router first if the setting for this is on
        if ($this->container->get('settings')['determineRouteBeforeAppMiddleware'] === true) {
            // Dispatch router (note: you won't be able to alter routes after this)
            $request = $this->dispatchRouterAndPrepareRoute($request, $router);
        }

        // Traverse middleware stack
        try {
            $response = $this->callMiddlewareStack($request, $response);
        } catch (MethodNotAllowedException $e) {
            if (!$this->container->has('notAllowedHandler')) {
                throw $e;
            }
            /** @var callable $notAllowedHandler */
            $notAllowedHandler = $this->container->get('notAllowedHandler');
            $response = $notAllowedHandler($e->getRequest(), $e->getResponse(), $e->getAllowedMethods());
        } catch (NotFoundException $e) {
            if (!$this->container->has('notFoundHandler')) {
                throw $e;
            }
            /** @var callable $notFoundHandler */
            $notFoundHandler = $this->container->get('notFoundHandler');
            $response = $notFoundHandler($e->getRequest(), $e->getResponse());
        } catch (SlimException $e) {
            $response = $e->getResponse();
        } catch (Exception $e) {
            if (!$this->container->has('errorHandler')) {
                throw $e;
            }
            /** @var callable $errorHandler */
            $errorHandler = $this->container->get('errorHandler');
            $response = $errorHandler($request, $response, $e);
        }

        $response = $this->finalize($response);

        if (!$silent) {
            $this->respond($response);
        }

        return $response;
    }

    /**
     * Send the response the client
     *
     * @param ResponseInterface $response
     */
    public function respond(ResponseInterface $response)
    {
        static $responded = false;

        if (!$responded) {
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
            if (!$this->isEmptyResponse($response)) {
                $body = $response->getBody();
                if ($body->isSeekable()) {
                    $body->rewind();
                }
                $settings       = $this->container->get('settings');
                $chunkSize      = $settings['responseChunkSize'];
                $contentLength  = $response->getHeaderLine('Content-Length');
                if (!$contentLength) {
                    $contentLength = $body->getSize();
                }
                $totalChunks    = ceil($contentLength / $chunkSize);
                $lastChunkSize  = $contentLength % $chunkSize;
                $currentChunk   = 0;
                while (!$body->eof() && $currentChunk < $totalChunks) {
                    if (++$currentChunk == $totalChunks && $lastChunkSize > 0) {
                        $chunkSize = $lastChunkSize;
                    }
                    echo $body->read($chunkSize);
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            }

            $responded = true;
        }
    }

    /**
     * Invoke application
     *
     * This method implements the middleware interface. It receives
     * Request and Response objects, and it returns a Response object
     * after compiling the routes registered in the Router and dispatching
     * the Request object to the appropriate Route callback routine.
     *
     * @param  ServerRequestInterface $request  The most recent Request object
     * @param  ResponseInterface      $response The most recent Response object
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        // Get the route info
        $routeInfo = $request->getAttribute('routeInfo');

        /** @var \Hawk\Interfaces\RouterInterface $router */
        $router = $this->container->get('router');

        // If router hasn't been dispatched or the URI changed then dispatch
        if (null === $routeInfo || ($routeInfo['request'] !== [$request->getMethod(), (string) $request->getUri()])) {
            $request = $this->dispatchRouterAndPrepareRoute($request, $router);
            $routeInfo = $request->getAttribute('routeInfo');
        }

        if ($routeInfo[0] === Dispatcher::FOUND) {
            $route = $router->lookupRoute($routeInfo[1]);
            return $route->run($request, $response);
        } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            if (!$this->container->has('notAllowedHandler')) {
                throw new MethodNotAllowedException($request, $response, $routeInfo[1]);
            }
            /** @var callable $notAllowedHandler */
            $notAllowedHandler = $this->container->get('notAllowedHandler');
            return $notAllowedHandler($request, $response, $routeInfo[1]);
        }

        if (!$this->container->has('notFoundHandler')) {
            throw new NotFoundException($request, $response);
        }
        /** @var callable $notFoundHandler */
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
     * @param  ResponseInterface $response     The response object (optional)
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

    /**
     * Dispatch the router to find the route. Prepare the route for use.
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function dispatchRouterAndPrepareRoute(ServerRequestInterface $request, RouterInterface $router)
    {
        $routeInfo = $router->dispatch($request);

        if ($routeInfo[0] === Dispatcher::FOUND) {
            $routeArguments = [];
            foreach ($routeInfo[2] as $k => $v) {
                $routeArguments[$k] = urldecode($v);
            }

            $route = $router->lookupRoute($routeInfo[1]);
            $route->prepare($request, $routeArguments);

            // add route to the request's attributes in case a middleware or handler needs access to the route
            $request = $request->withAttribute('route', $route);
        }

        $routeInfo['request'] = [$request->getMethod(), (string) $request->getUri()];

        return $request->withAttribute('routeInfo', $routeInfo);
    }

    /**
     * Finalize response
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function finalize(ResponseInterface $response)
    {
        if ($this->isEmptyResponse($response)) {
            return $response->withoutHeader('Content-Type')->withoutHeader('Content-Length');
        }

        $size = $response->getBody()->getSize();
        if ($size !== null && !$response->hasHeader('Content-Length')) {
            $response = $response->withHeader('Content-Length', (string) $size);
        }

        return $response;
    }

    /**
     * Helper method, which returns true if the provided response must not output a body and false
     * if the response could have a body.
     *
     * @see https://tools.ietf.org/html/rfc7231
     *
     * @param ResponseInterface $response
     * @return bool
     */
    protected function isEmptyResponse(ResponseInterface $response)
    {
        return in_array($response->getStatusCode(), [204, 205, 304]);
    }
}
