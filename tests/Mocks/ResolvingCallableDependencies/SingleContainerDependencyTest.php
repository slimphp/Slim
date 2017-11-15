<?php

namespace Slim\Tests\Mocks\ResolvingCallableDependencies;

use Psr\Container\ContainerInterface;

/**
 * Mock object for Slim\Tests\CallableResolverTest
 */
class SingleContainerDependencyTest
{
    private $container;

    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }
}