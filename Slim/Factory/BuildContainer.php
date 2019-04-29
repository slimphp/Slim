<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory;

use Psr\Container\ContainerInterface;
use RuntimeException;

final class BuildContainer implements ContainerInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string[]
     */
    private $services = [];

    /**
     * @var array
     */
    private $instances = [];

    public function __construct(ServiceProvider $provider, ContainerInterface $container = null)
    {
        $this->services = $provider->getServices();
        $this->container = $container;
    }

    public function has($id)
    {
        if ($this->container && $this->container->has($id)) {
            return true;
        }
        return array_key_exists($id, $this->services);
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        if ($this->container && $this->container->has($id)) {
            return $this->container->get($id);
        }
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }
        if (isset($this->services[$id])) {
            $service = $this->services[$id];
            $found = $service($this);
            $this->instances[$id] = $found;
            return $found;
        }
        throw new RuntimeException(
            "Could not find service: {$id}. "
        );
    }

    /**
     * @param string $id
     * @param mixed $entry
     */
    public function set($id, $entry)
    {
        $this->instances[$id] = $entry;
    }

    /**
     * @return ContainerInterface|null
     */
    public function getContainer()
    {
        return $this->container;
    }
}
