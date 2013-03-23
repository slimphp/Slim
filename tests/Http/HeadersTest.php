<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.2.0
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

class HeadersTest extends PHPUnit_Framework_TestCase
{
    public function testConstructWithoutArg()
    {
        $headers = new \Slim\Http\Headers();

        $this->assertAttributeEquals(array(), 'headers', $headers);
    }

    public function testConstructWithArg()
    {
        $headers = new \Slim\Http\Headers(array('Content-Type' => 'text/html'));

        $this->assertAttributeEquals(array('content-type' => 'text/html'), 'headers', $headers);
    }

    public function testMerge()
    {
        $headers = new \Slim\Http\Headers();

        $property = new \ReflectionProperty($headers, 'headers');
        $property->setAccessible(true);
        $property->setValue($headers, array('content-length' => 100));

        $headers->merge(array('Content-Type' => 'text/html'));

        $this->assertAttributeEquals(
            array(
                'content-type' => 'text/html',
                'content-length' => 100
            ),
            'headers',
            $headers
        );
    }

    public function testIterable()
    {
        $headers = new \Slim\Http\Headers(array(
            'Content-Type' => 'text/html',
            'Content-Length' => 100
        ));
        $iteratorResults = array();

        foreach ($headers as $name => $value) {
            $iteratorResults[$name] = $value;
        }

        $this->assertEquals(
            array(
                'Content-Type' => 'text/html',
                'Content-Length' => 100
            ),
            $iteratorResults
        );
    }

    public function testCountable()
    {
        $headers = new \Slim\Http\Headers(array(
            'Content-Type' => 'text/html',
            'Content-Length' => 100
        ));

        $this->assertEquals(2, count($headers));
    }

    /**
     * @covers Slim\Http\Response::setHeader
     */
    public function testArrayAccessSetter()
    {
        $headers = new \Slim\Http\Headers();
        $headers['Content-Length'] = 100;

        $this->assertAttributeEquals(
            array('content-length' => 100),
            'headers',
            $headers
        );
    }

    /**
     * @covers Slim\Http\Headers::offsetGet
     * @covers Slim\Http\Response::getHeader
     */
    public function testArrayAccessGetterExists()
    {
        $headers = new \Slim\Http\Headers(array(
            'Content-Type' => 'text/html',
            'Content-Length' => 100
        ));

        $this->assertEquals('text/html', $headers['Content-Type']);
    }

    /**
     * @covers Slim\Http\Headers::offsetGet
     * @covers Slim\Http\Response::getHeader
     */
    public function testArrayAccessGetterNotExists()
    {
        $headers = new \Slim\Http\Headers(array(
            'Content-Type' => 'text/html',
            'Content-Length' => 100
        ));

        $this->assertNull($headers['foo']);
    }

    public function testArrayAccessExists()
    {
        $headers = new \Slim\Http\Headers(array(
            'Content-Type' => 'text/html',
            'Content-Length' => 100
        ));

        $this->assertTrue(isset($headers['Content-Type']));
    }

    public function testArrayAccessUnset()
    {
        $headers = new \Slim\Http\Headers(array(
            'Content-Type' => 'text/html',
            'Content-Length' => 100
        ));
        unset($headers['Content-Type']);

        $this->assertAttributeEquals(
            array('content-length' => 100),
            'headers',
            $headers
        );
    }
}
