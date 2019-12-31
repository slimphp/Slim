<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Mocks;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MockSequenceMiddleware implements MiddlewareInterface
{
    /**
     * @var string
     */
    public static $id = '0';

    /**
     * @var bool
     */
    public static $hasBeenInstantiated = false;

    public function __construct()
    {
        static::$hasBeenInstantiated = true;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAddedHeader('X-SEQ-PRE-REQ-HANDLER', static::$id);
        $response = $handler->handle($request);

        return $response->withAddedHeader('X-SEQ-POST-REQ-HANDLER', static::$id);
    }
}
