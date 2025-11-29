<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Container\Definition;

use GuzzleHttp\Psr7\ServerRequest;
use HttpSoft\Message\RequestFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use RuntimeException;
use Slim\Http\Factory\DecoratedServerRequestFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Selects the appropriate PSR-17 implementations based on the available libraries.
 */
final class HttpDefinitions
{
    /**
     * @var callable
     */
    private $classExists = 'class_exists';

    private array $classes = [
        DecoratedServerRequestFactory::class => SlimHttpDefinitions::class,
        ServerRequestFactory::class => SlimPsr7Definitions::class,
        Psr17Factory::class => NyholmDefinitions::class,
        ServerRequest::class => GuzzleDefinitions::class,
        RequestFactory::class => HttpSoftDefinitions::class,
    ];

    public function getDefinitions(): array
    {
        foreach ($this->classes as $factory => $definitionClass) {
            if (call_user_func($this->classExists, $factory)) {
                return (new $definitionClass())->getDefinitions();
            }
        }

        throw new RuntimeException(
            'Could not detect any PSR-17 ResponseFactory implementations. '
            . 'Please install a supported implementation. '
            . 'See https://github.com/slimphp/Slim/blob/5.x/README.md for a list of supported implementations.',
        );
    }
}
