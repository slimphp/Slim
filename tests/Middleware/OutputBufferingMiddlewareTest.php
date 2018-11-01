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
use Slim\Middleware\OutputBufferingMiddleware;
use Slim\Tests\Test;

class OutputBufferingMiddlewareTest extends Test
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
        $mw = new OutputBufferingMiddleware($this->getStreamFactory(), 'foo');
    }

    public function testAppend()
    {
        $mw = new OutputBufferingMiddleware($this->getStreamFactory(), 'append');

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Body');
            echo 'Test';

            return $response;
        };

        $request = $this->createServerRequest('/', 'GET');
        $response = $this->createResponse();
        $result = $mw($request, $response, $next);

        $this->assertEquals('BodyTest', $result->getBody());
    }

    public function testPrepend()
    {
        $mw = new OutputBufferingMiddleware($this->getStreamFactory(), 'prepend');

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Body');
            echo 'Test';

            return $response;
        };

        $request = $this->createServerRequest('/', 'GET');
        $response = $this->createResponse();
        $result = $mw($request, $response, $next);

        $this->assertEquals('TestBody', $result->getBody());
    }
}
