<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

use Slim\Interfaces\ContainerResolverInterface;

use function current;
use function is_callable;
use function next;
use function sprintf;

/**
 * A pipeline runner.
 */
final class PipelineRunner implements RequestHandlerInterface
{
    private ContainerResolverInterface $resolver;

    /**
     * @var array<MiddlewareInterface|RequestHandlerInterface|callable|string>
     */
    private array $pipeline = [];

    public function __construct(ContainerResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @param array<MiddlewareInterface|RequestHandlerInterface|callable|string> $pipeline
     */
    public function withPipeline(array $pipeline): self
    {
        $clone = clone $this;
        $clone->pipeline = $pipeline;

        return $clone;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = current($this->pipeline);

        if (!$middleware) {
            throw new RuntimeException('No middleware found. Add a response factory middleware.');
        }

        $middleware = $this->resolver->resolve($middleware);

        next($this->pipeline);

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->process($request, $this);
        }

        if ($middleware instanceof RequestHandlerInterface) {
            return $middleware->handle($request);
        }

        if (is_callable($middleware)) {
            return $middleware($request, $this);
        }

        throw new RuntimeException(
            sprintf(
                'Invalid middleware queue entry "%s". Middleware must either be callable or implement %s.',
                is_scalar($middleware) ? (string)$middleware : gettype($middleware),
                MiddlewareInterface::class,
            ),
        );
    }
}
