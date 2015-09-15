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
use Slim\ServiceConfig;

class ServiceConfigTest extends \PHPUnit_Framework_TestCase
{
    public function serviceConfigFactory()
    {
        $c = new Container();
        $s = new ServiceConfig($c, $c['settings']);

        return $s;
    }

    /**
     * Test `get()` returns existing item
     */
    public function testGet()
    {
        $service = $this->serviceConfigFactory();
        $this->assertInstanceOf('\Slim\Http\Environment', $service->getEnvironment());
    }

    /**
     * Test container has request
     */
    public function testGetRequest()
    {
        $service = $this->serviceConfigFactory();
        $this->assertInstanceOf('\Psr\Http\Message\RequestInterface', $service->newRequest());
    }

    /**
     * Test container has response
     */
    public function testGetResponse()
    {
        $service = $this->serviceConfigFactory();
        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $service->newResponse());
    }

    /**
     * Test container has router
     */
    public function testGetRouter()
    {
        $service = $this->serviceConfigFactory();
        $this->assertInstanceOf('\Slim\Router', $service->getRouter());
    }

    /**
     * Test container has error handler
     */
    public function testGetErrorHandler()
    {
        $service = $this->serviceConfigFactory();
        $this->assertInstanceOf('\Slim\Handlers\Error', $service->newErrorHandler());
    }

    /**
     * Test container has error handler
     */
    public function testGetNotAllowedHandler()
    {
        $service = $this->serviceConfigFactory();
        $this->assertInstanceOf('\Slim\Handlers\NotAllowed', $service->newNotAllowedHandler());
    }
}
