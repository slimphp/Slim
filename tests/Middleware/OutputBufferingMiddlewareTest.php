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
use Slim\Middleware\Psr7MiddlewareWrapper;
use Slim\Middleware\OutputBufferingMiddleware;
use Slim\MiddlewareRunner;
use Slim\Tests\TestCase;

class OutputBufferingMiddlewareTest extends TestCase
{
    public function testStyleDefaultValid()
    {
        $mw = new OutputBufferingMiddleware($this->getStreamFactory());
        $this->assertAttributeEquals('append', 'style', $mw);
    }

    public function testStyleCustomValid()
    {
        $mw = new OutputBufferingMiddleware($this->getStreamFactory(), 'prepend');
        $this->assertAttributeEquals('prepend', 'style', $mw);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testStyleCustomInvalid()
    {
        new OutputBufferingMiddleware($this->getStreamFactory(), 'foo');
    }

    public function testAppend()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Body');
            echo 'Test';

            return $response;
        };

        $responseFactory = $this->getResponseFactory();
        $mw = new Psr7MiddlewareWrapper($callable, $responseFactory);
        $mw2 = new OutputBufferingMiddleware($this->getStreamFactory(), 'append');

        $request = $this->createServerRequest('/', 'GET');

        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->add($mw);
        $middlewareRunner->add($mw2);
        $response = $middlewareRunner->run($request);

        $this->assertEquals('BodyTest', $response->getBody());
    }

    public function testPrepend()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Body');
            echo 'Test';

            return $response;
        };

        $responseFactory = $this->getResponseFactory();
        $mw = new Psr7MiddlewareWrapper($callable, $responseFactory);
        $mw2 = new OutputBufferingMiddleware($this->getStreamFactory(), 'prepend');

        $request = $this->createServerRequest('/', 'GET');

        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->add($mw);
        $middlewareRunner->add($mw2);
        $response = $middlewareRunner->run($request);

        $this->assertEquals('TestBody', $response->getBody());
    }
}
