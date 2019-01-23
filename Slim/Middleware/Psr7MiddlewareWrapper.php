<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class LegacyMiddlewareWrapper
 * @package Slim\Middleware
 */
class Psr7MiddlewareWrapper implements MiddlewareInterface
{
    /**
     * @var callable
     */
    private $callable;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * LegacyMiddlewareWrapper constructor.
     * @param callable $callable
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(callable $callable, ResponseFactoryInterface $responseFactory)
    {
        $this->callable = $callable;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        $next = function (ServerRequestInterface $request) use ($handler) {
            return $handler->handle($request);
        };
        return call_user_func($this->callable, $request, $response, $next);
    }
}
