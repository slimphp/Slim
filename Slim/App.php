<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim;

use Closure;
use Exception;
use FastRoute\Dispatcher;
use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\InvalidMethodException;
use Slim\Exception\MethodNotAllowedException;
use Slim\Exception\NotFoundException;
use Slim\Handlers\Error;
use Slim\Handlers\NotAllowed;
use Slim\Handlers\NotFound;
use Slim\Handlers\PhpError;
use Slim\Http\Body;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\Http\EnvironmentInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouterInterface;
use Throwable;

/**
 * App
 *
 * This is the primary class with which you instantiate,
 * configure, and run a Slim Framework application.
 * The \Slim\App class also accepts Slim Framework middleware.
 */
class App
{
    use MiddlewareAwareTrait;

    /**
     * Container
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var \Slim\Interfaces\CallableResolverInterface
     */
    protected $callableResolver;

    /**
     * @var \Slim\Interfaces\RouterInterface
     */
    protected $router;

    /**
     * @var callable
     */
    protected $notFoundHandler;

    /**
     * @var callable
     */
    protected $notAllowedHandler;

    /**
     * @var callable
     */
    protected $errorHandler;

    /**
     * @var callable
     */
    protected $phpErrorHandler;

    /**
     * @var array
     */
    protected $settings = [
        'httpVersion' => '1.1',
        'responseChunkSize' => 4096,
        'outputBuffering' => 'append',
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails' => false,
        'addContentLengthHeader' => true,
        'routerCacheFile' => false,
    ];

    /********************************************************************************
     * Constructor
     *******************************************************************************/

    /**
     * Create new application
     *
     * @param array $settings
     */
    public function __construct(array $settings = [])
    {
        $this->addSettings($settings);
        $this->container = new Container();
    }

    /**
     * Get container
     *
     * @return ContainerInterface|null
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set container
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
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
        return $this->addMiddleware(
            new DeferredCallable($callable, $this->getCallableResolver())
        );
    }

    /**
     * Calling a non-existant method on App checks to see if there's an item
     * in the container that is callable and if so, calls it.
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     *
     * @throws BadMethodCallException
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
     * Settings management
     *******************************************************************************/

    /**
     * Does app have a setting with given key?
     *
     * @param string $key
     * @return bool
     */
    public function hasSetting($key)
    {
        return isset($this->settings[$key]);
    }

    /**
     * Get app settings
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Get app setting with given key
     *
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     */
    public function getSetting($key, $defaultValue = null)
    {
        return $this->hasSetting($key) ? $this->settings[$key] : $defaultValue;
    }

    /**
     * Merge a key-value array with existing app settings
     *
     * @param array $settings
     */
    public function addSettings(array $settings)
    {
        $this->settings = array_merge($this->settings, $settings);
    }

    /**
     * Add single app setting
     *
     * @param string $key
     * @param mixed $value
     */
    public function addSetting($key, $value)
    {
        $this->settings[$key] = $value;
    }

    /********************************************************************************
     * Setter and getter methods
     *******************************************************************************/

    /**
     * Set callable resolver
     *
     * @param CallableResolverInterface $resolver
     */
    public function setCallableResolver(CallableResolverInterface $resolver)
    {
        $this->callableResolver = $resolver;
    }

    /**
     * Get callable resolver
     *
     * @return CallableResolver|null
     */
    public function getCallableResolver()
    {
        if (! $this->callableResolver instanceof CallableResolverInterface) {
            $this->callableResolver = new CallableResolver($this->container);
        }

        return $this->callableResolver;
    }

    /**
     * Set router
     *
     * @param RouterInterface $router
     */
    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Get router
     *
     * @return RouterInterface
     */
    public function getRouter()
    {
        if (! $this->router instanceof RouterInterface) {
            $router = new Router();
            $resolver = $this->getCallableResolver();
            if ($resolver instanceof CallableResolverInterface) {
                $router->setCallableResolver($resolver);
            }
            $routerCacheFile = $this->getSetting('routerCacheFile', false);
            $router->setCacheFile($routerCacheFile);

            $this->router = $router;
        }

        return $this->router;
    }

