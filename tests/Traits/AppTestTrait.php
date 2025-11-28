<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Traits;

use PHPUnit\Framework\Constraint\IsIdentical;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Container\DiContainerFactory;
use Slim\Factory\AppFactory;

trait AppTestTrait
{
    protected function createApp(array $definitions = []): App
    {
        $containerFactory = new DiContainerFactory();

        return AppFactory::createFromContainer($containerFactory->createContainer($definitions));
    }

    protected function assertJsonResponse(mixed $expected, ResponseInterface $actual, string $message = ''): void
    {
        self::assertThat(
            json_decode((string)$actual->getBody(), true),
            new IsIdentical($expected),
            $message,
        );
    }
}
