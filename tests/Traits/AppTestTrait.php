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
use Psr\Http\Message\ServerRequestFactoryInterface;
use Slim\App;

trait AppTestTrait
{
    protected function assertJsonResponse(mixed $expected, ResponseInterface $actual, string $message = ''): void
    {
        self::assertThat(
            json_decode((string)$actual->getBody(), true),
            new IsIdentical($expected),
            $message,
        );
    }

    protected function getServerRequestFactory(App $app): ServerRequestFactoryInterface
    {
        return $app->getContainer()->get(ServerRequestFactoryInterface::class);
    }
}
