<?php

declare(strict_types=1);

namespace Slim\Interfaces;

interface DispatcherInterface
{
    const NOT_FOUND = 0;
    const FOUND = 1;
    const METHOD_NOT_ALLOWED = 2;

    /**
     * @return array<int, mixed>
     */
    public function dispatch(string $httpMethod, string $uri): array;
}
