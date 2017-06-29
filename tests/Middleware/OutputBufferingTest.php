<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
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
use Slim\Middleware\OutputBuffering;

class OutputBufferingTest extends TestCase
{
    public function testStyleDefaultValid()
    {
        $mw = new OutputBuffering();

        $this->assertAttributeEquals('append', 'style', $mw);
    }

    public function testStyleCustomValid()
    {
        $mw = new OutputBuffering('prepend');

        $this->assertAttributeEquals('prepend', 'style', $mw);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testStyleCustomInvalid()
    {
        $mw = new OutputBuffering('foo');
    }

    public function testAppend()
    {
        $mw = new OutputBuffering('append');

        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response();

        $next = function (ServerRequestInterface $req, ResponseInterface $res) {
            $res->write('Body');
            echo 'Test';

            return $res;
        };
        $result = $mw($request, $response, $next);

        $this->assertEquals('BodyTest', $result->getBody());
    }

    public function testPrepend()
    {
        $mw = new OutputBuffering('prepend');

        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response();

        $next = function (ServerRequestInterface $req, ResponseInterface $res) {
            $res->write('Body');
            echo 'Test';

            return $res;
        };
        $result = $mw($request, $response, $next);

        $this->assertEquals('TestBody', $result->getBody());
    }
}
