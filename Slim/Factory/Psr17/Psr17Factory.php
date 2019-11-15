<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory\Psr17;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
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
    protected static $streamFactoryClass;

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
        if (!static::isResponseFactoryAvailable()) {
            throw new RuntimeException(\get_called_class() . ' could not instantiate a response factory.');
        }

        return new static::$responseFactoryClass;
    }

    /**
     * {@inheritdoc}
     */
    public static function getStreamFactory(): StreamFactoryInterface
    {
        if (!static::isStreamFactoryAvailable()) {
            throw new RuntimeException(\get_called_class() . ' could not instantiate a stream factory.');
        }

        return new static::$streamFactoryClass;
    }

    /**
     * {@inheritdoc}
     */
    public static function getServerRequestCreator(): ServerRequestCreatorInterface
    {
        if (!static::isServerRequestCreatorAvailable()) {
            throw new RuntimeException(\get_called_class() . ' could not instantiate a server request creator.');
        }

        return new ServerRequestCreator(static::$serverRequestCreatorClass, static::$serverRequestCreatorMethod);
    }

    /**
     * {@inheritdoc}
     */
    public static function isResponseFactoryAvailable(): bool
    {
        return static::$responseFactoryClass && \class_exists(static::$responseFactoryClass);
    }

    /**
     * {@inheritdoc}
     */
    public static function isStreamFactoryAvailable(): bool
    {
        return static::$streamFactoryClass && \class_exists(static::$streamFactoryClass);
    }

    /**
     * {@inheritdoc}
     */
    public static function isServerRequestCreatorAvailable(): bool
    {
        return (
            static::$serverRequestCreatorClass
            && static::$serverRequestCreatorMethod
            && \class_exists(static::$serverRequestCreatorClass)
        );
    }
}
