<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use Psr\Http\Server\RequestHandlerInterface;
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Tests\TestCase;

class ContentLengthMiddlewareTest extends TestCase
{
    /**
     * Provide a boolean flag to indicate whether the test case should use the
     * advanced callable resolver or the non-advanced callable resolver
     *
     * @return array
     */
    public function useAdvancedCallableResolverDataProvider(): array
    {
        return [[true], [false]];
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testAddsContentLength(bool $useAdvancedCallableResolver)
    {
        $request = $this->createServerRequest('/');
        $responseFactory = $this->getResponseFactory();

        $mw = function ($request, $handler) use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response->getBody()->write('Body');
            return $response;
        };
        $mw2 = new ContentLengthMiddleware();

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandlerInterface::class),
            null,
            $useAdvancedCallableResolver
        );
        $middlewareDispatcher->addCallable($mw);
        $middlewareDispatcher->addMiddleware($mw2);
        $response = $middlewareDispatcher->handle($request);

        $this->assertEquals(4, $response->getHeaderLine('Content-Length'));
    }
}
