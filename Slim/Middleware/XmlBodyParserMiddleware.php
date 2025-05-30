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

final class XmlBodyParserMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $contentType = $request->getHeaderLine('Content-Type');

        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $handler->handle($request);
        }

        if ($this->isXmlMediaType($contentType)) {
            $backup = libxml_use_internal_errors(true);
            $body = (string)$request->getBody();
            $xml = simplexml_load_string($body);

            libxml_clear_errors();
            libxml_use_internal_errors($backup);

            if ($xml === false) {
                throw new RuntimeException('Invalid XML body');
            }

            $request = $request->withParsedBody($xml);
        }

        return $handler->handle($request);
    }

    private function isXmlMediaType(string $contentType): bool
    {
        $contentType = strtolower(trim(explode(';', $contentType)[0]));

        return $contentType === 'application/xml'
            || $contentType === 'text/xml'
            || str_ends_with($contentType, '+xml');
    }
}
