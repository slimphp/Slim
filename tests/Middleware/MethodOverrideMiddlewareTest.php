<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Middleware;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Tests\TestCase;

/**
 * @covers \Slim\Middleware\MethodOverrideMiddleware
 */
class MethodOverrideMiddlewareTest extends TestCase
{
    public function testHeader()
    {
        $mw = new MethodOverrideMiddleware();

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $this->assertEquals('PUT', $request->getMethod());
            return $response;
        };
        Closure::bind($next, $this);

        $request = $this
            ->createServerRequest('/', 'POST')
            ->withHeader('X-Http-Method-Override', 'PUT');
        $response = $this->createResponse();

        $mw($request, $response, $next);
    }

    public function testBodyParam()
    {
        $mw = new MethodOverrideMiddleware();

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $this->assertEquals('PUT', $request->getMethod());
            return $response;
        };
        Closure::bind($next, $this);

        $request = $this
            ->createServerRequest('/', 'POST')
            ->withParsedBody(['_METHOD' => 'PUT']);
        $response = $this->createResponse();

        $mw($request, $response, $next);
    }

    public function testHeaderPreferred()
    {
        $mw = new MethodOverrideMiddleware();

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $this->assertEquals('DELETE', $request->getMethod());
            return $response;
        };
        Closure::bind($next, $this);

        $request = $this
            ->createServerRequest('/', 'POST')
            ->withHeader('X-Http-Method-Override', 'DELETE')
            ->withParsedBody((object) ['_METHOD' => 'PUT']);
        $response = $this->createResponse();

        $mw($request, $response, $next);
    }

    public function testNoOverride()
    {
        $mw = new MethodOverrideMiddleware();

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $this->assertEquals('POST', $request->getMethod());
            return $response;
        };
        Closure::bind($next, $this);

        $request = $this->createServerRequest('/', 'POST');
        $response = $this->createResponse();

        $mw($request, $response, $next);
    }
}