    /**
     * Set callable to handle scenarios where a suitable
     * route does not match the current request.
     *
     * This service MUST return a callable that accepts
     * two arguments:
     *
     * 1. Instance of \Psr\Http\Message\ServerRequestInterface
     * 2. Instance of \Psr\Http\Message\ResponseInterface
     *
     * The callable MUST return an instance of
     * \Psr\Http\Message\ResponseInterface.
     *
     * @param callable $handler
     */
    public function setNotFoundHandler(callable $handler)
    {
        $this->notFoundHandler = $handler;
    }

    /**
     * Get callable to handle scenarios where a suitable
     * route does not match the current request.
     *
     * @return callable|Error
     */
    public function getNotFoundHandler()
    {
        if (!$this->notFoundHandler) {
            $this->notFoundHandler = new NotFound();
        }

        return $this->notFoundHandler;
    }

    /**
     * Set callable to handle scenarios where a suitable
     * route matches the request URI but not the request method.
     *
     * This service MUST return a callable that accepts
     * three arguments:
     *
     * 1. Instance of \Psr\Http\Message\ServerRequestInterface
     * 2. Instance of \Psr\Http\Message\ResponseInterface
     * 3. Array of allowed HTTP methods
     *
     * The callable MUST return an instance of
     * \Psr\Http\Message\ResponseInterface.
     *
     * @param callable $handler
     */
    public function setNotAllowedHandler(callable $handler)
    {
        $this->notAllowedHandler = $handler;
    }

    /**
     * Get callable to handle scenarios where a suitable
     * route matches the request URI but not the request method.
     *
     * @return callable|Error
     */
    public function getNotAllowedHandler()
    {
        if (!$this->notAllowedHandler) {
            $this->notAllowedHandler = new NotAllowed();
        }

        return $this->notAllowedHandler;
    }

    /**
     * Set callable to handle scenarios where an error
     * occurs when processing the current request.
     *
     * This service MUST return a callable that accepts three arguments:
     *
     * 1. Instance of \Psr\Http\Message\ServerRequestInterface
     * 2. Instance of \Psr\Http\Message\ResponseInterface
     * 3. Instance of \Error
     *
     * The callable MUST return an instance of
     * \Psr\Http\Message\ResponseInterface.
     *
     * @param callable $handler
     */
    public function setErrorHandler(callable $handler)
    {
        $this->errorHandler = $handler;
    }

    /**
     * Get callable to handle scenarios where an error
     * occurs when processing the current request.
     *
     * @return callable|Error
     */
    public function getErrorHandler()
    {
        if (!$this->errorHandler) {
            $this->errorHandler = new Error($this->getSetting('displayErrorDetails'));
        }

        return $this->errorHandler;
    }

    /**
     * Set callable to handle scenarios where a PHP error
     * occurs when processing the current request.
     *
     * This service MUST return a callable that accepts three arguments:
     *
     * 1. Instance of \Psr\Http\Message\ServerRequestInterface
     * 2. Instance of \Psr\Http\Message\ResponseInterface
     * 3. Instance of \Error
     *
     * The callable MUST return an instance of
     * \Psr\Http\Message\ResponseInterface.
     *
     * @param callable $handler
     */
    public function setPhpErrorHandler(callable $handler)
    {
        $this->phpErrorHandler = $handler;
    }

