<?php

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class FormUrlEncodedBodyParserMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = strtolower($request->getHeaderLine('Content-Type'));

        if ($this->isFormUrlEncodedMediaType($contentType)) {
            $body = (string)$request->getBody();

            if ($body === '') {
                return $handler->handle($request);
            }

            parse_str($body, $parsed);

            if (!is_array($parsed)) {
                throw new RuntimeException('Invalid URL-encoded body.');
            }

            $request = $request->withParsedBody($parsed);
        }

        return $handler->handle($request);
    }

    private function isFormUrlEncodedMediaType(string $contentType): bool
    {
        $type = strtolower(trim(explode(';', $contentType)[0]));

        return $type === 'application/x-www-form-urlencoded';
    }
}
