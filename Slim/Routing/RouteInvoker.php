<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

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
    private array $arguments = [];

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        RequestHandlerInvocationStrategyInterface $invocationStrategy,
        ContainerResolverInterface $resolver,
    ) {
        $this->responseFactory = $responseFactory;
        $this->invocationStrategy = $invocationStrategy;
        $this->resolver = $resolver;
    }

    /**
     * @param callable|string $handler
     * @param array<string, mixed> $arguments
     */
    public function withHandler(callable|string $handler, array $arguments = []): self
    {
        $clone = clone $this;
        $clone->handler = $this->resolver->resolveCallable($handler);
        $clone->arguments = $arguments;

        return $clone;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->handler === null) {
            throw new RuntimeException(
                'RouteInvoker has no handler assigned. Call withHandler() before execution.',
            );
        }

        return ($this->invocationStrategy)(
            $this->handler,
            $request,
            $this->responseFactory->createResponse(),
            $this->arguments,
        );
    }
}
