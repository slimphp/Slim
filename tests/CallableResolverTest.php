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

use Slim\CallableResolver;
use Pimple\Container;

class CallableTest
{
    public static $CalledCount = 0;
    public function toCall()
    {
        return static::$CalledCount++;
    }
}

class CallableResolverTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        CallableTest::$CalledCount = 0;
        $this->container = new Container();
    }

    public function testClosure()
    {
        $test_callable = function() {
            static $called_count = 0;
            return $called_count++;
        };
        $resolver = new CallableResolver($test_callable, $this->container);
        $resolver();
        $this->assertEquals(1, $test_callable());
    }

    public function testFunctionName()
    {
        function test_callable()
        {
            static $called_count = 0;
            return $called_count++;
        };
        $resolver = new CallableResolver('test_callable', $this->container);
        $resolver();
        $this->assertEquals(1, test_callable());
    }

    public function testObjMethodArray()
    {
        $obj = new CallableTest();
        $resolver = new CallableResolver([$obj, 'toCall'], $this->container);
        $resolver();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testSlimCallable()
    {
        $resolver = new CallableResolver('CallableTest:toCall', $this->container);
        $resolver();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testContainer()
    {
        $this->container['callable_service'] = new CallableTest();
        $resolver = new CallableResolver('callable_service:toCall', $this->container);
        $resolver();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testMethodNotFoundThrowException()
    {
        $this->container['callable_service'] = new CallableTest();
        $resolver = new CallableResolver('callable_service:noFound', $this->container);
        $this->setExpectedException('\RuntimeException');
        $resolver();
    }

    public function testFunctionNotFoundThrowException()
    {
        $resolver = new CallableResolver('noFound', $this->container);
        $this->setExpectedException('\RuntimeException');
        $resolver();
    }
}
