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

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Class DeferredResolutionMiddleware
 * @package Slim\Middleware
 */
class DeferredResolutionMiddleware implements MiddlewareInterface
{
    /**
     * @var string
     */
    private $resolvable;

    /**
     * @var Closure|null
     */
    private $deferredContainerResolver;

    /**
     * DeferredResolutionMiddleware constructor.
     * @param string $resolvable
     * @param Closure|null $deferredContainerResolver
     */
    public function __construct(string $resolvable, Closure $deferredContainerResolver = null)
    {
        $this->resolvable = $resolvable;
        $this->deferredContainerResolver = $deferredContainerResolver;
    }

    /**
     * @return MiddlewareInterface
     */
    protected function resolve(): MiddlewareInterface
    {
        /** @var ContainerInterface|null $container */
        $container = $this->deferredContainerResolver ? ($this->deferredContainerResolver)() : null;

        if ($container instanceof ContainerInterface && $container->has($this->resolvable)) {
            $resolved = $container->get($this->resolvable);
        } else {
            if (!class_exists($this->resolvable)) {
                throw new RuntimeException(sprintf('Middleware %s does not exist', $this->resolvable));
            }
            $resolved = new $this->resolvable;
        }

        if (!($resolved instanceof MiddlewareInterface)) {
            throw new RuntimeException(sprintf(
                'Middleware %s does not implement MiddlewareInterface',
                $this->resolvable
            ));
        }

        return $resolved;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->resolve()->process($request, $handler);
    }
}
