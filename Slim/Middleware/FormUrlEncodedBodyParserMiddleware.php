<?php

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class FormUrlEncodedBodyParserMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $contentType = $request->getHeaderLine('Content-Type');

        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $handler->handle($request);
        }

        if ($this->isFormUrlEncodedMediaType($contentType)) {
            $body = (string) $request->getBody();
            parse_str($body, $parsed);
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
