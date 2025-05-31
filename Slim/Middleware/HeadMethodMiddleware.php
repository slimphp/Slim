<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware to handle HTTP HEAD requests.
 *
 * Ensures that the response body is empty for HEAD requests in compliance with RFC 2616, Section 9.
 * This is to avoid unintended content in the response body if the route handler was originally
 * intended for GET requests.
 */
final class HeadMethodMiddleware implements MiddlewareInterface
{
    private StreamFactoryInterface $streamFactory;

    public function __construct(StreamFactoryInterface $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        /**
         * This is to be in compliance with RFC 2616, Section 9.
         * If the incoming request method is HEAD, we need to ensure that the response body
         * is empty as the request may fall back on a GET route handler due to FastRoute's
         * routing logic which could potentially append content to the response body
         * https://www.rfc-editor.org/rfc/rfc9110.html#name-head.
         */
        $method = strtoupper($request->getMethod());
        if ($method === 'HEAD') {
            return $response->withBody($this->streamFactory->createStream());
        }

        return $response;
    }
}
