<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Body;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Middleware\ContentLengthMiddleware;

class ContentLengthMiddlewareTest extends TestCase
{
    public function testAddsContentLenght()
    {
        $mw = new ContentLengthMiddleware('append');

        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response();

        $next = function (ServerRequestInterface $req, ResponseInterface $res) {
            $res->write('Body');
            return $res;
        };

        $newResponse = $mw($request, $response, $next);

        $this->assertEquals(4, $newResponse->getHeaderLine('Content-Length'));
    }
}
