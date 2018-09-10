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
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\RouterInterface;
use RuntimeException;

/**
 * Perform routing and store matched route to the request's attributes
 */
class RoutingMiddleware
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * RoutingMiddleware constructor.
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Invoke
     *
     * @param  ServerRequestInterface $request   PSR7 server request
     * @param  ResponseInterface      $response  PSR7 response
     * @param  callable               $next      Middleware callable
     * @return ResponseInterface                 PSR7 response
     *
     * @throws HttpNotFoundException
     * @throws HttpMethodNotAllowedException
     * @throws RuntimeException
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface {
        $request = $this->performRouting($request);
        return $next($request, $response);
    }

    /**
     * Perform routing
     *
     * @param  ServerRequestInterface $request   PSR7 server request
     * @return ServerRequestInterface
     *
     * @throws HttpNotFoundException
     * @throws HttpMethodNotAllowedException
     * @throws RuntimeException
     */
    public function performRouting(ServerRequestInterface $request): ServerRequestInterface
    {
        $routingResults = $this->router->dispatch($request);
        $routeStatus = $routingResults->getRouteStatus();

        switch ($routeStatus) {
            case Dispatcher::FOUND:
                $routeArguments = $routingResults->getRouteArguments();
                $route = $this->router->lookupRoute($routingResults->getRouteIdentifier());
                $route->prepare($request, $routeArguments);
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
