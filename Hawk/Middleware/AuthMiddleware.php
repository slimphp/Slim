<?php
namespace Hawk\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuthMiddleware
{
	private $authHandler;

	public function __construct(callable $authHandler)
	{
		$this->authHandler = $authHandler;
	}

	/**
     * [__invoke description]
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  callable               $next     [description]
     * @return [type]                           [description]
     */
	public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
	{
		$route = $request->getAttribute('route');

		if ($route !== null)
			if ($route->isAuthenticated())
				$response = call_user_func($this->authHandler, $request, $response, $route->getArguments());

		return $next($request, $response);
	}
}
