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
    protected static $implementations = [
        SlimPsr17Factory::class,
        NyholmPsr17Factory::class,
        ZendDiactorosPsr17Factory::class,
        GuzzlePsr17Factory::class,
    ];

    /**
     * @return ServerRequestCreatorInterface
     * @throws RuntimeException
     */
    public static function determineServerRequestCreator(): ServerRequestCreatorInterface
    {
        /** @var Psr17Factory $implementation */
        foreach (self::$implementations as $implementation) {
            if ($implementation::isServerRequestCreatorAvailable()) {
                return $implementation::getServerRequestCreator();
            }
        }

        throw new RuntimeException('Could not detect any ServerRequest creator implementations.');
    }

    /**
     * @return ServerRequestCreatorInterface
     */
    public static function create(): ServerRequestCreatorInterface
    {
        return static::determineServerRequestCreator();
    }
}
