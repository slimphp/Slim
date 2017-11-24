<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim;

use FastRoute\Dispatcher;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpNotAllowedException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Handlers\ErrorHandler;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouterInterface;
use Slim\Middleware\RoutingMiddleware;
use Exception;
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
     * @var array
     */
    protected $settings = [
        'httpVersion' => '1.1',
        'responseChunkSize' => 4096,
        'displayErrorDetails' => false,
        'routerCacheFile' => false,
        'defaultErrorHandler' => null,
        'errorHandlers' => []
    ];

    /********************************************************************************
     * Constructor
     *******************************************************************************/

    /**
     * Create new application
     *
     * @param array $settings
     */
    public function __construct(array $settings = [], ContainerInterface $container = null)
    {
        $this->addSettings($settings);
        $this->container = $container;
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
     * Set callable to handle scenarios where an error
     * occurs when processing the current request.
     *
     * This service MUST return a callable that accepts
     * three arguments optionally four arguments.
     *
     * 1. Instance of \Psr\Http\Message\ServerRequestInterface
     * 2. Instance of \Psr\Http\Message\ResponseInterface
     * 3. Instance of \Exception
     * 4. Boolean displayErrorDetails (optional)
     *
     * The callable MUST return an instance of
     * \Psr\Http\Message\ResponseInterface.
     *
     * @param string $type
     * @param callable $callable
     *
     * @throws \RuntimeException
     */
    public function setErrorHandler($type, $callable)
    {
        $resolver = $this->getCallableResolver();
        $handler = $resolver->resolve($callable);
        $handlers = $this->getErrorHandlers();
        $handlers[$type] = $handler;
        $this->addSetting('errorHandlers', $handlers);
    }

    /**
     * Get callable to handle scenarios where an error
     * occurs when processing the current request.
     *
     * @param string $type
     * @return callable
     *
     * @throws \RuntimeException
     */
    public function getErrorHandler($type)
    {
        $handlers = $this->getErrorHandlers();

        if (isset($handlers[$type])) {
            $handler = $handlers[$type];
            $resolver = $this->getCallableResolver();
            return $resolver->resolve($handler);
        }

        return $this->getDefaultErrorHandler();
    }

    /**
     * Retrieve error handler array from settings
     *
     * @returns array
     *
     * @throws \RuntimeException
     */
    protected function getErrorHandlers()
    {
        $handlers = $this->getSetting('errorHandlers', []);

        if (!is_array($handlers)) {
            throw new \RuntimeException('Slim application setting "errorHandlers" should be an array.');
        }

        return $handlers;
    }


    /**
     * Set callable as the default Slim application error handler.
     *
     * This service MUST return a callable that accepts
     * three arguments optionally four arguments.
     *
     * 1. Instance of \Psr\Http\Message\ServerRequestInterface
     * 2. Instance of \Psr\Http\Message\ResponseInterface
     * 3. Instance of \Exception
     * 4. Boolean displayErrorDetails (optional)
     *
     * The callable MUST return an instance of
     * \Psr\Http\Message\ResponseInterface.
     *
     * @param callable $callable
     *
     * @throws \RuntimeException
     */
    public function setDefaultErrorHandler($callable)
    {
        $resolver = $this->getCallableResolver();
        $handler = $resolver->resolve($callable);
        $this->addSetting('defaultErrorHandler', $handler);
    }

    /**
     * Get the default error handler from settings.
     *
     * @return callable|ErrorHandler
     */
    public function getDefaultErrorHandler()
    {
        $handler = $this->getSetting('defaultErrorHandler', null);

        if (!is_null($handler)) {
            $resolver = $this->getCallableResolver();
            return $resolver->resolve($handler);
        }

        return new ErrorHandler();
    }

    /**
     * Set callable to handle scenarios where a suitable
     * route does not match the current request.
     *
     * This service MUST return a callable that accepts
     * three arguments optionally four arguments.
     *
     * 1. Instance of \Psr\Http\Message\ServerRequestInterface
     * 2. Instance of \Psr\Http\Message\ResponseInterface
     * 3. Instance of \Exception
     * 4. Boolean displayErrorDetails (optional)
     *
     * The callable MUST return an instance of
     * \Psr\Http\Message\ResponseInterface.
     *
     * @param callable $handler
     */
    public function setNotFoundHandler($handler)
    {
        $this->setErrorHandler(HttpNotFoundException::class, $handler);
    }

    /**
     * Get callable to handle scenarios where a suitable
     * route does not match the current request.
     *
     * @return callable|ErrorHandlerInterface
     */
    public function getNotFoundHandler()
    {
        return $this->getErrorHandler(HttpNotFoundException::class);
    }

    /**
     * Set callable to handle scenarios where a suitable
     * route matches the request URI but not the request method.
     *
     * This service MUST return a callable that accepts
     * three arguments optionally four arguments.
     *
     * 1. Instance of \Psr\Http\Message\ServerRequestInterface
     * 2. Instance of \Psr\Http\Message\ResponseInterface
     * 3. Instance of \Exception
     * 4. Boolean displayErrorDetails (optional)
     *
     * The callable MUST return an instance of
     * \Psr\Http\Message\ResponseInterface.
     *
     * @param string|callable $handler
     */
    public function setNotAllowedHandler($handler)
    {
        $this->setErrorHandler(HttpNotAllowedException::class, $handler);
    }

    /**
     * Get callable to handle scenarios where a suitable
     * route matches the request URI but not the request method.
     *
     * @return callable
     */
    public function getNotAllowedHandler()
    {
        return $this->getErrorHandler(HttpNotAllowedException::class);
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
     * @return ResponseInterface
     * @throws Exception
     */
    public function run()
    {
        // create request
        $request = Request::createFromGlobals($_SERVER);

        // create response
        $headers = new Headers(['Content-Type' => 'text/html; charset=UTF-8']);
        $response = new Response(200, $headers);
        $response = $response->withProtocolVersion($this->getSetting('httpVersion'));

        // Traverse middleware stack
        $response = $this->process($request, $response);
        $this->respond($response);

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
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, ResponseInterface $response)
    {
        try {
            $response = $this->callMiddlewareStack($request, $response);
        } catch (Exception $e) {
            $response = $this->handleException($e, $request, $response);
        } catch (Throwable $e) {
            $response = $this->handleException($e, $request, $response);
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
            // Headers
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
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
            ));
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
        /**
         * Get the route info
         * If router hasn't been dispatched or the URI changed then dispatch
         *
         * @var \Slim\Interfaces\RouterInterface $router
         */
        $exception = null;
        $routeInfo = $request->getAttribute('routeInfo');
        $router = $this->getRouter();

        // If routing hasn't been done, then do it now so we can dispatch
        if (null === $routeInfo) {
            $routingMiddleware = new RoutingMiddleware($router);
            $request = $routingMiddleware->performRouting($request);
            $routeInfo = $request->getAttribute('routeInfo');
        }

        switch ($routeInfo[0]) {
            case Dispatcher::FOUND:
                $route = $router->lookupRoute($routeInfo[1]);
                return $route->run($request, $response);

            case Dispatcher::METHOD_NOT_ALLOWED:
                $exception = new HttpNotAllowedException;
                $exception->setAllowedMethods($routeInfo[1]);
                break;

            case Dispatcher::NOT_FOUND:
                $exception = new HttpNotFoundException;
                break;

            default:
                /**
                 * This case should never be triggered unless unforeseen
                 * circumstances with the Dispatcher occur.
                 */
                $exception = new HttpInternalServerErrorException;
                break;
        }

        if (!is_null($exception)) {
            $exception->setRequest($request);
        }

        return $this->handleException($exception, $request, $response);
    }

    /**
     * Resolve custom error handler from container or use default ErrorHandler
     * @param Exception|Throwable $exception
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return mixed
     */
    public function handleException($exception, ServerRequestInterface $request, ResponseInterface $response)
    {
        $exceptionType = get_class($exception);
        $handler = $this->getErrorHandler($exceptionType);
        $displayErrorDetails = $this->getSetting('displayErrorDetails');

        /**
         * Retrieve request object from exception
         * and replace current request object if not null
         */
        if (method_exists($exception, 'getRequest')) {
            $r = $exception->getRequest();
            if (!is_null($r)) {
                $request = $r;
            }
        }

        $params = [
            $request,
            $response,
            $exception,
            $displayErrorDetails
        ];

        return call_user_func_array($handler, $params);
    }

    /**
     * Finalize response
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     *
     * @throws \RuntimeException
     */
    protected function finalize(ResponseInterface $response)
    {
        // stop PHP sending a Content-Type automatically
        ini_set('default_mimetype', '');

        if ($this->isEmptyResponse($response)) {
            return $response->withoutHeader('Content-Type')->withoutHeader('Content-Length');
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
}
