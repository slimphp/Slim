<?php

declare(strict_types=1);

namespace Slim\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

if (method_exists(PHPUnitTestCase::class, 'assertMatchesRegularExpression')) {
    trait MigratePhpUnitDeprecations
    {

    }
} else {
    // @codingStandardsIgnoreStart
    trait MigratePhpUnitDeprecations
    {
        // @codingStandardsIgnoreEnd
        public static function assertMatchesRegularExpression(
            string $pattern,
            string $string,
            string $message = ''
        ): void {
            static::assertRegExp($pattern, $string, $message);
        }
    }
}
