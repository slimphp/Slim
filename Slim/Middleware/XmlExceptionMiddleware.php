<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use DOMDocument;
use ErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class XmlExceptionMiddleware implements MiddlewareInterface
{
    use ExceptionMiddlewareTrait;

    private const DEFAULT_TYPE = 'application/xml';

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
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $errorElement = $dom->createElement('error');
        $dom->appendChild($errorElement);

        $messageElement = $dom->createElement('message', $this->getErrorTitle($exception));
        $errorElement->appendChild($messageElement);

        // If error details should be displayed
        if ($this->displayErrorDetails) {
            do {
                $exceptionElement = $dom->createElement('exception');

                $typeElement = $dom->createElement('type', get_class($exception));
                $exceptionElement->appendChild($typeElement);

                $code = $exception instanceof ErrorException ? $exception->getSeverity() : $exception->getCode();
                $codeElement = $dom->createElement('code', (string) $code);
                $exceptionElement->appendChild($codeElement);

                $messageElement = $dom->createElement('message', $exception->getMessage());
                $exceptionElement->appendChild($messageElement);

                $fileElement = $dom->createElement('file', $exception->getFile());
                $exceptionElement->appendChild($fileElement);

                $lineElement = $dom->createElement('line', (string) $exception->getLine());
                $exceptionElement->appendChild($lineElement);

                $errorElement->appendChild($exceptionElement);
            } while ($exception = $exception->getPrevious());
        }

        return (string) $dom->saveXML();
    }
}
