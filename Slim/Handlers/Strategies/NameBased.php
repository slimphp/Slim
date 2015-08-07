<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Handlers\Strategies;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;
use Slim\Interfaces\InvocationStrategyInterface;

/**
 * Route callback strategy which maps the route argument names to the callable parameter names.
 */
class NameBased implements InvocationStrategyInterface
{
    /**
     * Inspect the signature of the route callable and match the route argument names with the
     * parameter names of the callable.
     *
     * @param array|callable         $callable
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param array                  $routeArguments
     * @throws RuntimeException
     *
     * @return mixed
     */
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ) {
        $routeArguments['req'] = $request;
        $routeArguments['request'] = $request;
        $routeArguments['res'] = $response;
        $routeArguments['response'] = $response;

        $reflection = is_array($callable)
            ? new ReflectionMethod($callable[0], $callable[1])
            : new ReflectionFunction($callable);

        $parameters = $reflection->getParameters();
        $arguments = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $routeArguments)) {
                $value = $routeArguments[$name];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            } else {
                throw new RuntimeException(
                    sprintf(
                        'Could not find a value for parameter "%s" (available arguments: %s).',
                        $name,
                        implode(', ', array_keys($routeArguments))
                    )
                );
            }

            $arguments[$parameter->getPosition()] = $value;
        }

        return call_user_func_array($callable, $arguments);
    }
}
