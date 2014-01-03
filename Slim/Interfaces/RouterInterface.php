<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.0
 * @package     Slim
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace Slim\Interfaces;

use \Slim\Interfaces\RouteInterface;

/**
 * Router Interface
 *
 * @package Slim
 * @author  John Porter
 * @since   3.0.0
 */
interface RouterInterface
{
    public function getCurrentRoute();

    public function getMatchedRoutes($httpMethod, $resourceUri, $reload = false);

    public function map(RouteInterface $route);

    public function pushGroup($group, $middleware = array());

    public function popGroup();

    public function urlFor($name, $params = array());

    public function addNamedRoute($name, RouteInterface $route);

    public function hasNamedRoute($name);

    public function getNamedRoute($name);

    public function getNamedRoutes();
}
