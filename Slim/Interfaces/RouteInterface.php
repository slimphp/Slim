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

use \Psr\Http\Message\ResponseInterface;

/**
 * Route Interface
 *
 * @package Slim
 * @author  John Porter
 * @since   3.0.0
 */
interface RouteInterface
{
    public static function setDefaultConditions(array $defaultConditions);

    public static function getDefaultConditions();

    public function getPattern();

    public function setPattern($pattern);

    public function getCallable();

    public function setCallable($callable);

    public function getConditions();

    public function setConditions(array $conditions);

    public function getName();

    public function setName($name);

    public function getParams();

    public function setParams(array $params);

    public function getParam($index);

    public function setParam($index, $value);

    public function setHttpMethods();

    public function getHttpMethods();

    public function appendHttpMethods();

    public function via();

    public function supportsHttpMethod($method);

    public function getMiddleware();

    public function setMiddleware($middleware);

    public function matches($resourceUri);

    public function name($name);

    public function conditions(array $conditions);

    public function dispatch(Http\RequestInterface $request, ResponseInterface $response);
}
