<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Mocks;

/**
 * Mock object for Slim\Tests\RouteTest
 */
class MiddlewareStub
{
    public function run($request, $response, $next)
    {
        return $response; //$next($request, $response);
    }
}
