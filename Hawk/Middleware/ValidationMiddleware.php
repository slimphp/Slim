<?php
namespace Hawk\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Hawk\Route\Params;
use Hawk\Exception\NestedException;
use Hawk\Exception\ExceptionBase;

/**
 *
 */
class ValidationMiddleware
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
					$param->validate();
				}
				catch (ExceptionBase $e) {
					$exceptions[] = $e;
				}
			}

			if (count($exceptions) === 1)
				throw $exceptions[0];
			else if (count($exceptions) > 1)
				throw new NestedException($exceptions);

			// If execution got here, all route's required parameters are filtered, valid and all optional
			// parameters are either filtered and valid or were populated with their default value due to
			// being invalid after filtering. Assemble all arguments received in this route in the array
			// that will be passed to the controller.s
			foreach ($request->getAttribute('route')->getParams() as $param)
				$request->getAttribute('route')->setArgument($param->getName(), $param->getValue());
		}

		return $next($request, $response);
	}
}