<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory\Psr17;

class ZendDiactorosPsr17Factory extends Psr17Factory
{
    protected static $responseFactoryClass = 'Zend\Diactoros\ResponseFactory';
    protected static $streamFactoryClass = 'Zend\Diactoros\StreamFactory';
    protected static $serverRequestCreatorClass = 'Zend\Diactoros\ServerRequestFactory';
    protected static $serverRequestCreatorMethod = 'fromGlobals';
}
