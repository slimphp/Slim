<?php
declare(strict_types=1);

namespace Slim\Tests\Mocks;

use Prophecy\Prophet;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MockMiddlewareWithConstructor implements MiddlewareInterface
{
    /**
     * @var ContainerInterface|null
     */
    public static $container;

    /**
     * @param ContainerInterface|null $container
     */
    public function __construct(?ContainerInterface $container = null)
    {
        self::$container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $prophet = new Prophet();
        $responseProphecy = $prophet->prophesize(ResponseInterface::class);
        return $responseProphecy->reveal();
    }
}
