<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Mocks;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Mock object for Slim\Tests\AppTest
 */
class MockMiddlewareWithoutConstructor implements MiddlewareInterface
{
    public static $CalledCount = 0;

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $appendToOutput = $request->getAttribute('appendToOutput');
        if ($appendToOutput !== null) {
            $appendToOutput('Hello World');
        }

        static::$CalledCount++;

        return $handler->handle($request);
    }
}
