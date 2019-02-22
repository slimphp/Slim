<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\RouterInterface;
use Slim\Middleware\RoutingMiddleware;

/**
 * RouteDispatcher
 * @package Slim
 */
class RouteDispatcher implements RequestHandlerInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * This request handler is instantiated automatically in App::__construct()
     * It is at the very tip of the middleware queue meaning it will be executed
     * last and it detects whether or not routing has been performed in the user
     * defined middleware stack. In the event that the user did not perform routing
     * it is done here
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws HttpNotFoundException
     * @throws HttpMethodNotAllowedException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // If routing hasn't been done, then do it now so we can dispatch
        if ($request->getAttribute('routingResults') === null) {
            $routingMiddleware = new RoutingMiddleware($this->router);
            $request = $routingMiddleware->performRouting($request);
        }

        /** @var Route $route */
        $route = $request->getAttribute('route');
        return $route->run($request);
    }
}
