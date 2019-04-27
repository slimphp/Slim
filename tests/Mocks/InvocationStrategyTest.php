<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Mocks;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;

class InvocationStrategyTest implements InvocationStrategyInterface
{
    public static $LastCalledFor = null;

    /**
     * {@inheritdoc}
     */
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ) {
        static::$LastCalledFor = $callable;

        return $response;
    }
}
