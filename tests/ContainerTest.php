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
use Interop\Container\ContainerInterface;

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
     * @expectedException \Interop\Container\Exception\NotFoundException
     */
    public function testGetWithValueNotFoundError()
    {
        $c = new Container;
        $c->get('foo');
    }

    /**
     * Test `get()` throws something that is a ContainerExpception - typically a NotFoundException, when there is a DI
     * config error
     *
     * @expectedException \Interop\Container\Exception\ContainerException
     */
    public function testGetWithDiConfigErrorThrownAsContainerValueNotFoundException()
    {
        $container = new Container;
        $container['foo'] =
            function (ContainerInterface $container) {
                return $container->get('doesnt-exist');
            }
        ;
        $container->get('foo');
    }

    /**
     * Test `get()` recasts \InvalidArgumentException as ContainerInterop-compliant exceptions when an error is present
     * in the DI config
     *
     * @expectedException \Interop\Container\Exception\ContainerException
     */
    public function testGetWithDiConfigErrorThrownAsInvalidArgumentException()
    {
        $container = new Container;
        $container['foo'] =
            function (ContainerInterface $container) {
                return $container['doesnt-exist'];
            }
        ;
        $container->get('foo');
    }

    /**
     * Test `get()` does not recast exceptions which are thrown in a factory closure
     *
     * @expectedException \UnexpectedValueException
     */
    public function testGetWithErrorThrownByFactoryClosure()
    {
        $container = new Container;
        $container['foo'] =
            function (ContainerInterface $container) {
                throw new \UnexpectedValueException();
            }
        ;
        $container->get('foo');
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

    public function testGetMagicMethod()
    {
        $container = new Container();
        $container['dependency'] = function () {
            return new \stdClass;
        };
        $result = $container->dependency;
        $this->assertInstanceOf('\stdClass', $result);
    }

    public function testIssetMagicMethod()
    {
        $container = new Container();
        $container['dependency'] = function () {
            return new \stdClass;
        };
        $result = isset($container->dependency);
        $this->assertTrue($result);
    }
}
