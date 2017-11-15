<?php

namespace Slim\Tests\Mocks\ResolvingCallableDependencies;


class MockDependency
{

    public function process()
    {
        return "processed";
    }

}