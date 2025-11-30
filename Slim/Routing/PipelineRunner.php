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

use function is_callable;
use function sprintf;

/**
 * A pipeline runner.
 */
final class PipelineRunner implements RequestHandlerInterface
{
    private ContainerResolverInterface $resolver;

    /**
     * @var array<int, mixed>
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
        $clone->pipeline = array_values($pipeline);

        return $clone;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $entry = current($this->pipeline);

        if (!$entry) {
            throw new RuntimeException('The middleware pipeline is empty.');
        }

        $entry = $this->resolver->resolve($entry);

        next($this->pipeline);

        if ($entry instanceof MiddlewareInterface) {
            return $entry->process($request, $this);
        }

        if ($entry instanceof RequestHandlerInterface) {
            return $entry->handle($request);
        }

        if (is_callable($entry)) {
            return $entry($request, $this);
        }

        throw new RuntimeException(
            sprintf(
                'Invalid pipeline entry of type "%s". Expected one of: callable, %s, or %s.',
                is_object($entry) ? $entry::class : gettype($entry),
                MiddlewareInterface::class,
                RequestHandlerInterface::class,
            ),
        );
    }
}
