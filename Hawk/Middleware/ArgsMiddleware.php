<?php
namespace Hawk\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Hawk\Exception\InvalidArgumentException;
use Hawk\Exception\NestedException;

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
        $route = $request->getAttribute('route');
        if ($route !== null)
		{
			$exceptions = [];

			foreach ($route->getParams() as $param)
            {
				try {
					$param->checkRequest($request);

                    $param->filter();

                    $param->validate();
				}
                catch (InvalidArgumentException $e) {
					$exceptions[] = $e;
				}
			}

			if (count($exceptions) === 1)
				throw $exceptions[0];
			else if (count($exceptions) > 1)
				throw new NestedException($exceptions);

            // If execution got here, all route's required parameters are filtered, valid and all optional
			// parameters are either filtered and valid or were assigned their default value due to being
            // empty after filtering. Assemble all arguments received in this route in the array that will
            // be passed to the user provided route handler.
			foreach ($route->getParams() as $param)
				$route->setArgument($param->getName(), $param->getValue());
		}

        return $next($request, $response);
    }
}
