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
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Tests\TestCase;

class ContentLengthMiddlewareTest extends TestCase
{
    public function testAddsContentLenght()
    {
        $mw = new ContentLengthMiddleware();

        $request = $this->createServerRequest('https://example.com:443/foo/bar?abc=123');
        $response = $this->createResponse();

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Body');
            return $response;
        };

        $newResponse = $mw($request, $response, $next);

        $this->assertEquals(4, $newResponse->getHeaderLine('Content-Length'));
    }
}
