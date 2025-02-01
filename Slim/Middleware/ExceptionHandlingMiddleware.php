<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Interfaces\ExceptionRendererInterface;
use Slim\Media\MediaTypeDetector;
use Throwable;

/**
 * This middleware handles exceptions that occur during the processing of an HTTP request.
 * It catches any `Throwable` thrown by the subsequent middleware or request handler and delegates
 * the handling of the exception to a configured `ExceptionHandlerInterface` implementation.
 *
 * This middleware ensures that the application can gracefully handle errors and return an appropriate
 * response to the client.
 */
final class ExceptionHandlingMiddleware implements MiddlewareInterface
{
    private ResponseFactoryInterface $responseFactory;

    private MediaTypeDetector $mediaTypeDetector;

    private ContainerResolverInterface $resolver;

    private bool $displayErrorDetails = false;

    private string $defaultMediaType = 'text/html';

    private array $handlers = [];

    /**
     * @var callable|ExceptionRendererInterface|string|null
     */
    private $defaultHandler = null;

    public function __construct(
        ContainerResolverInterface $resolver,
        ResponseFactoryInterface $responseFactory,
        MediaTypeDetector $mediaTypeDetector,
    ) {
        $this->resolver = $resolver;
        $this->responseFactory = $responseFactory;
        $this->mediaTypeDetector = $mediaTypeDetector;
    }

    public static function createFromContainer(ContainerInterface $container): self
    {
        return new self(
            $container->get(ContainerResolverInterface::class),
            $container->get(ResponseFactoryInterface::class),
            $container->get(MediaTypeDetector::class)
        );
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            $statusCode = $this->determineStatusCode($request, $exception);
            $mediaType = $this->negotiateMediaType($request);
            $response = $this->createResponse($statusCode, $mediaType, $exception);
            $handler = $this->negotiateHandler($mediaType);

            // Invoke the formatter handler
            return call_user_func(
                $handler,
                $request,
                $response,
                $exception,
                $this->displayErrorDetails
            );
        }
    }

    public function withDefaultMediaType(string $defaultMediaType): self
    {
        $clone = clone $this;
        $clone->defaultMediaType = $defaultMediaType;

        return $clone;
    }

    public function withDisplayErrorDetails(bool $displayErrorDetails): self
    {
        $clone = clone $this;
        $clone->displayErrorDetails = $displayErrorDetails;

        return $clone;
    }

    public function withDefaultHandler(ExceptionRendererInterface|callable|string $handler): self
    {
        $clone = clone $this;
        $clone->defaultHandler = $handler;

        return $clone;
    }

    public function withHandler(string $mediaType, ExceptionRendererInterface|callable|string $handler): self
    {
        $clone = clone $this;
        $clone->handlers[$mediaType] = $handler;

        return $clone;
    }

    public function withoutHandlers(): self
    {
        $clone = clone $this;
        $clone->handlers = [];
        $clone->defaultHandler = null;

        return $clone;
    }

    private function negotiateMediaType(ServerRequestInterface $request): string
    {
        $mediaTypes = $this->mediaTypeDetector->detect($request);

        return $mediaTypes[0] ?? $this->defaultMediaType;
    }

    /**
     * Determine which handler to use based on media type.
     */
    private function negotiateHandler(string $mediaType): callable
    {
        $handler = $this->handlers[$mediaType] ?? $this->defaultHandler ?? reset($this->handlers);

        if (!$handler) {
            throw new RuntimeException(sprintf('Exception handler for "%s" not found', $mediaType));
        }

        return $this->resolver->resolveCallable($handler);
    }

    private function determineStatusCode(ServerRequestInterface $request, Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->getCode();
        }

        if ($request->getMethod() === 'OPTIONS') {
            return 200;
        }

        return 500;
    }

    private function createResponse(
        int $statusCode,
        string $contentType,
        Throwable $exception,
    ): ResponseInterface {
        $response = $this->responseFactory
            ->createResponse($statusCode)
            ->withHeader('Content-Type', $contentType);

        if ($exception instanceof HttpMethodNotAllowedException) {
            $allowedMethods = implode(', ', $exception->getAllowedMethods());
            $response = $response->withHeader('Allow', $allowedMethods);
        }

        return $response;
    }
}
