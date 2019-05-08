<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Factory\Psr17;

use RuntimeException;
use Slim\Tests\Mocks\MockPsr17Factory;
use Slim\Tests\TestCase;

class Psr17FactoryTest extends TestCase
{
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Slim\Tests\Mocks\MockPsr17Factory could not instantiate a response factory.
     */
    public function testGetResponseFactoryThrowsRuntimeException()
    {
        MockPsr17Factory::getResponseFactory();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Slim\Tests\Mocks\MockPsr17Factory could not instantiate a stream factory.
     */
    public function testGetStreamFactoryThrowsRuntimeException()
    {
        MockPsr17Factory::getStreamFactory();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Slim\Tests\Mocks\MockPsr17Factory could not instantiate a server request creator.
     */
    public function testGetServerRequestCreatorThrowsRuntimeException()
    {
        MockPsr17Factory::getServerRequestCreator();
    }
}
