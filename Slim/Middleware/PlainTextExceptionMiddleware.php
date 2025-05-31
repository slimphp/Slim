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

final class PlainTextExceptionMiddleware implements MiddlewareInterface
{
    use ExceptionMiddlewareTrait;

    private const DEFAULT_TYPE = 'text/plain';

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

    private function createPayload(Throwable $exception): string
    {
        $text = sprintf("%s\n", $this->getErrorTitle($exception));

        if ($this->displayErrorDetails) {
            $text .= $this->formatExceptionFragment($exception);

            while ($exception = $exception->getPrevious()) {
                $text .= "\nPrevious Exception:\n";
                $text .= $this->formatExceptionFragment($exception);
            }
        }

        return $text;
    }

    private function formatExceptionFragment(Throwable $exception): string
    {
        $text = sprintf("Type: %s\n", get_class($exception));

        $code = $exception instanceof ErrorException ? $exception->getSeverity() : $exception->getCode();

        $text .= sprintf("Code: %s\n", $code);
        $text .= sprintf("Message: %s\n", $exception->getMessage());
        $text .= sprintf("File: %s\n", $exception->getFile());
        $text .= sprintf("Line: %s\n", $exception->getLine());
        $text .= sprintf('Trace: %s', $exception->getTraceAsString());

        return $text;
    }
}
