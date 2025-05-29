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
use Slim\Media\MediaType;
use Slim\Media\MediaTypeDetector;

use function is_array;
use function is_object;
use function json_decode;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function parse_str;
use function simplexml_load_string;

final class BodyParsingMiddleware implements MiddlewareInterface
{
    private MediaTypeDetector $mediaTypeDetector;

    private array $handlers = [];

    private string $defaultMediaType = 'text/html';

    public function __construct(MediaTypeDetector $mediaTypeDetector)
    {
        $this->mediaTypeDetector = $mediaTypeDetector;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->handlers) {
            throw new RuntimeException('No body parsing handlers defined');
        }

        $parsedBody = $request->getParsedBody();

        if (empty($parsedBody)) {
            $parsedBody = $this->parseBody($request);
            $request = $request->withParsedBody($parsedBody);
        }

        return $handler->handle($request);
    }

    /**
     * @param string $mediaType The HTTP media type (excluding content-type params)
     * @param callable $handler The callable that returns parsed contents for media type
     */
    public function withBodyParser(string $mediaType, callable $handler): self
    {
        $clone = clone $this;
        $clone->handlers[$mediaType] = $handler;

        return $clone;
    }

    public function withDefaultMediaType(string $mediaType): self
    {
        $clone = clone $this;
        $clone->defaultMediaType = $mediaType;

        return $clone;
    }

    public function withDefaultBodyParsers(): self
    {
        $clone = clone $this;
        $clone = $clone->withBodyParser(MediaType::APPLICATION_JSON, function ($input) {
            $result = json_decode($input, true);

            if (!is_array($result)) {
                return null;
            }

            return $result;
        });

        $clone = $clone->withBodyParser(MediaType::APPLICATION_FORM_URLENCODED, function ($input) {
            parse_str($input, $data);

            return $data;
        });

        $xmlCallable = function ($input) {
            $backup_errors = libxml_use_internal_errors(true);
            $result = simplexml_load_string($input);

            libxml_clear_errors();
            libxml_use_internal_errors($backup_errors);

            if ($result === false) {
                return null;
            }

            return $result;
        };

        return $clone
            ->withBodyParser(MediaType::APPLICATION_XML, $xmlCallable)
            ->withBodyParser(MediaType::TEXT_XML, $xmlCallable);
    }

    /**
     * Parse request body.
     *
     * @throws RuntimeException
     */
    private function parseBody(ServerRequestInterface $request): array|object|null
    {
        // Negotiate content type
        $contentTypes = $this->mediaTypeDetector->detect($request);
        $contentType = $contentTypes[0] ?? $this->defaultMediaType;

        // Determine which handler to use based on media type
        $handler = $this->handlers[$contentType] ?? reset($this->handlers);

        // Invoke the parser
        $parsed = call_user_func(
            $handler,
            (string)$request->getBody()
        );

        if ($parsed === null || is_object($parsed) || is_array($parsed)) {
            return $parsed;
        }

        throw new RuntimeException(
            'Request body media type parser return value must be an array, an object, or null.'
        );
    }
}
