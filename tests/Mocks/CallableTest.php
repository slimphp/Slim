<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Mocks;

use Slim\Tests\Providers\PSR7ObjectProvider;

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

        $psr7ObjectProvider = new PSR7ObjectProvider();
        return $psr7ObjectProvider->createResponse();
    }
}
