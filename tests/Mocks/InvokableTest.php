<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Mocks;

class InvokableTest
{
    public static $CalledCount = 0;

    public function __invoke()
    {
        return static::$CalledCount++;
    }
}
