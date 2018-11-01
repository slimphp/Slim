<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Tests\Mocks\Stackable;

class MiddlewareAwareTest extends Test
{
    public function testSeedsMiddlewareStack()
    {
        $bottom = null;

        $stack = new Stackable;
        $stack->add(function (ServerRequestInterface $request, ResponseInterface $response, $next) use (&$bottom) {
            $bottom = $next;
            return $response;
        });

        $request = $this->createServerRequest('https://example.com:443/foo/bar?abc=123');
        $response = $this->createResponse();

        $stack->callMiddlewareStack($request, $response);

        $this->assertSame($stack, $bottom);
    }

    public function testCallMiddlewareStack()
    {
        $stack = new Stackable;
        $stack->add(function (ServerRequestInterface $request, ResponseInterface $response, $next) {
            $response->getBody()->write('In1');
            $response = $next($request, $response);
            $response->getBody()->write('Out1');
            return $response;
        })->add(function (ServerRequestInterface $request, ResponseInterface $response, $next) {
            $response->getBody()->write('In2');
            $response = $next($request, $response);
            $response->getBody()->write('Out2');
            return $response;
        });

        $request = $this->createServerRequest('/');
        $response = $stack->callMiddlewareStack($request, $this->createResponse());

        $this->assertEquals('In2In1CenterOut1Out2', (string) $response->getBody());
    }

    public function testMiddlewareStackWithAStatic()
    {
        // Build middleware stack
        $stack = new Stackable;
        $stack
            ->add('Slim\Tests\Mocks\StaticCallable::run')
            ->add(function (ServerRequestInterface $request, ResponseInterface $response, $next) {
                $response->getBody()->write('In2');
                $response = $next($request, $response);
                $response->getBody()->write('Out2');
                return $response;
            });

        $request = $this->createServerRequest('/');
        $response = $stack->callMiddlewareStack($request, $this->createResponse());

        $this->assertEquals('In2In1CenterOut1Out2', (string) $response->getBody());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testMiddlewareBadReturnValue()
    {
        $stack = new Stackable;
        $stack->add(function (ServerRequestInterface $request, ResponseInterface $response, $next) {
            // Return Nothing
        });

        $request = $this->createServerRequest('/');
        $response = $stack->callMiddlewareStack($request, $this->createResponse());

        $stack->callMiddlewareStack($request, $response);
    }

    public function testAlternativeSeedMiddlewareStack()
    {
        $stack = new Stackable;
        $stack->alternativeSeed();
        $bottom = null;

        $stack->add(function (ServerRequestInterface $request, ResponseInterface $response, $next) use (&$bottom) {
            $bottom = $next;
            return $response;
        });

        $request = $this->createServerRequest('/');
        $response = $this->createResponse();

        $stack->callMiddlewareStack($request, $response);

        $this->assertSame([$stack, 'testMiddlewareKernel'], $bottom);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testAddMiddlewareWhileStackIsRunningThrowException()
    {
        $stack = new Stackable;
        $stack->add(function () use ($stack) {
            $stack->add(function () {
            });
        });

        $request = $this->createServerRequest('/');
        $response = $this->createResponse();

        $stack->callMiddlewareStack($request, $response);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSeedTwiceThrowException()
    {
        $stack = new Stackable;
        $stack->alternativeSeed();
        $stack->alternativeSeed();
    }
}
