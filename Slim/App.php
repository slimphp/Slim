<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim;

use Exception;
use Throwable;
use Closure;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Container\ContainerInterface;
use FastRoute\Dispatcher;
use Slim\Exception\SlimException;
use Slim\Exception\MethodNotAllowedException;
use Slim\Exception\NotFoundException;
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\Body;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Slim\Interfaces\Http\EnvironmentInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouterInterface;

/**
 * App
 *
 * This is the primary class with which you instantiate,
 * configure, and run a Slim Framework application.
 * The \Slim\App class also accepts Slim Framework middleware.
 *
 * @property-read array $settings App settings
 * @property-read EnvironmentInterface $environment
 * @property-read RequestInterface $request
 * @property-read ResponseInterface $response
 * @property-read RouterInterface $router
 * @property-read callable $errorHandler
 * @property-read callable $phpErrorHandler
 * @property-read callable $notFoundHandler function($request, $response)
 * @property-read callable $notAllowedHandler function($request, $response, $allowedHttpMethods)
 */
class App
{
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
     * @param ContainerInterface|array $container Either a ContainerInterface or an associative array of app settings
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
     * @param  callable|string    $callable The callback routine
     *
     * @return static
     */
    public function add($callable)
    {
        return $this->addMiddleware(new DeferredCallable($callable, $this->container));
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

        throw new \BadMethodCallException("Method $method is not a valid method");
    }

    /********************************************************************************
     * Router proxy methods
     *******************************************************************************/

