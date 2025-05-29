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
        $contentType = strtolower($request->getHeaderLine('Content-Type'));

        if ($this->isJsonMediaType($contentType)) {
            $body = (string)$request->getBody();
            $parsed = json_decode($body, true, 512, $this->flags);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed)) {
                throw new RuntimeException('Invalid JSON body: ' . json_last_error_msg());
            }

            $request = $request->withParsedBody($parsed);
        }

        return $handler->handle($request);
    }

    /**
     * Check whether the content type is JSON or has a +json structured suffix.
     */
    private function isJsonMediaType(string $contentType): bool
    {
        // Remove parameters (e.g. "; charset=utf-8")
        $type = strtolower(trim(explode(';', $contentType)[0]));

        return $type === 'application/json' || str_ends_with($type, '+json');
    }
}
