<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim;

use BadMethodCallException;
use Closure;
use Exception;
use FastRoute\Dispatcher;
use Interop\Container\Exception\ContainerException;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Slim\Exception\InvalidMethodException;
use Slim\Exception\MethodNotAllowedException;
use Slim\Exception\NotFoundException;
use Slim\Exception\SlimException;
use Slim\Http\Body;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Uri;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouterInterface;
use Throwable;

class App
{
    use MiddlewareAwareTrait;

    /**
     * Current version
     *
     * @var string
     */
    const VERSION = '3.12.1';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface|array $container
     *
     * @throws InvalidArgumentException When no container is provided that implements ContainerInterface
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
     * Get container
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
     * @param  callable|string $callable The callback routine
     *
     * @return static
     */
    public function add($callable)
    {
        return $this->addMiddleware(new DeferredCallable($callable, $this->container));
    }

    /**
     * Calling a non-existent method on App checks to see if there's an item
     * in the container that is callable and if so, calls it.
     *
     * @param  string $method
     * @param  array  $args
     *
     * @return mixed
     *
     * @throws BadMethodCallException
     * @throws ContainerException
     */
    public function __call($method, $args)
    {
        if ($this->container->has($method)) {
            $obj = $this->container->get($method);
            if (is_callable($obj)) {
                return call_user_func_array($obj, $args);
            }
        }

        throw new BadMethodCallException("Method $method is not a valid method");
    }

    /**
     * Add GET route
     *
     * @param  string          $pattern  The route URI pattern
     * @param  callable|string $callable The route callback routine
     *
     * @return RouteInterface
     *
     * @throws ContainerException
     */
    public function get($pattern, $callable)
    {
        return $this->map(['GET'], $pattern, $callable);
    }

    /**
     * Add POST route
     *
     * @param  string          $pattern  The route URI pattern
     * @param  callable|string $callable The route callback routine
     *
     * @return RouteInterface
     *
     * @throws ContainerException
     */
    public function post($pattern, $callable)
    {
        return $this->map(['POST'], $pattern, $callable);
    }

    /**
     * Add PUT route
     *
     * @param  string          $pattern  The route URI pattern
     * @param  callable|string $callable The route callback routine
     *
     * @return RouteInterface
     *
     * @throws ContainerException
     */
    public function put($pattern, $callable)
    {
        return $this->map(['PUT'], $pattern, $callable);
    }

    /**
     * Add PATCH route
     *
     * @param  string          $pattern  The route URI pattern
     * @param  callable|string $callable The route callback routine
     *
     * @return RouteInterface
     *
     * @throws ContainerException
     */
    public function patch($pattern, $callable)
    {
        return $this->map(['PATCH'], $pattern, $callable);
    }

    /**
     * Add DELETE route
     *
     * @param  string          $pattern  The route URI pattern
     * @param  callable|string $callable The route callback routine
     *
     * @return RouteInterface
     *
     * @throws ContainerException
     */
    public function delete($pattern, $callable)
    {
        return $this->map(['DELETE'], $pattern, $callable);
    }

    /**
     * Add OPTIONS route
     *
     * @param  string          $pattern  The route URI pattern
     * @param  callable|string $callable The route callback routine
     *
     * @return RouteInterface
     *
     * @throws ContainerException
     */
    public function options($pattern, $callable)
    {
        return $this->map(['OPTIONS'], $pattern, $callable);
    }

