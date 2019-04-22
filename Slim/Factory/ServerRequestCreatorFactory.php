<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory;

use RuntimeException;
use Slim\Factory\Psr17\GuzzlePsr17Factory;
use Slim\Factory\Psr17\NyholmPsr17Factory;
use Slim\Factory\Psr17\Psr17Factory;
use Slim\Factory\Psr17\SlimPsr17Factory;
use Slim\Factory\Psr17\ZendDiactorosPsr17Factory;
use Slim\Interfaces\ServerRequestCreatorInterface;

class ServerRequestCreatorFactory
{
    /**
     * @var array
     */
    protected static $psr17Factories = [
        SlimPsr17Factory::class,
        NyholmPsr17Factory::class,
        ZendDiactorosPsr17Factory::class,
        GuzzlePsr17Factory::class,
    ];

    /**
     * @return ServerRequestCreatorInterface
     */
    public static function create(): ServerRequestCreatorInterface
    {
        return static::determineServerRequestCreator();
    }

    /**
     * @return ServerRequestCreatorInterface
     * @throws RuntimeException
     */
    public static function determineServerRequestCreator(): ServerRequestCreatorInterface
    {
        /** @var Psr17Factory $psr17Factory */
        foreach (self::$psr17Factories as $psr17Factory) {
            if ($psr17Factory::isServerRequestCreatorAvailable()) {
                return $psr17Factory::getServerRequestCreator();
            }
        }

        throw new RuntimeException(
            "Could not detect any ServerRequest creator implementations. " .
            "Please install a supported implementation in order to use `App::run()` " .
            "without having to pass in a `ServerRequest` object. " .
            "See https://github.com/slimphp/Slim/blob/4.x/README.md for a list of supported implementations."
        );
    }
}
