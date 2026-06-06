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
use Psr\Log\LoggerInterface;
use Slim\Interfaces\EmitterInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouterInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\ErrorExceptionMiddleware;
use Slim\Middleware\ExceptionLoggingMiddleware;
use Slim\Middleware\HtmlExceptionMiddleware;
use Slim\Middleware\JsonExceptionMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Routing\Route;
use Slim\Routing\RouteCollectionTrait;
use Slim\Routing\RouteGroup;

/**
 * App
 *
 * The main application class for Slim framework, responsible for routing, middleware handling, and
 * running the application. It provides methods for defining routes, adding middleware, and managing
 * the application's lifecycle, including handling HTTP requests and emitting responses.
 *
 * @api
 */
class App implements RequestHandlerInterface
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
    private RouterInterface $router;

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
        $this->router = $container->get(RouterInterface::class);
        $this->emitter = $container->get(EmitterInterface::class);
    }

    /**
     * Get the dependency injection container.
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Define a new route with the specified HTTP methods and URI pattern.
     *
     * @param array<string> $methods The HTTP methods the route should respond to
     * @param string $path The URI pattern for the route
     * @param callable|string $handler The route handler callable or controller method
     *
     * @return RouteInterface The newly created route instance
     */
    public function map(array $methods, string $path, callable|string $handler): RouteInterface
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
     * Set the base path used for routing.
     *
     * @param string $basePath The url base path
     */
    public function setBasePath(string $basePath): self
    {
        $this->router->setBasePath($basePath);

        return $this;
    }

    /**
     * Get the base path used for routing.
     */
    public function getBasePath(): string
    {
        return $this->router->getBasePath() ?? '';
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
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->router->addMiddleware($middleware);

        return $this;
    }

    /**
     * Add routing middleware.
     *
     * @return self
     */
    public function addRoutingMiddleware(): self
    {
        return $this
            ->add(RoutingMiddleware::class)
            ->add(EndpointMiddleware::class);
    }

    /**
     * Add a set of default error handling middleware.
     *
     * @param bool $displayErrorDetails
     * @param bool $logErrors
     * @param bool $logErrorDetails
     * @param LoggerInterface|null $logger
     *
     * @return self
     */
    public function addErrorMiddleware(
        bool $displayErrorDetails = false,
        bool $logErrors = true,
        bool $logErrorDetails = true,
        ?LoggerInterface $logger = null,
    ): self {
        $app = $this
            ->add(ErrorExceptionMiddleware::class)
            ->add($this->container->get(HtmlExceptionMiddleware::class)->withErrorDetails($displayErrorDetails))
            ->add(JsonExceptionMiddleware::class);

        if ($logErrors) {
            $loggingMiddleware = $this->container
                ->get(ExceptionLoggingMiddleware::class)
                ->withLogErrorDetails($logErrorDetails);

            if ($logger) {
                $loggingMiddleware = $loggingMiddleware->withLogger($logger);
            }

            $app->add($loggingMiddleware);
        }

        return $app;
    }

    /**
     * Run the Slim application.
     *
     * This method traverses the application's middleware stack, processes the incoming HTTP request,
     * and emits the resultant HTTP response to the client.
     * @param ?ServerRequestInterface $request
     */
    public function run(?ServerRequestInterface $request = null): void
    {
        if (!$request) {
            $request = $this->serverRequestCreator->createServerRequestFromGlobals();
        }

        $this->emitter->emit($this->handle($request));
    }

    /**
     * Handle an incoming HTTP request.
     *
     * This method processes the request through the application's middleware stack and router,
     * returning the resulting HTTP response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->requestHandler->handle($request);
    }
}
