<?php

namespace Slim\Tests\Mocks\ResolvingCallableDependencies;

use Psr\Container\ContainerInterface;

/**
 * Mock object for Slim\Tests\CallableResolverTest
 */
class MultipleDependencyTest
{
    private $dependency;
    private $container;

    public function __construct(MockDependency $dependency, ContainerInterface $container = null)
    {
        $this->dependency = $dependency;
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function getDependency()
    {
        return $this->dependency;
    }

    public function getInstance()
    {
        return $this;
    }
}