    /**
     * Add GET route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
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
     * @param  callable|string  $callable The route callback routine
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
     * @param  callable|string  $callable The route callback routine
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
     * @param  callable|string  $callable The route callback routine
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
     * @param  callable|string  $callable The route callback routine
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
     * @param  callable|string  $callable The route callback routine
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
     * @param  callable|string  $callable The route callback routine
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
     * @param  callable|string    $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function map(array $methods, $pattern, $callable)
    {
        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this->container);
        }

        $route = $this->container->get('router')->map($methods, $pattern, $callable);
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
     *
     * @throws Exception
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function run($silent = false, ServerRequestInterface $request = null, ResponseInterface $response = null)
    {
        if (!$request) {
            $request = $this->createRequest();
        }

        if (!$response) {
            $response = $this->createResponse();
        }

        $response = $this->process($request, $response);

        if (!$silent) {
            $this->respond($response);
        }

        return $response;
    }

    /**
     * Process a request
     *
     * This method traverses the application middleware stack and then returns the
     * resultant Response object.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     *
     * @throws Exception
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function process(ServerRequestInterface $request, ResponseInterface $response)
    {
        // Ensure basePath is set
        $router = $this->container->get('router');
        if (is_callable([$request->getUri(), 'getBasePath']) && is_callable([$router, 'setBasePath'])) {
            $router->setBasePath($request->getUri()->getBasePath());
        }

        // Dispatch the Router first if the setting for this is on
        if ($this->container->get('settings')['determineRouteBeforeAppMiddleware'] === true) {
            // Dispatch router (note: you won't be able to alter routes after this)
            $request = $this->dispatchRouterAndPrepareRoute($request, $router);
        }

        // Traverse middleware stack
        try {
            $response = $this->callMiddlewareStack($request, $response);
        } catch (Exception $e) {
            $response = $this->handleException($e, $request, $response);
        } catch (Throwable $e) {
            $response = $this->handlePhpError($e, $request, $response);
        }

        $response = $this->finalize($response);

        return $response;
    }

    /**
     * Send the response the client
     *
     * @param ResponseInterface $response
     */
    public function respond(ResponseInterface $response)
    {
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
            if (isset($contentLength)) {
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
            } else {
                while (!$body->eof()) {
                    echo $body->read($chunkSize);
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            }
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
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        // Get the route info
        $routeInfo = $request->getAttribute('routeInfo');

        /** @var \Slim\Interfaces\RouterInterface $router */
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
    public function subRequest(
        $method,
        $path,
        $query = '',
        array $headers = [],
        array $cookies = [],
        $bodyContent = '',
        ResponseInterface $response = null
    ) {
        $env = new Environment($_SERVER);
        $uri = Uri::createFromEnvironment($env)->withPath($path)->withQuery($query);
        $headers = new Headers($headers);
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($bodyContent);
        $body->rewind();
        $request = new Request($method, $uri, $headers, $cookies, $serverParams, $body);

        if (!$response) {
            $response = $this->createResponse();
        }

        return $this($request, $response);
    }

    /**
     * Dispatch the router to find the route. Prepare the route for use.
     *
     * @param ServerRequestInterface $request
     * @param RouterInterface        $router
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
        // stop PHP sending a Content-Type automatically
        ini_set('default_mimetype', '');

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
        if (method_exists($response, 'isEmpty')) {
            return $response->isEmpty();
        }

        return in_array($response->getStatusCode(), [204, 205, 304]);
    }

    /**
     * Call relevant handler from the Container if needed. If it doesn't exist,
     * then just re-throw.
     *
     * @param  Exception $e
     * @param  ServerRequestInterface $request
     * @param  ResponseInterface $response
     *
     * @return ResponseInterface
     * @throws Exception if a handler is needed and not found
     */
    protected function handleException(Exception $e, ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($e instanceof MethodNotAllowedException) {
            $handler = 'notAllowedHandler';
            $params = [$e->getRequest(), $e->getResponse(), $e->getAllowedMethods()];
        } elseif ($e instanceof NotFoundException) {
            $handler = 'notFoundHandler';
            $params = [$e->getRequest(), $e->getResponse()];
        } elseif ($e instanceof SlimException) {
            // This is a Stop exception and contains the response
            return $e->getResponse();
        } else {
            // Other exception, use $request and $response params
            $handler = 'errorHandler';
            $params = [$request, $response, $e];
        }

        if ($this->container->has($handler)) {
            $callable = $this->container->get($handler);
            // Call the registered handler
            return call_user_func_array($callable, $params);
        }

        // No handlers found, so just throw the exception
        throw $e;
    }

    /**
     * Call relevant handler from the Container if needed. If it doesn't exist,
     * then just re-throw.
     *
     * @param  Throwable $e
     * @param  ServerRequestInterface $request
     * @param  ResponseInterface $response
     *
     * @return ResponseInterface
     * @throws Exception if a handler is needed and not found
     */
    protected function handlePhpError(Throwable $e, ServerRequestInterface $request, ResponseInterface $response)
    {
        $handler = 'phpErrorHandler';
        $params = [$request, $response, $e];

        if ($this->container->has($handler)) {
            $callable = $this->container->get($handler);
            // Call the registered handler
            return call_user_func_array($callable, $params);
        }

        // No handlers found, so just throw the exception
        throw $e;
    }

    /**
     * Create request object. First look in the container. If one has been
     * registered, then use it. Otherwise, create one and register it into
     * the container with a deprecation notice.
     *
     * @return ServerRequestInterface
     */
    protected function createRequest()
    {
        if ($this->container->has('request')) {
            // a request has been registered with the container, so use it.
            return $this->container->get('request');
        }

        if ($this->container->has('environment')) {
            $environment = $this->container->get('environment');
        } else {
            $environment = new Environment($_SERVER);

            // register our new environment with the container for BC
            if ($this->container instanceof \ArrayAccess) {
                if (!$this->container->has('environment')) {
                    $container['environment'] = function () use ($environment) {
                        trigger_error(
                            'Retrieving the environment from the container is deprecated; '
                            . 'update your code to use the one within the Request object.',
                            E_USER_DEPRECATED
                        );
                        return $environment;
                    };
                }
            }
        }

        $request = Request::createFromEnvironment($environment);

        // register our new request with the container for BC
        if ($this->container instanceof \ArrayAccess) {
            $container['request'] = function ($container) use ($request) {
                trigger_error(
                    'Retrieving the request from the container is deprecated; '
                    . 'update your code to use the Request object that is passed through the middleware. '
                    . 'To use your own Request object, pass it in as the second parameter to run().',
                    E_USER_DEPRECATED
                );
                return $request;
            };
        }

        return $request;
    }

    /**
     * Create response object. First look in the container. If one has been
     * registered, then use it. Otherwise, create one and register it into
     * the container with a deprecation notice.
     *
     * @return ServerRequestInterface
     */
    protected function createResponse()
    {
        if ($this->container->has('response')) {
            // a response has been registered with the container, so use it.
            return $this->container->get('response');
        }

        $headers = new Headers(['Content-Type' => 'text/html; charset=UTF-8']);
        $response = new Response(200, $headers);
        $response = $response->withProtocolVersion($this->container->get('settings')['httpVersion']);

        // register our new response with the container for BC
        if ($this->container instanceof \ArrayAccess) {
            $container['response'] = function ($container) use ($response) {
                trigger_error(
                    'Retrieving the response from the container is deprecated; '
                    . 'update your code to use the Response object that is passed through the middleware. '
                    . 'To use your own Response object, pass it in as the third parameter to run().',
                    E_USER_DEPRECATED
                );

                return $response;
            };
        }

        return $response;
    }
}
