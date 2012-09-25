<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.0.0
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

class containerTestObject
{
}

class ContainerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->container = new \Slim\Container();
    }

    /**
     * Test container with a parameter
     */
    public function testContainerParameters()
    {
        $this->container['foo'] = 'bar';
        $this->container['bar'] = 'foo';
        $this->assertEquals($this->container['foo'], 'bar');
        $this->assertEquals($this->container['bar'], 'foo');
    }

    /**
     * Test container as a factory
     * each returned object is a new object
     */
    public function testContainerFactory()
    {
        $this->container['foo'] = function($c) {
            return new containerTestObject();
        };
        $c = $this->container['foo'];
        $this->assertInstanceOf('containerTestObject', $c);
        $this->assertNotSame($this->container['foo'], $this->container['foo']);
    }

    /**
     * Test shared object
     * the container returns always the same object
     */
    public function testContainerShare()
    {
        $this->container['foo'] = $this->container->share(function($c) {
            return new containerTestObject();
        });
        $c = $this->container['foo'];
        $this->assertSame($c, $this->container['foo']);
        $this->assertSame($this->container['foo'], $this->container['foo']);
    }
}
