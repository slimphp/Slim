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
use Slim\Handlers\Error;
use Slim\Handlers\NotAllowed;
use Slim\Interfaces\Http\EnvironmentInterface;
use Slim\Interfaces\Http\ResponseInterface;
use Slim\Interfaces\Http\ServerRequestInterface;
use Slim\Interfaces\RouterInterface;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test `get()` returns existing item
     */
    public function testGet()
    {
        $c = new Container;
        $this->assertInstanceOf(EnvironmentInterface::class, $c->get('environment'));
    }

    /**
     * Test `get()` throws error if item does not exist
     *
     * @expectedException \Slim\Exception\ContainerValueNotFoundException
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
        $this->assertInstanceOf(ServerRequestInterface::class, $c['request']);
    }

    /**
     * Test container has response
     */
    public function testGetResponse()
    {
        $c = new Container;
        $this->assertInstanceOf(ResponseInterface::class, $c['response']);
    }

    /**
     * Test container has router
     */
    public function testGetRouter()
    {
        $c = new Container;
        $this->assertInstanceOf(RouterInterface::class, $c['router']);
    }

    /**
     * Test container has error handler
     */
    public function testGetErrorHandler()
    {
        $c = new Container;
        $this->assertInstanceOf(Error::class, $c['errorHandler']);
    }

    /**
     * Test container has error handler
     */
    public function testGetNotAllowedHandler()
    {
        $c = new Container;
        $this->assertInstanceOf(NotAllowed::class, $c['notAllowedHandler']);
    }

    /**
     * Test settings can be edited
     */
    public function testSettingsCanBeEdited()
    {
        $c = new Container;
        $this->assertSame('1.1', $c->get('settings')['httpVersion']);

        $c->get('settings')['httpVersion'] = '1.2';
        $this->assertSame('1.2', $c->get('settings')['httpVersion']);
    }
}
