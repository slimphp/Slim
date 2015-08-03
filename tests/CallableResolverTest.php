<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use Slim\CallableResolver;
use Slim\Container;
use Slim\Tests\Mocks\CallableTest;

class CallableResolverTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Container
     */
    private $container;


    public function setUp()
    {
        CallableTest::$CalledCount = 0;
        $this->container = new Container();
    }

    public function testClosure()
    {
        $test_callable = function () {
            static $called_count = 0;
            return $called_count++;
        };
        $resolver = new CallableResolver($this->container, $test_callable);
        $resolver();
        $this->assertEquals(1, $test_callable());
    }

    public function testFunctionName()
    {
        // @codingStandardsIgnoreStart
        function testCallable()
        {
            static $called_count = 0;
            return $called_count++;
        };
        // @codingStandardsIgnoreEnd

        $resolver = new CallableResolver($this->container, __NAMESPACE__ . '\testCallable');
        $resolver();
        $this->assertEquals(1, testCallable());
    }

    public function testObjMethodArray()
    {
        $obj = new CallableTest();
        $resolver = new CallableResolver($this->container, [$obj, 'toCall']);
        $resolver();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testSlimCallable()
    {
        $resolver = new CallableResolver($this->container, 'Slim\Tests\Mocks\CallableTest:toCall');
        $resolver();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testContainer()
    {
        $this->container['callable_service'] = new CallableTest();
        $resolver = new CallableResolver($this->container, 'callable_service:toCall');
        $resolver();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testMethodNotFoundThrowException()
    {
        $this->container['callable_service'] = new CallableTest();
        $resolver = new CallableResolver($this->container, 'callable_service:noFound');
        $this->setExpectedException('\RuntimeException');
        $resolver();
    }

    public function testFunctionNotFoundThrowException()
    {
        $resolver = new CallableResolver($this->container, 'noFound');
        $this->setExpectedException('\RuntimeException');
        $resolver();
    }

    public function testClassNotFoundThrowException()
    {
        $resolver = new CallableResolver($this->container, 'Unknown:notFound');
        $this->setExpectedException('\RuntimeException', 'Callable Unknown does not exist');
        $resolver();
    }
}
