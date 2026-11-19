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

// adriansuter/php-autoload-override calls spl_object_hash() for each closure override, which is deprecated
// as of PHP 8.6. This bootstrap runs before PHPUnit registers its error handler, so the deprecation message
// would be printed and break the tests that run in a separate process. Silence only that deprecation while
// the overrides are being registered.
set_error_handler(static function (int $errno, string $errstr): bool {
    return strpos($errstr, 'spl_object_hash()') !== false;
}, E_DEPRECATED);

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

restore_error_handler();
