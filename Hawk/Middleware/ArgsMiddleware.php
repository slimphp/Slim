<?php
namespace Hawk\Middleware;

/**
 * ArgsMiddleware
 *
 * This middleware runs after the router has a match. It checks if the request contains
 * arguments for the required route's parameters. Then it filters these arguments and then
 * it validates them. Finally, it assembles all arguments(filtered and validated) in an
 * array that is passed to the route's handler.
 */
class ArgsMiddleware
{
    /**
     * [__invoke description]
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  callable               $next     [description]
     * @return [type]                           [description]
     */
	public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
	{
        return $next($request, $response);
    }
}
