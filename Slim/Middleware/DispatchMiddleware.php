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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\RouterInterface;
use Slim\Route;
use Slim\RoutingResults;

/**
 * Class DispatchMiddleware
 * @package Slim\Middleware
 */
class DispatchMiddleware implements MiddlewareInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * DispatchMiddleware constructor.
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * This middleware is instantiated automatically in App::__construct()
     * It is at the very tip of the middleware queue meaning it will be executed
     * last and it detects whether or not routing has been performed in the user
     * defined middleware queue. In the event that the user did not perform routing
     * it is done here
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws HttpNotFoundException
     * @throws HttpMethodNotAllowedException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var RoutingResults|null $routingResults */
        $routingResults = $request->getAttribute('routingResults');

        // If routing hasn't been done, then do it now so we can dispatch
        if ($routingResults === null) {
            $routingMiddleware = new RoutingMiddleware($this->router);
            $request = $routingMiddleware->performRouting($request);
        }

        /** @var Route $route */
        $route = $request->getAttribute('route');
        return $route->run($request);
    }
}
