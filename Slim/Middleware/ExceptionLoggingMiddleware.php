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
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

final class ExceptionLoggingMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    private bool $logErrorDetails = false;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Process.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @throws Throwable
     * @throws ErrorException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ErrorException $exception) {
            $errorLevels = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
            $level = in_array($exception->getSeverity(), $errorLevels) ? LogLevel::ERROR : LogLevel::WARNING;

            $context = $this->getContext($exception, $request);
            $this->logger->log($level, $exception->getMessage(), $context);

            throw $exception;
        } catch (Throwable $exception) {
            $context = $this->getContext($exception, $request);
            $this->logger->error($exception->getMessage(), $context);

            throw $exception;
        }
    }

    public function withLogErrorDetails(bool $logErrorDetails): self
    {
        $clone = clone $this;
        $clone->logErrorDetails = $logErrorDetails;

        return $clone;
    }

    private function getContext(Throwable $exception, ServerRequestInterface $request): array
    {
        $context = [];

        if ($this->logErrorDetails) {
            $context = [
                'exception' => $exception,
                'request' => $request,
            ];
        }

        return $context;
    }
}
