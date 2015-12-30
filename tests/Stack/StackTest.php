<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Stack;

use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Stack\Stack;
use Slim\Stack\StackException;

class StackTest extends \PHPUnit_Framework_TestCase
{
    public function testStackExceptionReturnsException()
    {
        $req = Request::createFromEnvironment(Environment::mock());
        $res = new Response;
        $exception = new \RuntimeException();

        $this->setExpectedException('RuntimeException');

        try {
            throw new StackException($req, $res, $exception);
        } catch (StackException $e) {
            throw $e->getException();
        }
    }

    public function testConstructSetsValues()
    {
        $kernel = 'callable';
        $resolver = function($entry) {
            return $entry;
        };

        $stack = new Stack($kernel, [$resolver, $resolver]);

        $queue = StackUtils::getQueue($stack);
        $this->assertCount(1, $queue);
        $this->assertSame($kernel, array_shift($queue));

        $resolvers = StackUtils::getResolvers($stack);
        $this->assertCount(2, $resolvers);
        $this->assertSame($resolver, array_shift($resolvers));
    }

    public function testConstructDoesNotSetValues()
    {
        $stack = new Stack(null);
        $this->assertCount(0, StackUtils::getQueue($stack));
        $this->assertCount(0, StackUtils::getResolvers($stack));
    }

    public function testAddPrependsEntries()
    {
        $kernel = 'callable';
        $callFirst = 'callable';
        $callSecond = 'callable';

        $stack = new Stack($kernel);
        $stack->add($callSecond);
        $stack->add($callFirst);

        $queue = StackUtils::getQueue($stack);
        $this->assertSame($callFirst, $queue[0]);
        $this->assertSame($callSecond, $queue[1]);
        $this->assertSame($kernel, $queue[2]);
    }

    public function testAddQueuePrependsEntries()
    {
        $kernel = 'callable';
        $callFirst = 'callable';
        $callSecond = 'callable';

        $stack = new Stack($kernel);
        $stack->addQueue([$callSecond, $callFirst]);

        $queue = StackUtils::getQueue($stack);
        $this->assertSame($callFirst, $queue[0]);
        $this->assertSame($callSecond, $queue[1]);
        $this->assertSame($kernel, $queue[2]);
    }

    public function testAddThrowsWhenRunning()
    {
        $this->setExpectedException('RuntimeException', Stack::ERR_RUNNING);

        $stack = new Stack(null);

        // This middleware adds a new entry to the stack
        $mw = function ($req, $res, $next) use ($stack) {
            $stack->add('callable');
            return $res;
        };

        $stack->add($mw);

        $req = Request::createFromEnvironment(Environment::mock());
        $res = new Response;

        try {
            $stack->run($req, $res);
        } catch (StackException $e) {
            throw $e->getException();
        }
    }

    public function testAddResolverAppendsItem()
    {
        $resolver0 = function ($entry) {
            return $entry;
        };

        $resolver1 = function ($entry) {
            return $entry;
        };

        $stack = new Stack(null, [$resolver0]);
        $stack->addResolver($resolver1);

        $resolvers = StackUtils::getResolvers($stack);
        $this->assertSame($resolver1, $resolvers[1]);
    }

    public function testRun()
    {
        $kernel = function($req, $res, $next) {
            $res->write('Kernel');
            return $res;
        };

        $in1 = function($req, $res, $next) {
            $res->write('In1');
            $res = $next($req, $res);
            $res->write('Out1');
            return $res;
        };

        $in2 = function($req, $res, $next) {
            $res->write('In2');
            $res = $next($req, $res);
            $res->write('Out2');
            return $res;
        };

        $stack = new Stack($kernel);
        $stack->add($in1);
        $stack->add($in2);

        $req = Request::createFromEnvironment(Environment::mock());
        $res = new Response;

        $response = $stack->run($req, $res);
        $this->assertEquals('In2In1KernelOut1Out2', (string)$response->getBody());
    }

    public function testRunWithMethod()
    {
        $kernel = function($req, $res, $next) {
            $res->write('Kernel');
            return $res;
        };

        $resolver = function ($entry) {
            return [new $entry, 'run'];
        };

        $stack = new Stack($kernel, [$resolver]);
        $stack->add('Slim\Tests\Stack\Mocks\MiddlewareMethod');

        $req = Request::createFromEnvironment(Environment::mock());
        $res = new Response;

        $response = $stack->run($req, $res);
        $this->assertEquals('InMethodKernelOutMethod', (string)$response->getBody());
    }

