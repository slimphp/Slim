<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

use AdrianSuter\Autoload\Override\Override;
use Slim\ResponseEmitter;
use Slim\Routing\RouteCollector;
use Slim\Tests\Assets\HeaderStack;

$classLoader = require __DIR__ . '/../vendor/autoload.php';

Override::apply($classLoader, [
    ResponseEmitter::class => [
        'connection_status' => function (): int {
            if (isset($GLOBALS['connection_status_return'])) {
                return $GLOBALS['connection_status_return'];
            }

            return connection_status();
        },
        'header' => function (string $string, bool $replace = true, ?int $statusCode = null): void {
            HeaderStack::push(
                [
                    'header' => $string,
                    'replace' => $replace,
                    'status_code' => $statusCode,
                ]
            );
        },
        'headers_sent' => function (): bool {
            return false;
        }
    ],
    RouteCollector::class => [
        'is_readable' => function (string $file): bool {
            return stripos($file, 'non-readable.cache') === false;
        },
        'is_writable' => function (string $path): bool {
            return stripos($path, 'non-writable-directory') === false;
        }
    ]
]);