    /**
     * Get callable to handle scenarios where a PHP error
     * occurs when processing the current request.
     *
     * @return callable|Error
     */
    public function getPhpErrorHandler()
    {
        if (!$this->phpErrorHandler) {
            $this->phpErrorHandler = new PhpError($this->getSetting('displayErrorDetails'));
        }

        return $this->phpErrorHandler;
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
        // Bind route callable to container, if present
        if ($this->container instanceof ContainerInterface && $callable instanceof \Closure) {
            $callable = $callable->bindTo($this->container);
        }

        // Create route
        $route = $this->getRouter()->map($methods, $pattern, $callable);

        // TODO: Do we keep output buffering?
        if (is_callable([$route, 'setOutputBuffering'])) {
            $route->setOutputBuffering($this->getSetting('outputBuffering', 'append'));
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
        $router = $this->getRouter();
        $group = $router->pushGroup($pattern, $callable);
        if ($this->callableResolver instanceof CallableResolverInterface) {
            $group->setCallableResolver($this->callableResolver);
        }
        $group($this);
        $router->popGroup();

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
    public function run($silent = false)
    {
        $response = $this->container->get('response');

        try {
            $response = $this->process($this->container->get('request'), $response);
        } catch (InvalidMethodException $e) {
            $response = $this->processInvalidMethod($e->getRequest(), $response);
        }

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
     * @return ResponseInterface
     */
    protected function processInvalidMethod(ServerRequestInterface $request, ResponseInterface $response)
    {
        $router = $this->getRouter();
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
     * @return ResponseInterface
     *
     * @throws Exception
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function process(ServerRequestInterface $request, ResponseInterface $response)
    {
        $router = $this->getRouter();

        // Dispatch the Router first if the setting for this is on
        if ($this->getSetting('determineRouteBeforeAppMiddleware') === true) {
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
            $chunkSize = $this->getSetting('responseChunkSize', 4096);
            $contentLength  = $response->getHeaderLine('Content-Length');
            if (!$contentLength) {
                $contentLength = $body->getSize();
            }


            if (isset($contentLength)) {
                $amountToRead = $contentLength;
                while ($amountToRead > 0 && !$body->eof()) {
                    $data = $body->read(min($chunkSize, $amountToRead));
                    echo $data;

                    $amountToRead -= strlen($data);

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
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        // Get the route info
        $routeInfo = $request->getAttribute('routeInfo');

        /** @var \Slim\Interfaces\RouterInterface $router */
        $router = $this->getRouter();

        // If router hasn't been dispatched or the URI changed then dispatch
        if (null === $routeInfo || ($routeInfo['request'] !== [$request->getMethod(), (string) $request->getUri()])) {
            $request = $this->dispatchRouterAndPrepareRoute($request, $router);
            $routeInfo = $request->getAttribute('routeInfo');
        }

        if ($routeInfo[0] === Dispatcher::FOUND) {
            $route = $router->lookupRoute($routeInfo[1]);
            return $route->run($request, $response);
        } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            $notAllowedHandler = $this->getNotAllowedHandler();
            return $notAllowedHandler($request, $response, $routeInfo[1]);
        }

        $notFoundHandler = $this->getNotFoundHandler();
        return $notFoundHandler($request, $response);
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
     *
     * @throws RuntimeException
     */
    protected function finalize(ResponseInterface $response)
    {
        // stop PHP sending a Content-Type automatically
        ini_set('default_mimetype', '');

        if ($this->isEmptyResponse($response)) {
            return $response->withoutHeader('Content-Type')->withoutHeader('Content-Length');
        }

        // Add Content-Length header if `addContentLengthHeader` setting is set
        if ($this->getSetting('addContentLengthHeader') == true) {
            if (ob_get_length() > 0) {
                throw new \RuntimeException("Unexpected data in output buffer. " .
                    "Maybe you have characters before an opening <?php tag?");
            }
            $size = $response->getBody()->getSize();
            if ($size !== null && !$response->hasHeader('Content-Length')) {
                $response = $response->withHeader('Content-Length', (string) $size);
            }
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
            $handler = $this->getNotAllowedHandler();
            $params = [$e->getRequest(), $e->getResponse(), $e->getAllowedMethods()];
        } elseif ($e instanceof NotFoundException) {
            $handler = $this->getNotFoundHandler();
            $params = [$e->getRequest(), $e->getResponse()];
        } else {
            // Other exception, use $request and $response params
            $handler = $this->getErrorHandler();
            $params = [$request, $response, $e];
        }

        return call_user_func_array($handler, $params);
    }

    /**
     * Call relevant handler from the Container if needed. If it doesn't exist,
     * then just re-throw.
     *
     * @param  Throwable $e
     * @param  ServerRequestInterface $request
     * @param  ResponseInterface $response
     * @return ResponseInterface
     * @throws Throwable
     */
    protected function handlePhpError(Throwable $e, ServerRequestInterface $request, ResponseInterface $response)
    {
        $handler = $this->getPhpErrorHandler();
        $params = [$request, $response, $e];

        return call_user_func_array($handler, $params);
    }
}
