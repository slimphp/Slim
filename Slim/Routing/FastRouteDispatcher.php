<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use FastRoute\Dispatcher\GroupCountBased;
use Slim\Interfaces\DispatcherInterface;
use Slim\Interfaces\RouterInterface;

final class FastRouteDispatcher implements DispatcherInterface
{
    private RouterInterface $router;

    private ?GroupCountBased $dispatcher = null;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function dispatch(string $httpMethod, string $uri): array
    {
        return $this->getDispatcher()->dispatch($httpMethod, $uri);
    }

    private function getDispatcher(): GroupCountBased
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = new GroupCountBased(
                $this->router->getRouteCollector()->getData()
            );
        }

        return $this->dispatcher;
    }
}
