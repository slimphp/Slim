<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class RouteContext
{
    public const ROUTING_RESULTS = '__routingResults__';

    public const BASE_PATH = '__basePath__';

    private RoutingResults $routingResults;

    private ?string $basePath;

    private function __construct(
        RoutingResults $routingResults,
        ?string $basePath = null,
    ) {
        $this->routingResults = $routingResults;
        $this->basePath = $basePath;
    }

    public static function fromRequest(ServerRequestInterface $request): self
    {
        /* @var RoutingResults|null $routingResults */
        $routingResults = $request->getAttribute(self::ROUTING_RESULTS);

        /* @var string|null $basePath */
        $basePath = $request->getAttribute(self::BASE_PATH);

        if (!$routingResults instanceof RoutingResults) {
            throw new RuntimeException(
                'Cannot create RouteContext before routing has been completed. Add RoutingMiddleware to fix this.',
            );
        }

        if ($basePath !== null && !is_string($basePath)) {
            throw new RuntimeException(
                sprintf('Invalid basePath attribute type: %s', gettype($basePath)),
            );
        }

        return new self($routingResults, $basePath);
    }

    public function getRoutingResults(): RoutingResults
    {
        return $this->routingResults;
    }

    public function getBasePath(): ?string
    {
        return $this->basePath;
    }

    public function getRoute(): ?Route
    {
        return $this->routingResults->getRoute();
    }

    /**
     * @return array<string|int,mixed>
     */
    public function getArguments(): array
    {
        return $this->routingResults->getRouteArguments();
    }

    public function getArgument(string $key): mixed
    {
        return $this->routingResults->getRouteArgument($key);
    }
}
