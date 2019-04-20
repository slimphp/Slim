<?php
declare(strict_types=1);

namespace Slim\Interfaces;

use Slim\Routing\RoutingResults;

interface DispatcherInterface
{
    /**
     * Get allowed methods for a given request method and uri
     *
     * @param string $method
     * @param string $uri
     * @return array
     */
    public function getAllowedMethods(string $method, string $uri): array;

    /**
     * Get routing results for a given request method and uri
     *
     * @param string $method
     * @param string $uri
     * @return RoutingResults
     */
    public function dispatch(string $method, string $uri): RoutingResults;
}
