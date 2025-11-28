<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Container;

use DI\Container;
use Psr\Container\ContainerInterface;
use Slim\Container\Definition\HttpDefinitions;
use Slim\Container\Definition\SlimDefinitions;
use Slim\Interfaces\ContainerFactoryInterface;

final class DiContainerFactory implements ContainerFactoryInterface
{
    public function createContainer(array $definitions = []): ContainerInterface
    {
        return new Container(
            array_replace(
                (new SlimDefinitions())->getDefinitions(),
                (new HttpDefinitions())->getDefinitions(),
                $definitions
            )
        );
    }
}
