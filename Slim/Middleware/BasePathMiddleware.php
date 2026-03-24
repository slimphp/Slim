<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_pop;
use function dirname;
use function end;
use function explode;
use function implode;
use function is_string;
use function ltrim;
use function rtrim;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Middleware to handle applications running in a subdirectory.
 *
 * Strips the base path from the request URI so routes can be defined
 * without the base path prefix.
 *
 * @package Slim\Middleware
 * @api
 */
class BasePathMiddleware implements MiddlewareInterface
{
    /**
     * @var string The base path to strip (e.g., '/myapp')
     */
    private string $basePath;

    /**
     * @param string $basePath The base path to strip (e.g., '/myapp')
     */
    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Create middleware with auto-detected base path from request server params.
     *
     * Detects the base path from SCRIPT_NAME server parameter.
     * If SCRIPT_NAME points to /public/index.php, it strips the /public part.
     *
     * @param ServerRequestInterface $request The request to detect base path from
     * @return self
     */
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $serverParams = $request->getServerParams();
        $scriptName = $serverParams['SCRIPT_NAME'] ?? '';

        if (!is_string($scriptName) || $scriptName === '') {
            return new self('');
        }

        // Get the directory of the script
        $basePath = rtrim(dirname($scriptName), '/');

        // If the script is in /public directory, strip it
        if (str_starts_with($basePath, '/')) {
            $parts = explode('/', $basePath);
            if (end($parts) === 'public') {
                array_pop($parts);
                $basePath = implode('/', $parts);
            }
        }

        return new self($basePath);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // If base path is empty, skip processing
        if ($this->basePath === '') {
            return $handler->handle($request);
        }

        $uri = $request->getUri();
        $path = $uri->getPath();

        // If base path doesn't match, skip
        if (!str_starts_with($path, $this->basePath . '/')) {
            // Special case: exact match on base path (e.g., /myapp -> /)
            if ($path !== $this->basePath) {
                return $handler->handle($request);
            }
        }

        // Strip the base path
        $newPath = (string) substr($path, strlen($this->basePath));

        // Ensure path starts with /
        if ($newPath === '' || !str_starts_with($newPath, '/')) {
            $newPath = '/' . ltrim($newPath, '/');
        }

        // Create new request with modified URI
        $newUri = $uri->withPath($newPath);
        $request = $request->withUri($newUri);

        // Store original base path in attribute (useful for URL generation)
        $request = $request->withAttribute('basePath', $this->basePath);

        return $handler->handle($request);
    }
}
