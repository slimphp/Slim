<?php

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;

final class RouteInvokerMiddleware implements MiddlewareInterface
{
    private ResponseFactoryInterface $responseFactory;

    private RequestHandlerInvocationStrategyInterface $invocationStrategy;

    /** @var callable|null */
    private $handler = null;

    /** @var array<string, mixed> */
    private array $args = [];
    private ContainerResolverInterface $containerResolver;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        RequestHandlerInvocationStrategyInterface $invocationStrategy,
        ContainerResolverInterface $containerResolver,
    ) {
        $this->responseFactory = $responseFactory;
        $this->invocationStrategy = $invocationStrategy;
        $this->containerResolver = $containerResolver;
    }

    /**
     * Add handler.
     *
     * @param callable|string $handler
     * @param array<string, mixed> $args
     *
     * @return self
     */
    public function withHandler(callable|string $handler, array $args = []): self
    {
        $clone = clone $this;
        $clone->handler = $this->containerResolver->resolveCallable($handler);
        $clone->args = $args;

        return $clone;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if ($this->handler === null) {
            throw new RuntimeException(
                'RouteInvokerMiddleware: no handler has been assigned. ' .
                'Use withHandler() before using this middleware.',
            );
        }

        return ($this->invocationStrategy)(
            $this->handler,
            $request,
            $this->responseFactory->createResponse(),
            $this->args,
        );
    }
}
