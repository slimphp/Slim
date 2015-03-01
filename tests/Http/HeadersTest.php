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

use \Slim\Http\Environment;
use \Slim\Http\Headers;

class HeadersTest extends PHPUnit_Framework_TestCase
{
    public function testCreateFromEnvironment()
    {
        $e = Environment::mock([
            'HTTP_ACCEPT' => 'application/json'
        ]);
        $h = Headers::createFromEnvironment($e);
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertTrue(is_array($prop->getValue($h)['Accept']));
        $this->assertEquals('application/json', $prop->getValue($h)['Accept'][0]);
    }

    public function testCreateFromEnvironmentWithSpecialHeaders()
    {
        $e = Environment::mock([
            'CONTENT_TYPE' => 'application/json'
        ]);
        $h = Headers::createFromEnvironment($e);
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertTrue(is_array($prop->getValue($h)['Content-Type']));
        $this->assertEquals('application/json', $prop->getValue($h)['Content-Type'][0]);
    }

    public function testCreateFromEnvironmentIgnoresHeaders()
    {
        $e = Environment::mock([
            'CONTENT_TYPE' => 'text/csv',
            'HTTP_CONTENT_LENGTH' => 1230, // <-- Ignored
            'HTTP_CONTENT_TYPE' => 'application/json' // <-- Ignored
        ]);
        $h = Headers::createFromEnvironment($e);
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertEquals('text/csv', $prop->getValue($h)['Content-Type'][0]);
        $this->assertNotContains('Content-Length', $prop->getValue($h));
    }

    public function testConstructor()
    {
        $h = new Headers([
            'Content-Length' => 100
        ]);
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertTrue(is_array($prop->getValue($h)['Content-Length']));
        $this->assertEquals(100, $prop->getValue($h)['Content-Length'][0]);
    }

    public function testSetSingleValue()
    {
        $h = new Headers();
        $h->set('Content-Length', 100);
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertTrue(is_array($prop->getValue($h)['Content-Length']));
        $this->assertEquals(100, $prop->getValue($h)['Content-Length'][0]);
    }

    public function testSetArrayValue()
    {
        $h = new Headers();
        $h->set('Allow', ['GET', 'POST']);
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertTrue(is_array($prop->getValue($h)['Allow']));
        $this->assertEquals(['GET', 'POST'], $prop->getValue($h)['Allow']);
    }

    public function testGet()
    {
        $h = new Headers();
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);
        $prop->setValue($h, [
            'Allow' => ['GET', 'POST']
        ]);

        $this->assertEquals(['GET', 'POST'], $h->get('Allow'));
    }

    public function testGetNotExists()
    {
        $h = new Headers();

        $this->assertEquals([], $h->get('Foo'));
    }

    public function testAddNewValue()
    {
        $h = new Headers();
        $h->add('Foo', 'Bar');
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertTrue(is_array($prop->getValue($h)['Foo']));
        $this->assertEquals(['Bar'], $prop->getValue($h)['Foo']);
    }

    public function testAddAnotherValue()
    {
        $h = new Headers();
        $h->add('Foo', 'Bar');
        $h->add('Foo', 'Xyz');
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertTrue(is_array($prop->getValue($h)['Foo']));
        $this->assertEquals(['Bar', 'Xyz'], $prop->getValue($h)['Foo']);
    }

    public function testAddArrayValue()
    {
        $h = new Headers();
        $h->add('Foo', 'Bar');
        $h->add('Foo', ['Xyz', '123']);
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);

        $this->assertTrue(is_array($prop->getValue($h)['Foo']));
        $this->assertEquals(['Bar', 'Xyz', '123'], $prop->getValue($h)['Foo']);
    }

    public function testHas()
    {
        $h = new Headers();
        $prop = new \ReflectionProperty($h, 'data');
        $prop->setAccessible(true);
        $prop->setValue($h, [
            'Allow' => ['GET', 'POST']
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
            'Allow' => ['GET', 'POST']
        ]);
        $h->remove('Allow');

        $this->assertNotContains('Allow', $prop->getValue($h));
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
