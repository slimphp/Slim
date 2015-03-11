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
use \Slim\Http\Cookies;

class CookiesTest extends PHPUnit_Framework_TestCase
{
    public function testConstructorSetsDefaults()
    {
        $c = new Cookies([], [
            'path' => '/foo',
        ]);
        $prop = new \ReflectionProperty($c, 'defaults');
        $prop->setAccessible(true);

        $this->assertEquals('/foo', $prop->getValue($c)['path']);
    }

    public function testConstructorSetsValues()
    {
        $c = new Cookies(['foo' => 'bar']);
        $prop = new \ReflectionProperty($c, 'data');
        $prop->setAccessible(true);

        $this->assertEquals('bar', $prop->getValue($c)['foo']['value']);
    }

    public function testGetDefaults()
    {
        $c = new Cookies();
        $prop = new \ReflectionProperty($c, 'defaults');
        $prop->setAccessible(true);
        $prop->setValue($c, ['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $c->getDefaults());
    }

    public function testSetWithSingleValue()
    {
        $c = new Cookies();
        $c->set('foo', 'bar');
        $prop = new \ReflectionProperty($c, 'data');
        $prop->setAccessible(true);

        $this->assertEquals('bar', $prop->getValue($c)['foo']['value']);
    }

    public function testSetWithArrayValue()
    {
        $c = new Cookies();
        $c->set('foo', [
            'value' => 'bar',
            'path' => '/foo',
        ]);
        $prop = new \ReflectionProperty($c, 'data');
        $prop->setAccessible(true);

        $this->assertEquals('bar', $prop->getValue($c)['foo']['value']);
        $this->assertEquals('/foo', $prop->getValue($c)['foo']['path']);
    }

    public function testRemove()
    {
        $c = new Cookies();
        $prop = new \ReflectionProperty($c, 'data');
        $prop->setAccessible(true);
        $prop->setValue($c, [
            'foo' => [
                'value' => 'bar',
                'domain' => null,
                'path' => '/path',
                'expires' => null,
                'secure' => false,
                'httponly' => false,
            ],
        ]);
        $c->remove('foo');

        $this->assertEquals('', $prop->getValue($c)['foo']['value']);
        $this->assertTrue($prop->getValue($c)['foo']['value'] < time());
    }

    public function testToString()
    {
        $expiresAt = time();
        $c = new Cookies([
            'foo' => [
                'value' => 'bar',
                'expires' => $expiresAt,
                'path' => '/foo',
                'domain' => 'example.com',
                'secure' => true,
                'httponly' => true,
            ],
        ]);

        $this->assertEquals('foo=bar; domain=example.com; path=/foo; expires=' . gmdate('D, d-M-Y H:i:s e', $expiresAt) . '; secure; HttpOnly', $c->getAsString('foo'));
    }

    public function testParseHeader()
    {
        $value = 'Abc=One;Def=Two;Ghi=Three';
        $shouldBe = [
            'Abc' => 'One',
            'Def' => 'Two',
            'Ghi' => 'Three',
        ];

        $this->assertEquals($shouldBe, Cookies::parseHeader($value));
    }

    public function testParseHeaderWithOneValue()
    {
        $value = 'Abc=One';

        $this->assertEquals(['Abc' => 'One'], Cookies::parseHeader($value));
    }

    public function testParseHeaderArray()
    {
        $value = ['Abc=One;Def=Two;Ghi=Three'];
        $shouldBe = [
            'Abc' => 'One',
            'Def' => 'Two',
            'Ghi' => 'Three',
        ];

        $this->assertEquals($shouldBe, Cookies::parseHeader($value));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testParseHeaderInvalid()
    {
        Cookies::parseHeader(100);
    }

    public function testParseEmptyHeader()
    {
        $value = '';

        $this->assertEquals([], Cookies::parseHeader($value));
    }
}
