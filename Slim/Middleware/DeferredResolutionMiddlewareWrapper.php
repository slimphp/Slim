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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Class DeferredResolutionMiddlewareWrapper
 * @package Slim\Middleware
 */
class DeferredResolutionMiddlewareWrapper implements MiddlewareInterface
{
    /**
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @var string
     */
    private $resolvable;

    /**
     * DeferredResolutionMiddlewareWrapper constructor.
     * @param ContainerInterface|null $container
     * @param string $resolvable
     */
    public function __construct(string $resolvable, ContainerInterface $container = null)
    {
        $this->resolvable = $resolvable;
        $this->container = $container;
    }

    /**
     * @return MiddlewareInterface
     */
    protected function resolve(): MiddlewareInterface
    {
        if ($this->container instanceof ContainerInterface && $this->container->has($this->resolvable)) {
            $resolved = $this->container->get($this->resolvable);
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
