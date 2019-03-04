<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use FastRoute\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\RouteResolverInterface;

/**
 * Class RoutingMiddleware
 * @package Slim\Middleware
 */
class RoutingMiddleware implements MiddlewareInterface
{
    /**
     * @var RouteResolverInterface
     */
    protected $routeResolver;

    /**
     * RoutingMiddleware constructor.
     *
     * @param RouteResolverInterface $routeResolver
     */
    public function __construct(RouteResolverInterface $routeResolver)
    {
        $this->routeResolver = $routeResolver;
    }

    /**
     * @param ServerRequestInterface    $request
     * @param RequestHandlerInterface   $handler
     * @return ResponseInterface
     *
     * @throws HttpNotFoundException
     * @throws HttpMethodNotAllowedException
     * @throws RuntimeException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $this->performRouting($request);
        return $handler->handle($request);
    }

    /**
     * Perform routing
     *
     * @param  ServerRequestInterface $request   PSR7 Server Request
     * @return ServerRequestInterface
     *
     * @throws HttpNotFoundException
     * @throws HttpMethodNotAllowedException
     * @throws RuntimeException
     */
    public function performRouting(ServerRequestInterface $request): ServerRequestInterface
    {
        $routingResults = $this->routeResolver->computeRoutingResults(
            $request->getUri()->getPath(),
            $request->getMethod()
        );
        $routeStatus = $routingResults->getRouteStatus();

        switch ($routeStatus) {
            case Dispatcher::FOUND:
                $routeArguments = $routingResults->getRouteArguments();
                $routeIdentifier = $routingResults->getRouteIdentifier() ?? '';
                $route = $this->routeResolver
                    ->resolveRoute($routeIdentifier)
                    ->prepare($request, $routeArguments);
                return $request
                    ->withAttribute('route', $route)
                    ->withAttribute('routingResults', $routingResults);

            case Dispatcher::NOT_FOUND:
                throw new HttpNotFoundException($request);

            case Dispatcher::METHOD_NOT_ALLOWED:
                $exception = new HttpMethodNotAllowedException($request);
                $exception->setAllowedMethods($routingResults->getAllowedMethods());
                throw $exception;

            default:
                throw new RuntimeException('An unexpected error occurred while performing routing.');
        }
    }
}
