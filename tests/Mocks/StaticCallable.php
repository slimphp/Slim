<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Mocks;

/**
* Mock object for Slim\Tests\RouteTest
*/
class StaticCallable
{
    public static function run($req, $res, $next)
    {
        $res->write('In1');
        $res = $next($req, $res);
        $res->write('Out1');

        return $res;
    }
}
