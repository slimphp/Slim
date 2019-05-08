<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

interface Psr17FactoryInterface
{
    /**
     * @return ResponseFactoryInterface
     *
     * @throws RuntimeException when the factory could not be instantiated
     */
    public static function getResponseFactory(): ResponseFactoryInterface;

    /**
     * @return StreamFactoryInterface
     *
     * @throws RuntimeException when the factory could not be instantiated
     */
    public static function getStreamFactory(): StreamFactoryInterface;

    /**
     * @return ServerRequestCreatorInterface
     *
     * @throws RuntimeException when the factory could not be instantiated
     */
    public static function getServerRequestCreator(): ServerRequestCreatorInterface;

    /**
     * Is the PSR-17 ResponseFactory available
     *
     * @return bool
     */
    public static function isResponseFactoryAvailable(): bool;

    /**
     * Is the PSR-17 StreamFactory available
     *
     * @return bool
     */
    public static function isStreamFactoryAvailable(): bool;

    /**
     * Is the ServerRequest creator available
     *
     * @return bool
     */
    public static function isServerRequestCreatorAvailable(): bool;
}
