<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Mocks;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Http\Response;

/**
 * Mock object for Slim\Tests\CallableResolverTest
 */
class RequestHandlerTest implements RequestHandlerInterface
{
    public static $CalledCount = 0;
    public static $strategy = '';

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        static::$CalledCount++;

        // store the strategy that was used to call this handler - it's in the back trace
        $trace = debug_backtrace();
        if (isset($trace[1])) {
            static::$strategy = $trace[1]['class'];
        }

        $response = new Response();
        $response = $response->withHeader('Content-Type', 'text/plain');
        $response->write(static::$CalledCount);

        return $response;
    }
}
