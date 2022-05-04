<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Mocks;

use Slim\Factory\Psr17\Psr17Factory;

class MockPsr17Factory extends Psr17Factory
{
    protected static string $responseFactoryClass = '';
    protected static string $streamFactoryClass = '';
    protected static string $serverRequestCreatorClass = '';
    protected static string $serverRequestCreatorMethod = '';
}
