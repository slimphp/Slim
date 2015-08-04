<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

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
