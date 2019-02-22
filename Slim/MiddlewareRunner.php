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
     * @param array $middleware List of middleware in LIFO order
     */
    public function __construct(array $middleware = [])
    {
        $this->setMiddleware($middleware);
    }

    /**
     * @param MiddlewareInterface $middleware
     * @return self
     */
    public function add(MiddlewareInterface $middleware): self
    {
        array_unshift($this->middleware, $middleware);
        return $this;
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

        $stages = $this->buildStages();
        $runner = new MiddlewareRunner();
        $runner->setStages($stages);
        return $runner->handle($request);
    }

    /**
     * @return SplObjectStorage
     */
    protected function buildStages(): SplObjectStorage
    {
        $stages = new SplObjectStorage();
        foreach ($this->middleware as $middleware) {
            $stages->attach($middleware);
        }
        $stages->rewind();
        return $stages;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!($this->stages instanceof SplObjectStorage)) {
            throw new RuntimeException(
                'Middleware queue stages have not been set yet. '.
                'Please use the `MiddlewareRunner::run()` method.'
            );
        }

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
     * @param MiddlewareInterface[] $middleware List of middleware in LIFO order
     * @return self
     */
    public function setMiddleware(array $middleware): self
    {
        $this->middleware = [];
        while ($item = array_pop($middleware)) {
            $this->add($item);
        }
        return $this;
    }

    /**
     * @param SplObjectStorage $stages
     * @return self
     */
    public function setStages(SplObjectStorage $stages): self
    {
        $this->stages = $stages;
        return $this;
    }
}
