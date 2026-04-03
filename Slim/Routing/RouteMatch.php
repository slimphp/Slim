<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

namespace Slim\Routing;

use Slim\Interfaces\DispatcherInterface;
use Slim\Interfaces\RouteInterface;

final class RouteMatch
{
    private int $status;

    private ?RouteInterface $route;

    /** @var array<string, mixed> */
    private array $arguments;

    /** @var list<string> */
    private array $allowedMethods;

    /**
     * @param array<string, mixed> $arguments
     * @param list<string> $allowedMethods
     */
    private function __construct(
        int $status,
        ?RouteInterface $route = null,
        array $arguments = [],
        array $allowedMethods = [],
    ) {
        $this->status = $status;
        $this->route = $route;
        $this->arguments = $arguments;
        $this->allowedMethods = $allowedMethods;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public static function found(
        RouteInterface $route,
        array $arguments = [],
    ): self {
        return new self(DispatcherInterface::FOUND, $route, $arguments, []);
    }

    public static function notFound(): self
    {
        return new self(DispatcherInterface::NOT_FOUND, null, [], []);
    }

    /**
     * @param list<string> $allowedMethods
     */
    public static function methodNotAllowed(array $allowedMethods): self
    {
        return new self(DispatcherInterface::METHOD_NOT_ALLOWED, null, [], $allowedMethods);
    }

    public function isFound(): bool
    {
        return $this->status === DispatcherInterface::FOUND;
    }

    public function isNotFound(): bool
    {
        return $this->status === DispatcherInterface::NOT_FOUND;
    }

    public function isMethodNotAllowed(): bool
    {
        return $this->status === DispatcherInterface::METHOD_NOT_ALLOWED;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getRoute(): ?RouteInterface
    {
        return $this->route;
    }

    /**
     * @return array<string, mixed>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgument(string $name, mixed $default = null): mixed
    {
        return $this->arguments[$name] ?? $default;
    }

    /**
     * @return list<string>
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }

}
