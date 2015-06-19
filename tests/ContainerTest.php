<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.5
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

namespace Slim\Tests;

use Slim\Route;
use Slim\Http\Collection;
use Slim\Container;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test `get()` returns existing item
     */
    public function testGet()
    {
        $c = new Container;
        $this->assertInstanceOf('\Slim\Http\Environment', $c->get('environment'));
    }

    /**
     * Test `get()` throws error if item does not exist
     *
     * @expectedException \Slim\Exception\NotFoundException
     */
    public function testGetWithError()
    {
        $c = new Container;
        $c->get('foo');
    }

    /**
     * Test container has request
     */
    public function testGetRequest()
    {
        $c = new Container;
        $this->assertInstanceOf('\Psr\Http\Message\RequestInterface', $c['request']);
    }

    /**
     * Test container has response
     */
    public function testGetResponse()
    {
        $c = new Container;
        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $c['response']);
    }

    /**
     * Test container has router
     */
    public function testGetRouter()
    {
        $c = new Container;
        $this->assertInstanceOf('\Slim\Router', $c['router']);
    }

    /**
     * Test container has error handler
     */
    public function testGetErrorHandler()
    {
        $c = new Container;
        $this->assertInstanceOf('\Slim\Handlers\Error', $c['errorHandler']);
    }

    /**
     * Test container has error handler
     */
    public function testGetNotAllowedHandler()
    {
        $c = new Container;
        $this->assertInstanceOf('\Slim\Handlers\NotAllowed', $c['notAllowedHandler']);
    }
}
