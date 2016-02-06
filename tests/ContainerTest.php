<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use Slim\Container;

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
     * @expectedException \Slim\Exception\ContainerValueNotFoundException
     */
    public function testGetWithError()
    {
        $this->container->get('foo');
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
        $this->assertTrue($this->container->get('settings')['routerCacheDisabled']);
    }

    /**
     * Get cacheDisabled protected property from Route instance
     *
     * @return string
     */
    protected function getRouteCacheDisabled()
    {
        $router = $this->container['router'];
        $getCacheDisabled = function ($router) {
            return $router->cacheDisabled;
        };
        $getCacheDisabled = \Closure::bind($getCacheDisabled, null, $this->container['router']);
        return $getCacheDisabled($router);
    }

    /**
     * Get cacheFile protected property from Route instance
     *
     * @return string
     */
    protected function getRouteCacheFile()
    {
        $router = $this->container['router'];
        $getCacheFile = function ($router) {
            return $router->cacheFile;
        };
        $getCacheFile = \Closure::bind($getCacheFile, null, $this->container['router']);
        return $getCacheFile($router);
    }

    /**
     * Test that cache is not enabled and no cache file is set to Router
     * when option is disabled from config
     */
    public function testRouteCacheFileNotSetWhenCacheDisabled()
    {
        $this->container->get('settings')['routerCacheDisabled'] = true;
        $this->assertTrue($this->getRouteCacheDisabled());
        $this->assertNull($this->getRouteCacheFile());
    }

    /**
     * Test that cache is enabled and cache file is set to Router
     * when option is enabled from config
     */
    public function testRouteCacheSetWhenCacheEnabled()
    {
        $this->container->get('settings')['routerCacheDisabled'] = false;
        $this->container->get('settings')['routerCacheFile'] = 'slim';
        $this->assertFalse($this->getRouteCacheDisabled());
        $this->assertEquals('slim', $this->getRouteCacheFile());
    }
}
