<?php
namespace Slim\Middleware;

use FastRoute\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RouterInterface;

/**
 * Perform routing and store matched route to the request's attributes
 */
class RoutingMiddleware
{
    protected $router;

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
        $request = $this->doRouting($request);
        return $next($request, $response);
    }

    /**
     * Perform routing
     *
     * @param  ServerRequestInterface $request   PSR7 server request
     * @return ServerRequestInterface
     */
    public function doRouting(ServerRequestInterface $request)
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
        }

        // routeInfo to the request's attributes
        $routeInfo['request'] = [$request->getMethod(), (string) $request->getUri()];
        return $request->withAttribute('routeInfo', $routeInfo);
    }
}
