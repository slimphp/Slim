<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory\Psr17;

use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Interfaces\Psr17FactoryInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;

abstract class Psr17Factory implements Psr17FactoryInterface
{
    /**
     * @var string
     */
    protected static $responseFactoryClass;

    /**
     * @var string
     */
    protected static $serverRequestCreatorClass;

    /**
     * @var string
     */
    protected static $serverRequestCreatorMethod;

    /**
     * {@inheritdoc}
     */
    public static function getResponseFactory(): ResponseFactoryInterface
    {
        return new static::$responseFactoryClass;
    }

    /**
     * {@inheritdoc}
     */
    public static function getServerRequestCreator(): ServerRequestCreatorInterface
    {
        return new ServerRequestCreator(static::$serverRequestCreatorClass, static::$serverRequestCreatorMethod);
    }

    /**
     * {@inheritdoc}
     */
    public static function isResponseFactoryAvailable(): bool
    {
        return class_exists(static::$responseFactoryClass);
    }

    /**
     * {@inheritdoc}
     */
    public static function isServerRequestCreatorAvailable(): bool
    {
        return class_exists(static::$serverRequestCreatorClass);
    }
}
