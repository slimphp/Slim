<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory\Psr17;

class HttpSoftPsr17Factory extends Psr17Factory
{
    protected static $responseFactoryClass = 'HttpSoft\Message\ResponseFactory';
    protected static $streamFactoryClass = 'HttpSoft\Message\StreamFactory';
    protected static $serverRequestCreatorClass = 'HttpSoft\ServerRequest\ServerRequestCreator';
    protected static $serverRequestCreatorMethod = 'createFromGlobals';
}
