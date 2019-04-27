<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Mocks;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Response;
use Slim\MiddlewareAwareTrait;

class Stackable
{
    use MiddlewareAwareTrait;

    public function __invoke(ServerRequestInterface $request, Response $response)
    {
        return $response->write('Center');
    }

    public function alternativeSeed()
    {
        $this->seedMiddlewareStack([$this, 'testMiddlewareKernel']);
    }

    public function testMiddlewareKernel(ServerRequestInterface $request, Response $response)
    {
        return $response->write('hello from testMiddlewareKernel');
    }

    public function add($callable)
    {
        $this->addMiddleware($callable);
        return $this;
    }
}
