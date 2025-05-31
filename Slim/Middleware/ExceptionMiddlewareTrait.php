<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Media\MediaTypeParser;
use Throwable;

trait ExceptionMiddlewareTrait
{
    private ResponseFactoryInterface $responseFactory;

    private string $defaultErrorTitle = 'Application Error';

    private string $defaultErrorDescription = 'A website error has occurred. Sorry for the temporary inconvenience.';

    private bool $displayErrorDetails = false;

    private array $mimeTypes = [self::DEFAULT_TYPE => 1];

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function withErrorDetails(bool $flag): self
    {
        $clone = clone $this;
        $clone->displayErrorDetails = $flag;

        return $clone;
    }

    public function withMimeType(string $mimeType): self
    {
        $clone = clone $this;
        $clone->mimeTypes[$mimeType] = 1;

        return $clone;
    }

    private function getErrorTitle(Throwable $exception): string
    {
        if ($exception instanceof HttpException) {
            return $exception->getTitle();
        }

        return $this->defaultErrorTitle;
    }

    private function getErrorDescription(Throwable $exception): string
    {
        if ($exception instanceof HttpException) {
            return $exception->getDescription();
        }

        return $this->defaultErrorDescription;
    }

    private function detectMediaType(ServerRequestInterface $request): ?string
    {
        $accept = $request->getHeaderLine('Accept');

        // Performance optimized for most cases
        if ($accept === self::DEFAULT_TYPE || isset($this->mimeTypes['*/*'])) {
            return self::DEFAULT_TYPE;
        }

        // Parses complex Accept headers
        $mimeTypes = $this->parseAcceptHeader($accept);

        foreach ($mimeTypes as $type) {
            if (isset($this->mimeTypes[$type])) {
                return $type;
            }
        }

        return null;
    }

    private function createResponse(Throwable $exception, string $payload, string $contentType): ResponseInterface
    {
        $response = $this->responseFactory
            ->createResponse(500)
            ->withHeader('Content-Type', $contentType);

        $response->getBody()->write($payload);

        if ($exception instanceof HttpMethodNotAllowedException) {
            $allowedMethods = implode(', ', $exception->getAllowedMethods());
            $response = $response->withHeader('Allow', $allowedMethods);
        }

        return $response;
    }

    /**
     * Parses the 'Accept' header to extract media types.
     * This method doesn't consider the quality values (q-values).
     *
     * @param string|null $accept The value of the 'Accept' header
     *
     * @return array An array of normalized media types from the 'Accept' header
     */
    public function parseAcceptHeader(?string $accept): array
    {
        $acceptTypes = $accept ? explode(',', $accept) : [];

        $cleanTypes = [];

        foreach ($acceptTypes as $type) {
            // Remove optional parameters like ";q=0.8"
            $name = strtolower(trim(explode(';', $type)[0]));
            $cleanTypes[] = $name;
        }

        return $cleanTypes;
    }
}
