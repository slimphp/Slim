<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use ErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class JsonExceptionMiddleware implements MiddlewareInterface
{
    use ExceptionMiddlewareTrait;

    private const DEFAULT_TYPE = 'application/json';

    private int $jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            $contentType = $this->detectMediaType($request);

            if ($contentType === null) {
                throw $exception;
            }

            return $this->createResponse($exception, $this->createPayload($exception), $contentType);
        }
    }

    /**
     * Set options for JSON encoding.
     *
     * @see https://php.net/manual/function.json-encode.php
     * @see https://php.net/manual/json.constants.php
     */
    public function withJsonOptions(int $options): self
    {
        $clone = clone $this;
        $clone->jsonOptions = $options;

        return $clone;
    }

    private function createPayload(Throwable $exception): string
    {
        $payload = ['message' => $this->getErrorTitle($exception)];

        if ($this->displayErrorDetails) {
            $payload['exception'] = [];
            do {
                $payload['exception'][] = $this->formatExceptionFragment($exception);
            } while ($exception = $exception->getPrevious());
        }

        return (string)json_encode($payload, $this->jsonOptions);
    }

    private function formatExceptionFragment(Throwable $exception): array
    {
        $code = $exception instanceof ErrorException ? $exception->getSeverity() : $exception->getCode();

        return [
            'type' => get_class($exception),
            'code' => $code,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
    }
}
