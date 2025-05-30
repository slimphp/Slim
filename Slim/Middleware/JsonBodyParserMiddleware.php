<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class JsonBodyParserMiddleware implements MiddlewareInterface
{
    private int $flags;

    public function __construct(int $flags = 0)
    {
        $this->flags = $flags;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $contentType = $request->getHeaderLine('Content-Type');

        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $handler->handle($request);
        }

        if ($this->isJsonMediaType($contentType)) {
            $body = (string)$request->getBody();
            $parsed = json_decode($body, true, 512, $this->flags);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException(sprintf('Invalid JSON body: %s', json_last_error_msg()));
            }

            if (is_array($parsed)) {
                $request = $request->withParsedBody($parsed);
            }
        }

        return $handler->handle($request);
    }

    /**
     * Check whether the content type is JSON or has a +json structured suffix.
     */
    private function isJsonMediaType(string $contentType): bool
    {
        $contentType = strtolower(trim(explode(';', $contentType)[0]));

        return $contentType === 'application/json' || str_ends_with($contentType, '+json');
    }
}
