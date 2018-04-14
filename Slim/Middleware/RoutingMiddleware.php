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
     * @throws HttpNotFoundException
     * @throws HttpMethodNotAllowedException
     */
    public function performRouting(ServerRequestInterface $request)
    {
        $routeInfo = $this->router->dispatch($request);

        if ($routeInfo[0] === Dispatcher::FOUND) {
            $routeArguments = [];
            foreach ($routeInfo[2] as $k => $v) {
                $routeArguments[$k] = urldecode($v);
            }

            $route = $this->router->lookupRoute($routeInfo[1]);
            $route->prepare($request, $routeArguments);

            // add route to the request's attributes
            $request = $request->withAttribute('route', $route);
        } elseif ($routeInfo[0] === Dispatcher::NOT_FOUND) {
            $exception = new HttpNotFoundException($request);
            throw $exception;
        } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            $exception = new HttpMethodNotAllowedException($request);
            $exception->setAllowedMethods($routeInfo[1]);
            throw $exception;
        }

        // routeInfo to the request's attributes
        $routeInfo['request'] = [$request->getMethod(), (string) $request->getUri()];
        return $request->withAttribute('routeInfo', $routeInfo);
    }
}
