<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Mocks;

/**
 * Mock object for Slim\Tests\CallableResolverTest
 */
class InvokableTest
{
    public static $CalledCount = 0;
    public function __invoke()
    {
        return static::$CalledCount++;
    }
}
