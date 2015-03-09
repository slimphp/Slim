<?php
namespace Slim\Tests;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Stackable
{
    use \Slim\MiddlewareAware;

    public function __invoke(RequestInterface $req, ResponseInterface $res)
    {
        $res = $res->write('Center');

        return $res;
    }
}

class MiddlewareTest extends \PHPUnit_Framework_TestCase
{
    public function testSeedsMiddlewareStack()
    {
        $stack = new Stackable;
        $stack->add(function ($req, $res, $next) {
            return $res->write('Hi');
        });
        $prop = new \ReflectionProperty($stack, 'stack');
        $prop->setAccessible(true);

        $this->assertSame($stack, $prop->getValue($stack)->bottom());
    }

    public function testCallMiddlewareStack()
    {
        // Build middleware stack
        $stack = new Stackable;
        $stack->add(function ($req, $res, $next) {
            $res = $res->write('In1');
            $res = $next($req, $res);
            $res = $res->write('Out1');

            return $res;
        });
        $stack->add(function ($req, $res, $next) {
            $res = $res->write('In2');
            $res = $next($req, $res);
            $res = $res->write('Out2');

            return $res;
        });

        // Request
        $uri = \Slim\Http\Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Collection();
        $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
        $request = new \Slim\Http\Request('GET', $uri, $headers, $cookies, $body);

        // Response
        $response = new \Slim\Http\Response();

        // Invoke call stack
        $res = $stack->callMiddlewareStack($request, $response);

        $this->assertEquals('In2In1CenterOut1Out2', (string)$res->getBody());
    }

    /**
     * @expectedException \RuntimeException
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
        $uri = \Slim\Http\Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Collection();
        $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
        $request = new \Slim\Http\Request('GET', $uri, $headers, $cookies, $body);

        // Response
        $response = new \Slim\Http\Response();

        // Invoke call stack
        $res = $stack->callMiddlewareStack($request, $response);
    }
}
