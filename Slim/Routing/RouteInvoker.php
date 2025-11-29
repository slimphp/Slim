<?php

declare(strict_types=1);

namespace Slim\Routing;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;

final class RouteInvoker implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;

    private RequestHandlerInvocationStrategyInterface $invocationStrategy;

    private ContainerResolverInterface $resolver;

    /** @var callable|null */
    private $handler = null;
    /** @var array<string, mixed> */
    private array $args = [];

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        RequestHandlerInvocationStrategyInterface $invocationStrategy,
        ContainerResolverInterface $containerResolver,
    ) {
        $this->responseFactory = $responseFactory;
        $this->invocationStrategy = $invocationStrategy;
        $this->resolver = $containerResolver;
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
        $clone->handler = $this->resolver->resolveCallable($handler);
        $clone->args = $args;

        return $clone;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
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
