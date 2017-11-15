<?php

namespace Slim\Tests\Mocks\ResolvingCallableDependencies;

/**
 * Mock object for Slim\Tests\CallableResolverTest
 */
class MultipleUnTypedDependencyTest
{
    public function __construct($dependencyA, $dependencyB)
    {
    }
}