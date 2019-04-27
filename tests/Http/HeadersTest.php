<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Http;

use PHPUnit_Framework_TestCase;
use ReflectionProperty;
use Slim\Http\Environment;
use Slim\Http\Headers;

class HeadersTest extends PHPUnit_Framework_TestCase
{
    public function testCreateFromEnvironment()
    {
        $e = Environment::mock([
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $h = Headers::createFromEnvironment($e);
        $prop = new ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertInternalType('array', $prop->getValue($h)['accept']);
        $this->assertEquals('application/json', $prop->getValue($h)['accept']['value'][0]);
    }

    public function testCreateFromEnvironmentWithSpecialHeaders()
    {
        $e = Environment::mock([
            'CONTENT_TYPE' => 'application/json',
        ]);
        $h = Headers::createFromEnvironment($e);
        $prop = new ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertInternalType('array', $prop->getValue($h)['content-type']);
        $this->assertEquals('application/json', $prop->getValue($h)['content-type']['value'][0]);
    }

    public function testCreateFromEnvironmentIgnoresHeaders()
    {
        $e = Environment::mock([
            'CONTENT_TYPE' => 'text/csv',
            'HTTP_CONTENT_LENGTH' => 1230, // <-- Ignored
        ]);
        $h = Headers::createFromEnvironment($e);
        $prop = new ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertNotContains('content-length', $prop->getValue($h));
    }

    public function testConstructor()
    {
        $h = new Headers([
            'Content-Length' => 100,
        ]);
        $prop = new ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertInternalType('array', $prop->getValue($h)['content-length']);
        $this->assertEquals(100, $prop->getValue($h)['content-length']['value'][0]);
    }

    public function testSetSingleValue()
    {
        $h = new Headers();
        $h->set('Content-Length', 100);
        $prop = new ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertInternalType('array', $prop->getValue($h)['content-length']);
        $this->assertEquals(100, $prop->getValue($h)['content-length']['value'][0]);
    }

    public function testSetArrayValue()
    {
        $h = new Headers();
        $h->set('Allow', ['GET', 'POST']);
        $prop = new ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertInternalType('array', $prop->getValue($h)['allow']);
        $this->assertEquals(['GET', 'POST'], $prop->getValue($h)['allow']['value']);
    }

    public function testGet()
    {
        $h = new Headers();
        $prop = new ReflectionProperty($h, 'data');
        $prop->setAccessible(true);
        $prop->setValue($h, [
            'allow' => [
                'value' => ['GET', 'POST'],
                'originalKey' => 'Allow'
            ]
        ]);

        $this->assertEquals(['GET', 'POST'], $h->get('Allow'));
    }

    public function testGetOriginalKey()
    {
        $h = new Headers();
        $h->set('http-test_key', 'testValue');
        $h->get('test-key');

        $value = $h->get('test-key');

        $this->assertEquals('testValue', reset($value));
        $this->assertEquals('http-test_key', $h->getOriginalKey('test-key'));
        $this->assertNull($h->getOriginalKey('test-non-existing'));
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
        $prop = new ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertInternalType('array', $prop->getValue($h)['foo']);
        $this->assertEquals(['Bar'], $prop->getValue($h)['foo']['value']);
    }

    public function testAddAnotherValue()
    {
        $h = new Headers();
        $h->add('Foo', 'Bar');
        $h->add('Foo', 'Xyz');
        $prop = new ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertInternalType('array', $prop->getValue($h)['foo']);
        $this->assertEquals(['Bar', 'Xyz'], $prop->getValue($h)['foo']['value']);
    }

    public function testAddArrayValue()
    {
        $h = new Headers();
        $h->add('Foo', 'Bar');
        $h->add('Foo', ['Xyz', '123']);
        $prop = new ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertInternalType('array', $prop->getValue($h)['foo']);
        $this->assertEquals(['Bar', 'Xyz', '123'], $prop->getValue($h)['foo']['value']);
    }

    public function testHas()
    {
        $h = new Headers();
        $prop = new ReflectionProperty($h, 'data');
        $prop->setAccessible(true);
        $prop->setValue($h, [
            'allow' => [
                'value' => ['GET', 'POST'],
                'originalKey' => 'Allow'
            ]
        ]);
        $this->assertTrue($h->has('allow'));
        $this->assertFalse($h->has('foo'));
    }

    public function testRemove()
    {
        $h = new Headers();
        $prop = new ReflectionProperty($h, 'data');
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
        $prop = new ReflectionProperty($h, 'data');
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
        $this->assertEquals('foo-bar', $h->normalizeKey('HTTP_FOO_BAR'));
        $this->assertEquals('foo-bar', $h->normalizeKey('HTTP-FOO-BAR'));
        $this->assertEquals('foo-bar', $h->normalizeKey('Http-Foo-Bar'));
        $this->assertEquals('foo-bar', $h->normalizeKey('Http_Foo_Bar'));
        $this->assertEquals('foo-bar', $h->normalizeKey('http_foo_bar'));
        $this->assertEquals('foo-bar', $h->normalizeKey('http-foo-bar'));
    }

    public function testDetermineAuthorization()
    {
        $e = Environment::mock([]);
        $en = Headers::determineAuthorization($e);
        $h = Headers::createFromEnvironment($e);

        $this->assertEquals('electrolytes', $en->get('HTTP_AUTHORIZATION'));
        $this->assertEquals(['electrolytes'], $h->get('Authorization'));
    }

    public function testDetermineAuthorizationReturnsEarlyIfHeadersIsNotArray()
    {
        $e = Environment::mock([]);

        $GLOBALS['getallheaders_return'] = false;
        $en = Headers::determineAuthorization($e);
        $h = Headers::createFromEnvironment($e);
        unset($GLOBALS['getallheaders_return']);

        $this->assertNull($en->get('HTTP_AUTHORIZATION'));
        $this->assertNull($h->get('Authorization'));
    }
}
