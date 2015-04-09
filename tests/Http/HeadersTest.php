<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Tests;

use Slim\Http\Environment;
use Slim\Http\Headers;

class HeadersTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateFromEnvironment()
    {
        $e = Environment::mock([
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $h = Headers::createFromEnvironment($e);
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertTrue(is_array($prop->getValue($h)['Accept']));
        $this->assertEquals('application/json', $prop->getValue($h)['Accept']['value'][0]);
    }

    public function testCreateFromEnvironmentWithSpecialHeaders()
    {
        $e = Environment::mock([
            'CONTENT_TYPE' => 'application/json',
        ]);
        $h = Headers::createFromEnvironment($e);
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertTrue(is_array($prop->getValue($h)['Content-Type']));
        $this->assertEquals('application/json', $prop->getValue($h)['Content-Type']['value'][0]);
    }

    public function testCreateFromEnvironmentIgnoresHeaders()
    {
        $e = Environment::mock([
            'CONTENT_TYPE' => 'text/csv',
            'HTTP_CONTENT_LENGTH' => 1230, // <-- Ignored
            'HTTP_CONTENT_TYPE' => 'application/json', // <-- Ignored
        ]);
        $h = Headers::createFromEnvironment($e);
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertEquals('text/csv', $prop->getValue($h)['Content-Type']['value'][0]);
        $this->assertNotContains('Content-Length', $prop->getValue($h));
    }

    public function testConstructor()
    {
        $h = new Headers([
            'Content-Length' => 100,
        ]);
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertTrue(is_array($prop->getValue($h)['Content-Length']));
        $this->assertEquals(100, $prop->getValue($h)['Content-Length']['value'][0]);
    }

    public function testSetSingleValue()
    {
        $h = new Headers();
        $h->set('Content-Length', 100);
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertTrue(is_array($prop->getValue($h)['Content-Length']));
        $this->assertEquals(100, $prop->getValue($h)['Content-Length']['value'][0]);
    }

    public function testSetArrayValue()
    {
        $h = new Headers();
        $h->set('Allow', ['GET', 'POST']);
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertTrue(is_array($prop->getValue($h)['Allow']));
        $this->assertEquals(['GET', 'POST'], $prop->getValue($h)['Allow']['value']);
    }

    public function testGet()
    {
        $h = new Headers();
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);
        $prop->setValue($h, [
            'Allow' => [
                'value' => ['GET', 'POST'],
                'originalKey' => 'Allow'
            ]
        ]);

        $this->assertEquals(['GET', 'POST'], $h->get('Allow'));
    }

    public function testGetNotExists()
    {
        $h = new Headers();

        $this->assertNull($h->get('Foo'));
    }

    public function testAddNewValue()
    {
        $h = new Headers();
        $h->add('Foo', 'Bar');
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertTrue(is_array($prop->getValue($h)['Foo']));
        $this->assertEquals(['Bar'], $prop->getValue($h)['Foo']['value']);
    }

    public function testAddAnotherValue()
    {
        $h = new Headers();
        $h->add('Foo', 'Bar');
        $h->add('Foo', 'Xyz');
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertTrue(is_array($prop->getValue($h)['Foo']));
        $this->assertEquals(['Bar', 'Xyz'], $prop->getValue($h)['Foo']['value']);
    }

    public function testAddArrayValue()
    {
        $h = new Headers();
        $h->add('Foo', 'Bar');
        $h->add('Foo', ['Xyz', '123']);
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertTrue(is_array($prop->getValue($h)['Foo']));
        $this->assertEquals(['Bar', 'Xyz', '123'], $prop->getValue($h)['Foo']['value']);
    }

    public function testHas()
    {
        $h = new Headers();
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);
        $prop->setValue($h, [
            'Allow' => [
                'value' => ['GET', 'POST'],
                'originalKey' => 'Allow'
            ]
        ]);
        $this->assertTrue($h->has('Allow'));
        $this->assertFalse($h->has('Foo'));
    }

    public function testRemove()
    {
        $h = new Headers();
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);
        $prop->setValue($h, [
            'Allow' => [
                'value' => ['GET', 'POST'],
                'originalKey' => 'Allow'
            ]
        ]);
        $h->remove('Allow');

        $this->assertNotContains('Allow', $prop->getValue($h));
    }

    public function testOriginalKeys()
    {
        $h = new Headers();
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);
        $prop->setValue($h, [
            'Allow' => [
                'value' => ['GET', 'POST'],
                'originalKey' => 'ALLOW'
            ]
        ]);
        $all = $h->all();

        $this->assertArrayHasKey('ALLOW', $all);
    }

    public function testNormalizeKey()
    {
        $h = new Headers();
        $this->assertEquals('Foo-Bar', $h->normalizeKey('HTTP_FOO_BAR'));
        $this->assertEquals('Foo-Bar', $h->normalizeKey('HTTP-FOO-BAR'));
        $this->assertEquals('Foo-Bar', $h->normalizeKey('Http-Foo-Bar'));
        $this->assertEquals('Foo-Bar', $h->normalizeKey('Http_Foo_Bar'));
        $this->assertEquals('Foo-Bar', $h->normalizeKey('http_foo_bar'));
        $this->assertEquals('Foo-Bar', $h->normalizeKey('http-foo-bar'));
    }
}
