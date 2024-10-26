<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CorsMiddleware implements MiddlewareInterface
{
    private ResponseFactoryInterface $responseFactory;
    private ?int $maxAge = null;
    private ?array $allowedOrigins = null;
    private bool $allowCredentials = false;
    private array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    private array $allowedHeaders = ['*'];
    private array $exposedHeaders = [];
    private bool $useCache = true;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $origin = $request->getHeaderLine('Origin');

        if ($request->getMethod() === 'OPTIONS') {
            $response = $this->responseFactory->createResponse();
        } else {
            $response = $handler->handle($request);
        }

        // Handle origin header
        if ($origin && $this->isOriginAllowed($origin)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);

            // Allow credentials only with specific origin
            if ($this->allowCredentials) {
                $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
            }
        } elseif ($this->allowedOrigins === null) {
            // If no specific origins are set, use wildcard
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        }

        // Add allowed methods
        if (!empty($this->allowedMethods)) {
            $response = $response->withHeader(
                'Access-Control-Allow-Methods',
                implode(', ', $this->allowedMethods)
            );
        }

        // Add allowed headers
        if (!empty($this->allowedHeaders)) {
            $response = $response->withHeader(
                'Access-Control-Allow-Headers',
                implode(', ', $this->allowedHeaders)
            );
        }

        // Add exposed headers
        if (!empty($this->exposedHeaders)) {
            $response = $response->withHeader(
                'Access-Control-Expose-Headers',
                implode(', ', $this->exposedHeaders)
            );
        }

        // Add max age header if configured
        if ($this->maxAge !== null) {
            $response = $response->withHeader('Access-Control-Max-Age', (string)$this->maxAge);
        }

        // Add cache control headers if enabled
        if ($this->useCache) {
            $response = $response
                ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->withHeader('Pragma', 'no-cache');
        }

        return $response;
    }

    /**
     * Set the Access-Control-Max-Age header value in seconds.
     * Set to null to disable the header.
     */
    public function withMaxAge(?int $maxAge): self
    {
        $clone = clone $this;
        $clone->maxAge = $maxAge;

        return $clone;
    }

    /**
     * Set allowed origins. Null means allow all (*).
     * Pass an array of strings to specify allowed origins.
     */
    public function withAllowedOrigins(?array $origins): self
    {
        $clone = clone $this;
        $clone->allowedOrigins = $origins;

        return $clone;
    }

    /**
     * Set whether to allow credentials.
     */
    public function withAllowCredentials(bool $allow): self
    {
        $clone = clone $this;
        $clone->allowCredentials = $allow;

        return $clone;
    }

    /**
     * Set allowed methods.
     */
    public function withAllowedMethods(array $methods): self
    {
        $clone = clone $this;
        $clone->allowedMethods = array_map('strtoupper', $methods);

        return $clone;
    }

    /**
     * Set allowed headers.
     */
    public function withAllowedHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->allowedHeaders = $headers;

        return $clone;
    }

    /**
     * Set exposed headers.
     */
    public function withExposedHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->exposedHeaders = $headers;

        return $clone;
    }

    /**
     * Set whether to use cache control headers.
     */
    public function withCache(bool $useCache): self
    {
        $clone = clone $this;
        $clone->useCache = $useCache;

        return $clone;
    }

    /**
     * Check if origin is allowed.
     */
    private function isOriginAllowed(string $origin): bool
    {
        if ($this->allowedOrigins === null) {
            return true;
        }

        return in_array($origin, $this->allowedOrigins, true);
    }
}
