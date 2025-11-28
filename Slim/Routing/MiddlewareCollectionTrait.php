<?php

declare(strict_types=1);

namespace Slim\Routing;

use Psr\Http\Server\MiddlewareInterface;

trait MiddlewareCollectionTrait
{
    /**
     * @var array<MiddlewareInterface|callable|string|array>
     */
    private array $middleware = [];

    /**
     * @return array<MiddlewareInterface|callable|string|array>
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
