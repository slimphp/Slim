<?php
namespace Hawk\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Hawk\Route\Params;
use Hawk\Exception\NestedException;
use Hawk\Exception\InvalidParameterException;

/**
 *
 */
class FilteringMiddleware
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
		if ($request->getAttribute('route') !== null)
		{
			$exceptions = [];

			foreach ($request->getAttribute('route')->getParams() as $param) {
				try {
					$param->filter();
				}
				catch (InvalidParameterException $e) {
					$exceptions[] = $e;
				}
			}

			if (count($exceptions) === 1)
				throw $exceptions[0];
			else if (count($exceptions) > 1)
				throw new NestedException($exceptions);
		}

		return $next($request, $response);
	}
}