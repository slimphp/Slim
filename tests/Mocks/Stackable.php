<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Mocks;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\MiddlewareAwareTrait;

/**
 * Mock object for Slim\Tests\MiddlewareAwareTest
 */
class Stackable
{
    use MiddlewareAwareTrait;

    public function __invoke(ServerRequestInterface $req, ResponseInterface $res)
    {
        return $res->write('Center');
    }

    public function alternativeSeed()
    {
        $this->seedMiddlewareStack([$this, 'testMiddlewareKernel']);
    }

    public function testMiddlewareKernel(ServerRequestInterface $req, ResponseInterface $res)
    {
        return $res->write('hello from testMiddlewareKernel');
    }

    public function add($callable)
    {
        $this->addMiddleware($callable);
        return $this;
    }
}
