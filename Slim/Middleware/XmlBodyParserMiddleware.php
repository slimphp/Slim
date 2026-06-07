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
use Slim\Exception\HttpBadRequestException;

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
            $body = (string)$request->getBody();

            $options = LIBXML_NONET;

            // PHP 8.4+ provides explicit XXE hardening flag.
            if (defined('LIBXML_NO_XXE')) {
                $options |= LIBXML_NO_XXE;
            }

            $backup = libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body, 'SimpleXMLElement', $options);

            libxml_clear_errors();
            libxml_use_internal_errors($backup);

            if ($xml === false) {
                throw new HttpBadRequestException($request, 'Invalid XML body');
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
