<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Middleware;

use FastRoute\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\RouterInterface;

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
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
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
     */
    public function performRouting(ServerRequestInterface $request)
    {
        $dispatcherResults = $this->router->dispatch($request);
        $routeStatus = $dispatcherResults->getRouteStatus();

        switch ($routeStatus) {
            default:
            case Dispatcher::FOUND:
                $routeArguments = $dispatcherResults->getRouteArguments();
                $route = $this->router->lookupRoute($dispatcherResults->getRouteHandler());
                $route->prepare($request, $routeArguments);
                return $request
                    ->withAttribute('route', $route)
                    ->withAttribute('dispatcherResults', $dispatcherResults);

            case Dispatcher::NOT_FOUND:
                throw new HttpNotFoundException($request);

            case Dispatcher::METHOD_NOT_ALLOWED:
                $exception = new HttpMethodNotAllowedException($request);
                $exception->setAllowedMethods($dispatcherResults->getAllowedMethods());
                throw $exception;
        }
    }
}
