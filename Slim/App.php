<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\EmitterInterface;
use Slim\Interfaces\RouteCollectionInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\RequestHandler\MiddlewareRequestHandler;
use Slim\Routing\Route;
use Slim\Routing\RouteCollectionTrait;
use Slim\Routing\RouteGroup;
use Slim\Routing\Router;

/**
 * App
 *
 * The main application class for Slim framework, responsible for routing, middleware handling, and
 * running the application. It provides methods for defining routes, adding middleware, and managing
 * the application's lifecycle, including handling HTTP requests and emitting responses.
 *
 * @template TContainerInterface of (ContainerInterface|null)
 *
 * @api
 */
class App implements RouteCollectionInterface
{
    use RouteCollectionTrait;

    /**
     * Current Slim Framework version.
     *
     * @var string
     */
    public const VERSION = '5.0.0-alpha';

    /**
     * The dependency injection container instance.
     */
    private ContainerInterface $container;

    /**
     * The server request creator instance.
     */
    private ServerRequestCreatorInterface $serverRequestCreator;

    /**
     * The request handler responsible for processing the request through middleware and routing.
     */
    private RequestHandlerInterface $requestHandler;

    /**
     * The router instance for handling route definitions and matching.
     */
    private Router $router;

    /**
     * The emitter instance for sending the HTTP response to the client.
     */
    private EmitterInterface $emitter;

    /**
     * The constructor.
     *
     * Initializes the Slim application with the provided container, request creator,
     * request handler, router, and emitter.
     *
     * @param ContainerInterface $container The dependency injection container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->serverRequestCreator = $container->get(ServerRequestCreatorInterface::class);
        $this->requestHandler = $container->get(RequestHandlerInterface::class);
        $this->router = $container->get(Router::class);
        $this->emitter = $container->get(EmitterInterface::class);
    }

    /**
     * Get the dependency injection container.
     *
     * @return ContainerInterface The DI container instance
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Define a new route with the specified HTTP methods and URI pattern.
     *
     * @param array $methods The HTTP methods the route should respond to
     * @param string $path The URI pattern for the route
     * @param callable|string $handler The route handler callable or controller method
     *
     * @return Route The newly created route instance
     */
    public function map(array $methods, string $path, callable|string $handler): Route
    {
        return $this->router->map($methods, $path, $handler);
    }

    /**
     * Define a route group with a common URI prefix and a set of routes or middleware.
     *
     * @param string $path The URI pattern prefix for the group
     * @param callable $handler The group handler which defines routes or middleware
     *
     * @return RouteGroup The newly created route group instance
     */
    public function group(string $path, callable $handler): RouteGroup
    {
        return $this->router->group($path, $handler);
    }

    /**
     * Get the base path used for routing.
     *
     * @return string The base path used for routing
     */
    public function getBasePath(): string
    {
        return $this->router->getBasePath();
    }

    /**
     * Set the base path used for routing.
     *
     * @param string $basePath The base path to use for routing
     *
     * @return self The current App instance for method chaining
     */
    public function setBasePath(string $basePath): self
    {
        $this->router->setBasePath($basePath);

        return $this;
    }

    /**
     * Add a new middleware to the stack.
     */
    public function add(MiddlewareInterface|callable|string $middleware): self
    {
        $this->router->add($middleware);

        return $this;
    }

    /**
     * Add a new middleware to the application's middleware stack.
     *
     * @param MiddlewareInterface $middleware The middleware to add
     *
     * @return self The current App instance for method chaining
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->router->addMiddleware($middleware);

        return $this;
    }

    /**
     * Run the Slim application.
     *
     * This method traverses the application's middleware stack, processes the incoming HTTP request,
     * and emits the resultant HTTP response to the client.
     *
     * @param ServerRequestInterface|null $request The HTTP request to handle.
     *                                             If null, it creates a request from globals.
     *
     * @return void
     */
    public function run(?ServerRequestInterface $request = null): void
    {
        if (!$request) {
            $request = $this->serverRequestCreator->createServerRequestFromGlobals();
        }

        $response = $this->handle($request);

        $this->emitter->emit($response);
    }

    /**
     * Handle an incoming HTTP request.
     *
     * This method processes the request through the application's middleware stack and router,
     * returning the resulting HTTP response.
     *
     * @param ServerRequestInterface $request The HTTP request to handle
     *
     * @return ResponseInterface The HTTP response
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = $request->withAttribute(MiddlewareRequestHandler::MIDDLEWARE, $this->router->getMiddlewareStack());

        return $this->requestHandler->handle($request);
    }
}
