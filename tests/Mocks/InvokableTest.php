<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Mocks;

class InvokableTest
{
    public static $CalledCount = 0;

    public function __invoke()
    {
        return static::$CalledCount++;
    }
}
