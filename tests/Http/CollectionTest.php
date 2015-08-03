<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Http;

use ReflectionProperty;
use Slim\Http\Collection;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Collection
     */
    protected $bag;

    /**
     * @var ReflectionProperty
     */
    protected $property;

    public function setUp()
    {
        $this->bag = new Collection();
        $this->property = new ReflectionProperty($this->bag, 'data');
        $this->property->setAccessible(true);
    }

    public function testInitializeWithData()
    {
        $bag = new Collection(['foo' => 'bar']);
        $bagProperty = new ReflectionProperty($bag, 'data');
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

    public function testOffsetSet()
    {
        $this->bag['foo'] = 'bar';
        $this->assertArrayHasKey('foo', $this->property->getValue($this->bag));
        $bag = $this->property->getValue($this->bag);
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

    public function testOffsetExists()
    {
        $this->property->setValue($this->bag, ['foo' => 'bar']);
        $this->assertTrue(isset($this->bag['foo']));
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

    public function testOffsetUnset()
    {
        $data = [
            'abc' => '123',
            'foo' => 'bar',
        ];
        $this->property->setValue($this->bag, $data);

        unset($this->bag['foo']);
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
