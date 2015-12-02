<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Handlers\Strategies;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;

class RequestResponseSI implements InvocationStrategyInterface
{
    /**
     * Invoke a route callable with all route parameters as individual arguments and
     * set request and response with "Setter Injection" to object.
     *
     * @param callable $callable The callable to invoke using the strategy.
     * @param ServerRequestInterface $request The request object.
     * @param ResponseInterface $response The response object.
     * @param array $routeArguments The route's placholder arguments
     *
     * @return ResponseInterface|string The response from the callable.
     */
    public function __invoke(callable $callable, ServerRequestInterface $request, ResponseInterface $response, array $routeArguments)
    {
        // Set request into the controller if we can
        if (method_exists($callable, 'setRequest')) {
            $callable->setRequest($request);
        }

        // Set response into the controller if we can
        if (method_exists($callable, 'setResponse')) {
            $callable->setResponse($response);
        }

        call_user_func_array($callable, $routeArguments);
    }
}
