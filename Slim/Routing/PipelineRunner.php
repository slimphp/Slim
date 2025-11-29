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

use function current;
use function is_callable;
use function next;
use function sprintf;

/**
 * A pipeline runner.
 */
final class PipelineRunner implements RequestHandlerInterface
{
    /**
     * @var array<MiddlewareInterface|RequestHandlerInterface|callable>
     */
    private array $queue;

    /**
     * @param array<MiddlewareInterface|RequestHandlerInterface|callable> $queue
     */
    public function __construct(array $queue)
    {
        $this->queue = array_values($queue);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = current($this->queue);

        if (!$middleware) {
            throw new RuntimeException('No middleware found. Add a response factory middleware.');
        }

        next($this->queue);

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->process($request, $this);
        }

        if ($middleware instanceof RequestHandlerInterface) {
            return $middleware->handle($request);
        }

        if (is_callable($middleware)) {
            return $middleware($request, $this);
        }

        // @phpstan-ignore-next-line
        throw new RuntimeException(
            sprintf(
                'Invalid middleware queue entry "%s". Middleware must either be callable or implement %s.',
                is_scalar($middleware) ? (string)$middleware : gettype($middleware),
                MiddlewareInterface::class,
            ),
        );
    }
}
