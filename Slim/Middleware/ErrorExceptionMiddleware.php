<?php

declare(strict_types=1);

namespace Slim\Middleware;

use ErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Converts errors into ErrorException instances.
 */
final class ErrorExceptionMiddleware implements MiddlewareInterface
{
    /**
     * Process.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @throws ErrorException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $errorHandler = set_error_handler(function ($code, $message, $file, $line) {
            $level = error_reporting();
            if (($level & $code) === 0) {
                // silent error
                return false;
            }

            throw new ErrorException($message, 0, $code, $file, $line);
        }, E_ALL);

        try {
            $response = $handler->handle($request);
        } finally {
            if ($errorHandler) {
                restore_error_handler();
            }
        }

        return $response;
    }
}
