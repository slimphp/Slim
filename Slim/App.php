<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteResolver;
use Slim\Routing\RouteRunner;

/**
 * This is the primary class with which you instantiate,
 * configure, and run a Slim Framework application.
 */
class App extends RouteCollectorProxy implements RequestHandlerInterface
{
    /**
     * Current version
     *
     * @var string
     */
    public const VERSION = '4.0.0-beta';

    /**
     * @var MiddlewareDispatcher
     */
    protected $middlewareDispatcher;

    /**
     * @var RouteResolverInterface
     */
    protected $routeResolver;

    /**
     * @param ResponseFactoryInterface       $responseFactory
     * @param ContainerInterface|null        $container
     * @param CallableResolverInterface|null $callableResolver
     * @param RouteCollectorInterface|null   $routeCollector
     * @param RouteResolverInterface|null    $routeResolver
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ContainerInterface $container = null,
        CallableResolverInterface $callableResolver = null,
        RouteCollectorInterface $routeCollector = null,
        RouteResolverInterface $routeResolver = null
    ) {
        parent::__construct(
            $responseFactory,
            $callableResolver ?? new CallableResolver($container),
            $container,
            $routeCollector
        );

        $this->routeResolver = $routeResolver ?? new RouteResolver($this->routeCollector);
        $routeRunner = new RouteRunner($this->routeResolver);

        $this->middlewareDispatcher = new MiddlewareDispatcher($routeRunner, $container);
    }

    /**
     * @return RouteResolverInterface
     */
    public function getRouteResolver(): RouteResolverInterface
    {
        return $this->routeResolver;
    }

    /**
     * @param MiddlewareInterface|string|callable $middleware
     * @return self
     */
    public function add($middleware): self
    {
        $this->middlewareDispatcher->add($middleware);
        return $this;
    }

    /**
     * @param MiddlewareInterface $middleware
     * @return self
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middlewareDispatcher->addMiddleware($middleware);
        return $this;
    }

    /**
     * Run application
     *
     * This method traverses the application middleware stack and then sends the
     * resultant Response object to the HTTP client.
     *
     * @param ServerRequestInterface|null $request
     * @return void
     */
    public function run(ServerRequestInterface $request = null): void
    {
        if (!$request) {
            $serverRequestCreator = ServerRequestCreatorFactory::create();
            $request = $serverRequestCreator->createServerRequestFromGlobals();
        }

        $response = $this->handle($request);
        $responseEmitter = new ResponseEmitter();
        $responseEmitter->emit($response);
    }

    /**
     * Handle a request
     *
     * This method traverses the application middleware stack and then returns the
     * resultant Response object.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->middlewareDispatcher->handle($request);

        /**
         * This is to be in compliance with RFC 2616, Section 9.
         * If the incoming request method is HEAD, we need to ensure that the response body
         * is empty as the request may fall back on a GET route handler due to FastRoute's
         * routing logic which could potentially append content to the response body
         * https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
         */
        $method = strtoupper($request->getMethod());
        if ($method === 'HEAD') {
            $emptyBody = $this->responseFactory->createResponse()->getBody();
            return $response->withBody($emptyBody);
        }

        return $response;
    }
}
