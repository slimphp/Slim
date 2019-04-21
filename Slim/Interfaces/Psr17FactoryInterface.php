<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

use Psr\Http\Message\ResponseFactoryInterface;

interface Psr17FactoryInterface
{
    /**
     * @return ResponseFactoryInterface
     */
    public static function getResponseFactory(): ResponseFactoryInterface;

    /**
     * @return ServerRequestCreatorInterface
     */
    public static function getServerRequestCreator(): ServerRequestCreatorInterface;

    /**
     * Is the PSR-17 ResponseFactory available
     *
     * @return bool
     */
    public static function isResponseFactoryAvailable(): bool;

    /**
     * Is the ServerRequest creator available
     *
     * @return bool
     */
    public static function isServerRequestCreatorAvailable(): bool;
}
