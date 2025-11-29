<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use Psr\Http\Server\MiddlewareInterface;

trait MiddlewareCollectionTrait
{
    /**
     * @var array<MiddlewareInterface|callable|string>
     */
    private array $middleware = [];

    /**
     * @return array<MiddlewareInterface|callable|string>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function add(MiddlewareInterface|callable|string $middleware): self
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;

        return $this;
    }
}
