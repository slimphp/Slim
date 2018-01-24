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
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\RequestBody;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Middleware\MethodOverrideMiddleware;

/**
 * @covers \Slim\Middleware\MethodOverrideMiddleware
 */
class MethodOverrideMiddlewareTest extends TestCase
{
    public function testHeader()
    {
        $mw = new MethodOverrideMiddleware();

        $uri = new Uri('http', 'example.com');
        $headers = new Headers([
            'HTTP_X_HTTP_METHOD_OVERRIDE' => 'PUT',
        ]);
        $request = new Request('GET', $uri, $headers, [], [], new RequestBody());
        $response = new Response();

        $next = function (ServerRequestInterface $req, ResponseInterface $res) {
            $this->assertEquals('PUT', $req->getMethod());

            return $res;
        };
        \Closure::bind($next, $this);

        $mw($request, $response, $next);
    }

    public function testBodyParam()
    {
        $mw = new MethodOverrideMiddleware();

        $uri = new Uri('http', 'example.com');
        $body = new RequestBody();
        $body->write('_METHOD=PUT');
        $headers = new Headers([
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);
        $request = new Request('POST', $uri, $headers, [], [], $body);
        $response = new Response();

        $next = function (ServerRequestInterface $req, ResponseInterface $res) {
            $this->assertEquals('PUT', $req->getMethod());

            return $res;
        };
        \Closure::bind($next, $this);

        $mw($request, $response, $next);
    }

    public function testHeaderPreferred()
    {
        $mw = new MethodOverrideMiddleware();

        $uri = new Uri('http', 'example.com');
        $body = new RequestBody();
        $body->write('_METHOD=PUT');
        $headers = new Headers([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'HTTP_X_HTTP_METHOD_OVERRIDE' => 'DELETE',
        ]);
        $request = new Request('POST', $uri, $headers, [], [], $body);
        $response = new Response();

        $next = function (ServerRequestInterface $req, ResponseInterface $res) {
            $this->assertEquals('DELETE', $req->getMethod());

            return $res;
        };
        \Closure::bind($next, $this);

        $mw($request, $response, $next);
    }

    public function testNoOverride()
    {
        $mw = new MethodOverrideMiddleware();

        $uri = new Uri('http', 'example.com');
        $request = new Request('POST', $uri, new Headers(), [], [], new RequestBody());
        $response = new Response();

        $next = function (ServerRequestInterface $req, ResponseInterface $res) {
            $this->assertEquals('POST', $req->getMethod());

            return $res;
        };
        \Closure::bind($next, $this);

        $mw($request, $response, $next);
    }
}
