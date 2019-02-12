<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use SplObjectStorage;

/**
 * Class MiddlewareRunner
 * @package Slim
 */
class MiddlewareRunner implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    protected $middleware;

    /**
     * @var SplObjectStorage
     */
    protected $stages;

    /**
     * MiddlewareRunner constructor.
     * @param array $middleware
     */
    public function __construct(array $middleware = [])
    {
        $this->middleware = $middleware;
    }

    /**
     * @param MiddlewareInterface $middleware
     */
    public function add(MiddlewareInterface $middleware)
    {
        array_unshift($this->middleware, $middleware);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function run(ServerRequestInterface $request): ResponseInterface
    {
        if (empty($this->middleware)) {
            throw new RuntimeException('Middleware queue should not be empty.');
        }

        $this->stages = new SplObjectStorage();
        foreach ($this->middleware as $middleware) {
            if (! $middleware instanceof MiddlewareInterface) {
                throw new RuntimeException(
                    'All middleware should implement `MiddlewareInterface`. '.
                    'For PSR-7 middleware use the `Psr7MiddlewareAdapter` class.'
                );
            }

            $this->stages->attach($middleware);
        }
        $this->stages->rewind();

        return $this->handle($request);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var MiddlewareInterface $stage */
        $stage = $this->stages->current();
        $this->stages->next();
        return $stage->process($request, $this);
    }

    /**
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * @param MiddlewareInterface[] $middleware
     */
    public function setMiddleware(array $middleware)
    {
        $this->middleware = $middleware;
    }
}
