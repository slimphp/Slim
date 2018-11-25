<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
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

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $response->getBody()->write('Center');
        return $response;
    }

    public function alternativeSeed()
    {
        $this->seedMiddlewareStack([$this, 'testMiddlewareKernel']);
    }

    public function testMiddlewareKernel(ServerRequestInterface $request, ResponseInterface $response)
    {
        $response->getBody()->write('hello from testMiddlewareKernel');
        return $response;
    }

    public function add($callable)
    {
        $this->addMiddleware($callable);
        return $this;
    }
}
