<?php

namespace Slim\Tests\Mocks\ResolvingCallableDependencies;

/**
 * Mock object for Slim\Tests\CallableResolverTest
 */
class SingleUnTypeHintedContainerDependencyTest
{
    private $container;

    public function __construct($container = null)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }
}