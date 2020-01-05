<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory\Psr17;

class LaminasDiactorosPsr17Factory extends Psr17Factory
{
    protected static $responseFactoryClass = 'Laminas\Diactoros\ResponseFactory';
    protected static $streamFactoryClass = 'Laminas\Diactoros\StreamFactory';
    protected static $serverRequestCreatorClass = 'Laminas\Diactoros\ServerRequestFactory';
    protected static $serverRequestCreatorMethod = 'fromGlobals';
}
