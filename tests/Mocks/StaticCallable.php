<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Mocks;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Response;

class StaticCallable
{
    public static function run(ServerRequestInterface $request, Response $response, $next)
    {
        $response->write('In1');

        /** @var Response $response */
        $response = $next($request, $response);
        $response->write('Out1');

        return $response;
    }
}
