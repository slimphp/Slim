<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Middleware\ClosureMiddleware;
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Middleware\Psr7MiddlewareAdapter;
use Slim\MiddlewareRunner;
use Slim\Tests\TestCase;

class ContentLengthMiddlewareTest extends TestCase
{
    public function testAddsContentLength()
    {
        $request = $this->createServerRequest('/');
        $responseFactory = $this->getResponseFactory();

        $mw = new ClosureMiddleware(function ($request, $handler) use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response->getBody()->write('Body');
            return $response;
        });
        $mw2 = new ContentLengthMiddleware();

        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->add($mw);
        $middlewareRunner->add($mw2);
        $response = $middlewareRunner->run($request);

        $this->assertEquals(4, $response->getHeaderLine('Content-Length'));
    }
}
