<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Mocks;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Tests\Providers\PSR7ObjectProvider;

use function debug_backtrace;

class RequestHandlerTest implements RequestHandlerInterface
{
    public static $CalledCount = 0;
    public static $strategy = '';

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        static::$CalledCount++;

        // store the strategy that was used to call this handler - it's in the back trace
        $trace = debug_backtrace();
        if (isset($trace[1])) {
            static::$strategy = $trace[1]['class'];
        }

        $psr7ObjectProvider = new PSR7ObjectProvider();
        $responseFactory = $psr7ObjectProvider->getResponseFactory();

        $response = $responseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'text/plain');
        $calledCount = static::$CalledCount;
        $response->getBody()->write("{$calledCount}");

        return $response;
    }

    public function custom(ServerRequestInterface $request): ResponseInterface
    {
        $psr7ObjectProvider = new PSR7ObjectProvider();
        $responseFactory = $psr7ObjectProvider->getResponseFactory();

        return $responseFactory->createResponse();
    }
}
