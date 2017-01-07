<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
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
