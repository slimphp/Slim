<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests;

use PHPUnit_Framework_TestCase;
use RuntimeException;
use Slim\Http\Body;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Tests\Mocks\Stackable;

class MiddlewareAwareTest extends PHPUnit_Framework_TestCase
{
    public function testSeedsMiddlewareStack()
    {
        $stack = new Stackable;
        $bottom = null;

        $stack->add(function ($req, $res, $next) use (&$bottom) {
            $bottom = $next;
            return $res;
        });

        $stack->callMiddlewareStack(
            $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder('Psr\Http\Message\ResponseInterface')->disableOriginalConstructor()->getMock()
        );

        $this->assertSame($stack, $bottom);
    }

    public function testCallMiddlewareStack()
    {
        // Build middleware stack
        $stack = new Stackable;
        $stack->add(function ($req, $res, $next) {
            $res->write('In1');
            $res = $next($req, $res);
            $res->write('Out1');

            return $res;
        })->add(function ($req, $res, $next) {
            $res->write('In2');
            $res = $next($req, $res);
            $res->write('Out2');

            return $res;
        });

        // Request
        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);

        // Response
        $response = new Response();

        // Invoke call stack
        $res = $stack->callMiddlewareStack($request, $response);

        $this->assertEquals('In2In1CenterOut1Out2', (string)$res->getBody());
    }

    public function testMiddlewareStackWithAStatic()
    {
        // Build middleware stack
        $stack = new Stackable;
        $stack->add('Slim\Tests\Mocks\StaticCallable::run')
            ->add(function ($req, $res, $next) {
                $res->write('In2');
                $res = $next($req, $res);
                $res->write('Out2');

                return $res;
            });

        // Request
        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);

        // Response
        $response = new Response();

        // Invoke call stack
        $res = $stack->callMiddlewareStack($request, $response);

        $this->assertEquals('In2In1CenterOut1Out2', (string)$res->getBody());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testMiddlewareBadReturnValue()
    {
        // Build middleware stack
        $stack = new Stackable;
        $stack->add(function ($req, $res, $next) {
            $res = $res->write('In1');
            $res = $next($req, $res);
            $res = $res->write('Out1');

            // NOTE: No return value here
        });

        // Request
        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);

        // Response
        $response = new Response();

        // Invoke call stack
        $stack->callMiddlewareStack($request, $response);
    }

    public function testAlternativeSeedMiddlewareStack()
    {
        $stack = new Stackable;
        $stack->alternativeSeed();
        $bottom = null;

        $stack->add(function ($req, $res, $next) use (&$bottom) {
            $bottom = $next;
            return $res;
        });

        $stack->callMiddlewareStack(
            $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder('Psr\Http\Message\ResponseInterface')->disableOriginalConstructor()->getMock()
        );

        $this->assertSame([$stack, 'testMiddlewareKernel'], $bottom);
    }


    public function testAddMiddlewareWhileStackIsRunningThrowException()
    {
        $stack = new Stackable;
        $stack->add(function ($req, $resp) use ($stack) {
            $stack->add(function ($req, $resp) {
                return $resp;
            });
            return $resp;
        });
        $this->setExpectedException('RuntimeException');
        $stack->callMiddlewareStack(
            $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder('Psr\Http\Message\ResponseInterface')->disableOriginalConstructor()->getMock()
        );
    }

    public function testSeedTwiceThrowException()
    {
        $stack = new Stackable;
        $stack->alternativeSeed();
        $this->setExpectedException('RuntimeException');
        $stack->alternativeSeed();
    }
}