    /**
     * Add route for any HTTP method
     *
     * @param  string $pattern The route URI pattern
     * @param  callable|string $callable The route callback routine
     *
     * @return RouteInterface
     *
     * @throws ContainerException
     */
    public function any($pattern, $callable)
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callable);
    }

    /**
     * Add route with multiple methods
     *
     * @param  string[]        $methods  Numeric array of HTTP method names
     * @param  string          $pattern  The route URI pattern
     * @param  callable|string $callable The route callback routine
     *
     * @return RouteInterface
     *
     * @throws ContainerException
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
     * Add a route that sends an HTTP redirect
     *
     * @param string              $from
     * @param string|UriInterface $to
     * @param int                 $status
     *
     * @return RouteInterface
     *
     * @throws ContainerException
     */
    public function redirect($from, $to, $status = 302)
    {
        $handler = function ($request, ResponseInterface $response) use ($to, $status) {
            return $response->withHeader('Location', (string)$to)->withStatus($status);
        };

        return $this->get($from, $handler);
    }

    /**
     * Add a route group
     *
     * This method accepts a route pattern and a callback. All route
     * declarations in the callback will be prepended by the group(s)
     * that it is in.
     *
     * @param string           $pattern
     * @param callable|Closure $callable
     *
     * @return RouteGroupInterface
     *
     * @throws ContainerException
     */
    public function group($pattern, $callable)
    {
        /** @var RouterInterface $router */
        $router = $this->container->get('router');

        /** @var RouteGroup $group */
        $group = $router->pushGroup($pattern, $callable);
        $group->setContainer($this->container);
        $group($this);

        $router->popGroup();
        return $group;
    }

    /**
     * Run application
     *
     * This method traverses the application middleware stack and then sends the
     * resultant Response object to the HTTP client.
     *
     * @param bool|false $silent
     *
     * @return ResponseInterface
     *
     * @throws Exception
     * @throws Throwable
     * @throws ContainerException
     */
    public function run($silent = false)
    {
        $response = $this->container->get('response');

        try {
            ob_start();
            $response = $this->process($this->container->get('request'), $response);
        } catch (InvalidMethodException $e) {
            $response = $this->processInvalidMethod($e->getRequest(), $response);
        } finally {
            $output = ob_get_clean();
        }

        if (!empty($output) && $response->getBody()->isWritable()) {
            $outputBuffering = $this->container->get('settings')['outputBuffering'];
            if ($outputBuffering === 'prepend') {
                // prepend output buffer content
                $body = new Http\Body(fopen('php://temp', 'r+'));
                $body->write($output . $response->getBody());
                $response = $response->withBody($body);
            } elseif ($outputBuffering === 'append') {
                // append output buffer content
                $response->getBody()->write($output);
            }
        }

        $response = $this->finalize($response);

        if (!$silent) {
            $this->respond($response);
        }

        return $response;
    }

    /**
     * Pull route info for a request with a bad method to decide whether to
     * return a not-found error (default) or a bad-method error, then run
     * the handler for that error, returning the resulting response.
     *
     * Used for cases where an incoming request has an unrecognized method,
     * rather than throwing an exception and not catching it all the way up.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     *
     * @throws ContainerException
     */
    protected function processInvalidMethod(ServerRequestInterface $request, ResponseInterface $response)
    {
        $router = $this->container->get('router');
        if (is_callable([$request->getUri(), 'getBasePath']) && is_callable([$router, 'setBasePath'])) {
            $router->setBasePath($request->getUri()->getBasePath());
        }

        $request = $this->dispatchRouterAndPrepareRoute($request, $router);
        $routeInfo = $request->getAttribute('routeInfo', [RouterInterface::DISPATCH_STATUS => Dispatcher::NOT_FOUND]);

        if ($routeInfo[RouterInterface::DISPATCH_STATUS] === Dispatcher::METHOD_NOT_ALLOWED) {
            return $this->handleException(
                new MethodNotAllowedException($request, $response, $routeInfo[RouterInterface::ALLOWED_METHODS]),
                $request,
                $response
            );
        }

        return $this->handleException(new NotFoundException($request, $response), $request, $response);
    }

    /**
     * Process a request
     *
     * This method traverses the application middleware stack and then returns the
     * resultant Response object.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     *
     * @throws Exception
     * @throws Throwable
     * @throws ContainerException
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

        return $response;
    }

    /**
     * Send the response to the client
     *
     * @param ResponseInterface $response
     *
     * @throws ContainerException
     */
    public function respond(ResponseInterface $response)
    {
        // Send response
        if (!headers_sent()) {
            // Headers
            foreach ($response->getHeaders() as $name => $values) {
                $first = stripos($name, 'Set-Cookie') === 0 ? false : true;
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), $first);
                    $first = false;
                }
            }

            // Set the status _after_ the headers, because of PHP's "helpful" behavior with location headers.
            // See https://github.com/slimphp/Slim/issues/1730

            // Status
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ), true, $response->getStatusCode());
        }

        // Body
        $request = $this->container->get('request');
        if (!$this->isEmptyResponse($response) && !$this->isHeadRequest($request)) {
            $body = $response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $settings = $this->container->get('settings');
            $chunkSize = $settings['responseChunkSize'];

            $contentLength = $response->getHeaderLine('Content-Length');
            if (!$contentLength) {
                $contentLength = $body->getSize();
            }


            if (isset($contentLength)) {
                $amountToRead = $contentLength;
                while ($amountToRead > 0 && !$body->eof()) {
                    $data = $body->read(min((int)$chunkSize, (int)$amountToRead));
                    echo $data;

                    $amountToRead -= strlen($data);

                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            } else {
                while (!$body->eof()) {
                    echo $body->read((int)$chunkSize);
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
     *
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     * @throws ContainerException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        // Get the route info
        $routeInfo = $request->getAttribute('routeInfo');

        /** @var RouterInterface $router */
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
     *
     * @return ResponseInterface
     *
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     * @throws ContainerException
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
     * @param RouterInterface        $router
     *
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
     *
     * @return ResponseInterface
     *
     * @throws RuntimeException
     * @throws ContainerException
     */
    protected function finalize(ResponseInterface $response)
    {
        // stop PHP sending a Content-Type automatically
        ini_set('default_mimetype', '');

        $request = $this->container->get('request');
        if ($this->isEmptyResponse($response) && !$this->isHeadRequest($request)) {
            return $response->withoutHeader('Content-Type')->withoutHeader('Content-Length');
        }

        // Add Content-Length header if `addContentLengthHeader` setting is set
        if (isset($this->container->get('settings')['addContentLengthHeader']) &&
            $this->container->get('settings')['addContentLengthHeader'] == true) {
            if (ob_get_length() > 0) {
                throw new RuntimeException("Unexpected data in output buffer. " .
                    "Maybe you have characters before an opening <?php tag?");
            }
            $size = $response->getBody()->getSize();
            if ($size !== null && !$response->hasHeader('Content-Length')) {
                $response = $response->withHeader('Content-Length', (string) $size);
            }
        }

        // clear the body if this is a HEAD request
        if ($this->isHeadRequest($request)) {
            return $response->withBody(new Body(fopen('php://temp', 'r+')));
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
     *
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
     * Helper method to check if the current request is a HEAD request
     *
     * @param RequestInterface $request
     *
     * @return bool
     */
    protected function isHeadRequest(RequestInterface $request)
    {
        return strtoupper($request->getMethod()) === 'HEAD';
    }

    /**
     * Call relevant handler from the Container if needed. If it doesn't exist,
     * then just re-throw.
     *
     * @param  Exception              $e
     * @param  ServerRequestInterface $request
     * @param  ResponseInterface      $response
     *
     * @return ResponseInterface
     *
     * @throws Exception If a handler is needed and not found
     * @throws ContainerException
     */
    protected function handleException(Exception $e, ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($e instanceof MethodNotAllowedException) {
            $handler = 'notAllowedHandler';
            $params = [$e->getRequest(), $e->getResponse(), $e->getAllowedMethods()];
        } elseif ($e instanceof NotFoundException) {
            $handler = 'notFoundHandler';
            $params = [$e->getRequest(), $e->getResponse(), $e];
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
     *
     * @throws Throwable
     * @throws ContainerException
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
}
