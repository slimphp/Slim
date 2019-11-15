<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class BodyParsingMiddleware implements MiddlewareInterface
{
    /**
     * @var callable[]
     */
    protected $bodyParsers;

    /**
     * @param callable[] $bodyParsers list of body parsers as an associative array of mediaType => callable
     */
    public function __construct(array $bodyParsers = [])
    {
        $this->registerDefaultBodyParsers();

        foreach ($bodyParsers as $mediaType => $parser) {
            $this->registerBodyParser($mediaType, $parser);
        }
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        if ($parsedBody === null || empty($parsedBody)) {
            $parsedBody = $this->parseBody($request);
            $request = $request->withParsedBody($parsedBody);
        }

        return $handler->handle($request);
    }

    /**
     * @param string   $mediaType A HTTP media type (excluding content-type params).
     * @param callable $callable  A callable that returns parsed contents for media type.
     * @return self
     */
    public function registerBodyParser(string $mediaType, callable $callable): self
    {
        $this->bodyParsers[$mediaType] = $callable;
        return $this;
    }

    /**
     * @param string   $mediaType A HTTP media type (excluding content-type params).
     * @return boolean
     */
    public function hasBodyParser(string $mediaType): bool
    {
        return isset($this->bodyParsers[$mediaType]);
    }

    /**
     * @param string    $mediaType A HTTP media type (excluding content-type params).
     * @return callable
     * @throws RuntimeException
     */
    public function getBodyParser(string $mediaType): callable
    {
        if (!isset($this->bodyParsers[$mediaType])) {
            throw new RuntimeException('No parser for type ' . $mediaType);
        }
        return $this->bodyParsers[$mediaType];
    }


    protected function registerDefaultBodyParsers(): void
    {
        $this->registerBodyParser('application/json', function ($input) {
            $result = \json_decode($input, true);

            if (!\is_array($result)) {
                return null;
            }

            return $result;
        });

        $this->registerBodyParser('application/x-www-form-urlencoded', function ($input) {
            \parse_str($input, $data);
            return $data;
        });

        $xmlCallable = function ($input) {
            $backup = \libxml_disable_entity_loader(true);
            $backup_errors = \libxml_use_internal_errors(true);
            $result = \simplexml_load_string($input);

            \libxml_disable_entity_loader($backup);
            \libxml_clear_errors();
            \libxml_use_internal_errors($backup_errors);

            if ($result === false) {
                return null;
            }

            return $result;
        };

        $this->registerBodyParser('application/xml', $xmlCallable);
        $this->registerBodyParser('text/xml', $xmlCallable);
    }

    /**
     * @param ServerRequestInterface $request
     * @return null|array|object
     */
    protected function parseBody(ServerRequestInterface $request)
    {
        $mediaType = $this->getMediaType($request);
        if ($mediaType === null) {
            return null;
        }

        // Check if this specific media type has a parser registered first
        if (!isset($this->bodyParsers[$mediaType])) {
            // If not, look for a media type with a structured syntax suffix (RFC 6839)
            $parts = \explode('+', $mediaType);
            if (\count($parts) >= 2) {
                $mediaType = 'application/' . $parts[\count($parts) - 1];
            }
        }

        if (isset($this->bodyParsers[$mediaType])) {
            $body = (string)$request->getBody();
            $parsed = $this->bodyParsers[$mediaType]($body);

            if (!\is_null($parsed) && !\is_object($parsed) && !\is_array($parsed)) {
                throw new RuntimeException(
                    'Request body media type parser return value must be an array, an object, or null'
                );
            }

            return $parsed;
        }

        return null;
    }

    /**
     * @param ServerRequestInterface $request
     * @return string|null The serverRequest media type, minus content-type params
     */
    protected function getMediaType(ServerRequestInterface $request): ?string
    {
        $contentType = $request->getHeader('Content-Type')[0] ?? null;

        if (\is_string($contentType) && \trim($contentType) != '') {
            $contentTypeParts = \explode(';', $contentType);
            return \strtolower(\trim($contentTypeParts[0]));
        }

        return null;
    }
}
