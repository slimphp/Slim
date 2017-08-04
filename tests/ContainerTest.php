<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use Slim\Container;
use Psr\Container\ContainerInterface;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    protected $container;

    public function setUp()
    {
        $this->container = new Container;
    }

    /**
     * Test `get()` returns existing item
     */
    public function testGet()
    {
        $this->assertInstanceOf('\Slim\Http\Environment', $this->container->get('environment'));
    }



    /**
     * Test `get()` throws error if item does not exist
     *
     * @expectedException \Interop\Container\Exception\NotFoundException
     */
    public function testGetWithValueNotFoundError()
    {
        $this->container->get('foo');
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
     * @expectedException \InvalidArgumentException
     */
    public function testGetWithErrorThrownByFactoryClosure()
    {
        $invokable = $this->getMockBuilder('StdClass')->setMethods(['__invoke'])->getMock();
        /** @var \Callable $invokable */
        $invokable->expects($this->any())
            ->method('__invoke')
            ->will($this->throwException(new \InvalidArgumentException()));

        $container = new Container;
        $container['foo'] =
            function (ContainerInterface $container) use ($invokable) {
                call_user_func($invokable);
            }
        ;
        $container->get('foo');
    }

    /**
     * Test container has request
     */
    public function testGetRequest()
    {
        $this->assertInstanceOf('\Psr\Http\Message\RequestInterface', $this->container['request']);
    }

    /**
     * Test container has response
     */
    public function testGetResponse()
    {
        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $this->container['response']);
    }

    /**
     * Test container has router
     */
    public function testGetRouter()
    {
        $this->assertInstanceOf('\Slim\Router', $this->container['router']);
    }

    /**
     * Test container has error handler
     */
    public function testGetErrorHandler()
    {
        $this->assertInstanceOf('\Slim\Handlers\Error', $this->container['errorHandler']);
    }

    /**
     * Test container has error handler
     */
    public function testGetNotAllowedHandler()
    {
        $this->assertInstanceOf('\Slim\Handlers\NotAllowed', $this->container['notAllowedHandler']);
    }

    /**
     * Test settings can be edited
     */
    public function testSettingsCanBeEdited()
    {
        $this->assertSame('1.1', $this->container->get('settings')['httpVersion']);

        $this->container->get('settings')['httpVersion'] = '1.2';
        $this->assertSame('1.2', $this->container->get('settings')['httpVersion']);
    }

    //Test __isset
    public function testMagicIssetMethod()
    {
        $this->assertEquals(true, $this->container->__isset('settings'));
    }

    //test __get
    public function testMagicGetMethod()
    {
        $this->container->get('settings')['httpVersion'] = '1.2';
        $this->assertSame('1.2', $this->container->__get('settings')['httpVersion']);
    }

    public function testRouteCacheDisabledByDefault()
    {
        $this->assertFalse($this->container->get('settings')['routerCacheFile']);
    }
}
