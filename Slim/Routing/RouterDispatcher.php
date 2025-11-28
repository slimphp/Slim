<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\ContainerResolverInterface;

/**
 * Router request handler.
 */
final class RouterDispatcher implements RequestHandlerInterface
{
    private Router $router;

    private ContainerResolverInterface $resolver;

    public function __construct(Router $router, ContainerResolverInterface $resolver)
    {
        $this->router = $router;
        $this->resolver = $resolver;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middlewares = $this->router->getMiddleware();

        foreach ($middlewares as $key => $value) {
            $middlewares[$key] = $this->resolver->resolveMiddleware($value);
        }

        return (new PipelineRunner($middlewares))->handle($request);
    }
}
