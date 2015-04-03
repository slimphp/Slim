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

class CollectionTest extends PHPUnit_Framework_TestCase
{
    protected $bag;
    protected $property;

    public function setUp()
    {
        $this->bag = new \Slim\Http\Collection();
        $this->property = new \ReflectionProperty($this->bag, 'data');
        $this->property->setAccessible(true);
    }

    public function testInitializeWithData()
    {
        $bag = new \Slim\Http\Collection(['foo' => 'bar']);
        $bagProperty = new \ReflectionProperty($bag, 'data');
        $bagProperty->setAccessible(true);

        $this->assertEquals(['foo' => 'bar'], $bagProperty->getValue($bag));
    }

    public function testSet()
    {
        $this->bag->set('foo', 'bar');
        $this->assertArrayHasKey('foo', $this->property->getValue($this->bag));
        $bag =  $this->property->getValue($this->bag);
        $this->assertEquals('bar', $bag['foo']);
    }

    public function testGet()
    {
        $this->property->setValue($this->bag, ['foo' => 'bar']);
        $this->assertEquals('bar', $this->bag->get('foo'));
    }

    public function testGetWithDefault()
    {
        $this->property->setValue($this->bag, ['foo' => 'bar']);
        $this->assertEquals('default', $this->bag->get('abc', 'default'));
    }

    public function testReplace()
    {
        $this->bag->replace([
            'abc' => '123',
            'foo' => 'bar',
        ]);
        $this->assertArrayHasKey('abc', $this->property->getValue($this->bag));
        $this->assertArrayHasKey('foo', $this->property->getValue($this->bag));
        $bag = $this->property->getValue($this->bag);
        $this->assertEquals('123', $bag['abc']);
        $this->assertEquals('bar', $bag['foo']);
    }

    public function testAll()
    {
        $data = [
            'abc' => '123',
            'foo' => 'bar',
        ];
        $this->property->setValue($this->bag, $data);
        $this->assertEquals($data, $this->bag->all());
    }

    public function testKeys()
    {
        $data = [
            'abc' => '123',
            'foo' => 'bar',
        ];
        $this->property->setValue($this->bag, $data);
        $this->assertEquals(['abc', 'foo'], $this->bag->keys());
    }

    public function testHas()
    {
        $this->property->setValue($this->bag, ['foo' => 'bar']);
        $this->assertTrue($this->bag->has('foo'));
        $this->assertFalse($this->bag->has('abc'));
    }

    public function testRemove()
    {
        $data = [
            'abc' => '123',
            'foo' => 'bar',
        ];
        $this->property->setValue($this->bag, $data);
        $this->bag->remove('foo');
        $this->assertEquals(['abc' => '123'], $this->property->getValue($this->bag));
    }

    public function testClear()
    {
        $data = [
            'abc' => '123',
            'foo' => 'bar',
        ];
        $this->property->setValue($this->bag, $data);
        $this->bag->clear();
        $this->assertEquals([], $this->property->getValue($this->bag));
    }

    public function testCount()
    {
        $this->property->setValue($this->bag, ['foo' => 'bar', 'abc' => '123']);
        $this->assertEquals(2, $this->bag->count());
    }
}
