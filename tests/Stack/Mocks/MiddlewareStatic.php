<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Stack\Mocks;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class MiddlewareStatic
{
    public static function run(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $response->write('InStatic');
        $next($request, $response);
        $response->write('OutStatic');

        return $response;
    }
}
