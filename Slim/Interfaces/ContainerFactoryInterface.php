<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

use Psr\Container\ContainerInterface;

/**
 * Factory interface for creating a service container.
 */
interface ContainerFactoryInterface
{
    /**
     * Create a container instance.
     *
     * @param array<string, mixed> $definitions The service definitions.
     *
     * @return ContainerInterface
     */
    public function createContainer(array $definitions = []): ContainerInterface;
}
