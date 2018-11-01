<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Mocks;

use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * Mock object for Slim\Tests\CallableResolverTest
 */
class CallableTest
{
    public static $CalledCount = 0;

    public static $CalledContainer = null;

    public function __construct($container = null)
    {
        static::$CalledContainer = $container;
    }

    public function toCall()
    {
        static::$CalledCount++;

        $psr17Factory = new Psr17Factory();
        return $psr17Factory->createResponse();
    }
}
