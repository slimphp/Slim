<?php

namespace Slim\Tests\Mocks;

use Slim\Handlers\Strategies\RequestResponseArgs;
use Slim\ServiceConfig;

/**
 * Mock object for Slim\Tests\AppTest
 */
class ServiceConfigStub extends ServiceConfig
{
    /**
     * @return \Slim\Interfaces\InvocationStrategyInterface
     */
    public function newFoundHandler()
    {
        return new RequestResponseArgs();
    }
}
