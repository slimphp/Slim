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
    public function testGetResponseFactoryThrowsRuntimeException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Slim\\Tests\\Mocks\\MockPsr17Factory could not instantiate a response factory.');

        MockPsr17Factory::getResponseFactory();
    }

    public function testGetStreamFactoryThrowsRuntimeException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Slim\\Tests\\Mocks\\MockPsr17Factory could not instantiate a stream factory.');

        MockPsr17Factory::getStreamFactory();
    }

    public function testGetServerRequestCreatorThrowsRuntimeException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Slim\\Tests\\Mocks\\MockPsr17Factory' .
                                      ' could not instantiate a server request creator.');

        MockPsr17Factory::getServerRequestCreator();
    }
}