    public function testRunWithStatic()
    {
        $kernel = function($req, $res, $next) {
            $res->write('Kernel');
            return $res;
        };

        $stack = new Stack($kernel);
        $stack->add('Slim\Tests\Stack\Mocks\MiddlewareStatic::run');

        $req = Request::createFromEnvironment(Environment::mock());
        $res = new Response;

        $response = $stack->run($req, $res);
        $this->assertEquals('InStaticKernelOutStatic', (string)$response->getBody());
    }

    public function testRunWithExceptionSavesMessageState()
    {
        // All middleware changes the status so we get a new response object
        $kernel = function($req, $res, $next) {
            $res = $res->withStatus(200);
            $res->write('Kernel');
            throw new \RuntimeException('oops');
            return $res;
        };

        $in1 = function($req, $res, $next) {
            $res = $res->withStatus(201);
            $res->write('In1');
            $res = $next($req, $res);
            $res->write('Out1');
            return $res;
        };

        $in2 = function($req, $res, $next) {
            $res = $res->withStatus(202);
            $res->write('In2');
            $res = $next($req, $res);
            $res->write('Out2');
            return $res;
        };

        $stack = new Stack($kernel);
        $stack->add($in1);
        $stack->add($in2);

        $req = Request::createFromEnvironment(Environment::mock());
        $res = new Response;

        try {
            $stack->run($req, $res);
        } catch (StackException $e) {
            $response = $e->getResponse();

            // Our exception was thrown in the kernel
            $this->assertEquals('In2In1Kernel', (string)$response->getBody());

            // Our response object should be from the middleware that calls the kernel
            $this->assertEquals(201, $response->getStatusCode());
        }
    }

    public function testRunWithBadResponseThrowsException()
    {
        $this->setExpectedException('RuntimeException', Stack::ERR_RESPONSE);

        // This middleware does not return a ResponseInterface instance
        $kernel = function($req, $res, $next) {
            return new \stdClass();
        };

        $stack = new Stack($kernel);

        $req = Request::createFromEnvironment(Environment::mock());
        $res = new Response;

        try {
            $stack->run($req, $res);
        } catch (StackException $e) {
            throw $e->getException();
        }
    }

    public function testResolveReturnsCallable()
    {
        $kernel = function($req, $res, $next) {
            return $res;
        };

        $stack = new Stack($kernel);

        $method = StackUtils::getMethod($stack, 'resolve');
        $queue = StackUtils::getQueue($stack);
        $callable = $method->invoke($stack, array_shift($queue));

        $this->assertSame($kernel, $callable);
    }

    public function testResolveFindsResolver()
    {
        // This middleware entry needs resolving
        $kernel = 'kernel';

        // This resolver will not resolve it...
        $resolver1 = function($entry) {
            if ($entry === 'MyMiddleware') {
                return new $entry;
            }
        };

        $stack = new Stack($kernel, [$resolver1]);

        // ...but this resolver will
        $resolver2 = function($entry) {
            if ($entry === 'kernel') {
                return function() use ($entry) {
                    return $entry;
                };
            }
        };

        $stack->addResolver($resolver2);

        $method = StackUtils::getMethod($stack, 'resolve');
        $queue = StackUtils::getQueue($stack);
        $callable = $method->invoke($stack, array_shift($queue));

        $this->assertSame($kernel, $callable());
    }

    public function testResolveThrowsWithBadEntry()
    {
        $this->setExpectedException('RuntimeException', Stack::ERR_RESOLVER);

        // This middleware entry is not callable
        $kernel = new \stdClass();

        $stack = new Stack($kernel);

        $method = StackUtils::getMethod($stack, 'resolve');
        $queue = StackUtils::getQueue($stack);
        $callable = $method->invoke($stack, array_shift($queue));
    }

    public function testResolveThrowsWithBadResolver()
    {
        $this->setExpectedException('RuntimeException', Stack::ERR_RESOLVER);

        // This middleware entry needs resolving...
        $kernel = 'kernel';

        // ...but this resolver does not return a callable
        $resolver = function($entry) {
            return new \stdClass();
        };

        $stack = new Stack($kernel, [$resolver]);

        $method = StackUtils::getMethod($stack, 'resolve');
        $queue = StackUtils::getQueue($stack);
        $callable = $method->invoke($stack, array_shift($queue));
    }

    public function testHasHttpMessage()
    {
        $stack = new Stack(null);
        $method = StackUtils::getMethod($stack, 'hasHttpMessage');

        $req = Request::createFromEnvironment(Environment::mock());
        $res = new Response;

        $exception = new Mocks\GoodHttpException($req, $res);
        $this->assertTrue($method->invoke($stack, $exception));

        $exception = new Mocks\BadHttpException();
        $this->assertFalse($method->invoke($stack, $exception));
    }
}
