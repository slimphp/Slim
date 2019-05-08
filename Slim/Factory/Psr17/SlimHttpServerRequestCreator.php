<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory\Psr17;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Interfaces\ServerRequestCreatorInterface;

class SlimHttpServerRequestCreator implements ServerRequestCreatorInterface
{
    /**
     * @var ServerRequestCreatorInterface
     */
    protected $serverRequestCreator;

    /**
     * @var string
     */
    protected static $serverRequestDecoratorClass = 'Slim\Http\ServerRequest';

    /**
     * @param ServerRequestCreatorInterface $serverRequestCreator
     */
    public function __construct(ServerRequestCreatorInterface $serverRequestCreator)
    {
        $this->serverRequestCreator = $serverRequestCreator;
    }

    /**
     * {@inheritdoc}
     */
    public function createServerRequestFromGlobals(): ServerRequestInterface
    {
        if (!static::isServerRequestDecoratorAvailable()) {
            throw new RuntimeException('The Slim-Http ServerRequest decorator is not available.');
        }

        $request = $this->serverRequestCreator->createServerRequestFromGlobals();

        return new static::$serverRequestDecoratorClass($request);
    }

    /**
     * @return bool
     */
    public static function isServerRequestDecoratorAvailable(): bool
    {
        return class_exists(static::$serverRequestDecoratorClass);
    }
}
