<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim;

use Psr\Container\ContainerInterface;

/**
 * Container Awareness
 *
 * Anything that requires knowledge of a ContainerInterface should use or extend this trait.
 */
trait ContainerAwareTrait
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Set a container instance
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get the container instance
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }
}
