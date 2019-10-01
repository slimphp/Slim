<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use RuntimeException;

interface RouteParserInterface
{
    /**
     * Build the path for a named route excluding the base path
     *
     *
     * @param string $routeName   Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws RuntimeException         If named route does not exist
     * @throws InvalidArgumentException If required data not provided
     */
    public function relativeUrlFor(string $routeName, array $data = [], array $queryParams = []): string;

    /**
     * Build the path for a named route including the base path
     *
     * @param string $routeName   Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws RuntimeException         If named route does not exist
     * @throws InvalidArgumentException If required data not provided
     */
    public function urlFor(string $routeName, array $data = [], array $queryParams = []): string;

    /**
     * Get fully qualified URL for named route
     *
     * @param UriInterface $uri
     * @param string       $routeName   Route name
     * @param array        $data        Named argument replacement data
     * @param array        $queryParams Optional query string parameters
     *
     * @return string
     */
    public function fullUrlFor(UriInterface $uri, string $routeName, array $data = [], array $queryParams = []): string;

    /**
     * Get base path
     *
     * @return string
     */
    public function basePath(): string;
}
