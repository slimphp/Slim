<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Mocks;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Mock object for Slim\Tests\RouteTest
 */
class StaticCallable
{
    public static function run(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $response->getBody()->write('In1');
        $response = $next($request, $response);
        $response->getBody()->write('Out1');
        return $response;
    }
}
