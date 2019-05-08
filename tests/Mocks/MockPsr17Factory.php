<?php
declare(strict_types=1);

namespace Slim\Tests\Mocks;

use Slim\Factory\Psr17\Psr17Factory;

class MockPsr17Factory extends Psr17Factory
{
    protected static $responseFactoryClass = '';
    protected static $streamFactoryClass = '';
    protected static $serverRequestCreatorClass = '';
    protected static $serverRequestCreatorMethod = '';
}
