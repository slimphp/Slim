<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpBadRequestException;

use function in_array;
use function json_decode;

final class JsonBodyParserMiddleware implements MiddlewareInterface
{
    private int $flags;

    public function __construct(int $jsonFlags = 0)
    {
        $this->flags = $jsonFlags;
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

            try {
                $parsed = json_decode($body, true, 512, $this->flags | JSON_THROW_ON_ERROR);
            } catch (JsonException $jsonException) {
                throw new HttpBadRequestException(
                    $request,
                    sprintf('Invalid JSON body: %s', $jsonException->getMessage()),
                    $jsonException
                );
            }

            if (is_array($parsed)) {
                $request = $request->withParsedBody($parsed);
            }
        }

        return $handler->handle($request);
    }

    /**
     * Check whether the content type is JSON or has a +json structured suffix.
     *
     * @param string $contentType
     */
    private function isJsonMediaType(string $contentType): bool
    {
        $contentType = strtolower(trim(explode(';', $contentType)[0]));

        return $contentType === 'application/json' || str_ends_with($contentType, '+json');
    }
}
