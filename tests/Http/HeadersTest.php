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

class HeadersTest extends PHPUnit_Framework_TestCase
{
    protected $headers;
    protected $property;

    public function setUp()
    {
        $this->headers = new \Slim\Http\Headers();
        $this->property = new \ReflectionProperty($this->headers, 'data');
        $this->property->setAccessible(true);
    }

    public function testCreateFromEnvironment()
    {
        $env = new \Slim\Environment();
        $env->mock(array(
            'HTTP_ACCEPT' => 'application/json', // <-- Normal header with custom value
            'PHP_AUTH_USER' => 'josh' // <-- Special header
        ));
        $headers = new \Slim\Http\Headers($env);
        $this->assertInstanceOf('\Slim\Http\Headers', $headers);
        $this->assertEquals('application/json', $headers->get('Accept'));
        $this->assertEquals('josh', $headers->get('Php-Auth-User'));
    }

    public function testSet()
    {
        $this->headers->set('Http_Content_Type', 'text/html');
        $this->assertArrayHasKey('Content-Type', $this->property->getValue($this->headers));
    }

    public function testGet()
    {
        $this->property->setValue($this->headers, array('Content-Length' => 100));
        $this->assertEquals(100, $this->headers->get('CONTENT_LENGTH'));
    }

    public function testHas()
    {
        $this->property->setValue($this->headers, array('Content-Length' => 100));
        $this->assertTrue($this->headers->has('CONTENT_LENGTH'));
    }

    public function testRemove()
    {
        $this->property->setValue($this->headers, array('Content-Length' => 100));
        $this->assertEquals(1, count($this->property->getValue($this->headers)));
        $this->headers->remove('CONTENT_LENGTH');
        $this->assertEquals(0, count($this->property->getValue($this->headers)));
    }

    public function testCapturesSpecialHeaders()
    {
        $env = new \Slim\Environment();
        $env->mock(array(
            'CONTENT_TYPE' => 'text/csv',
            'CONTENT_LENGTH' => 10,
            'PHP_AUTH_USER' => 'foo',
            'PHP_AUTH_PW' => 'bar',
            'PHP_AUTH_DIGEST' => 'Basic bXl1c2VyOm15cGFzcw==',
            'AUTH_TYPE' => 'basic'
        ));
        $headers = new \Slim\Http\Headers($env);
        $this->assertEquals('text/csv', $headers->get('Content-Type'));
        $this->assertEquals(10, $headers->get('Content-Length'));
        $this->assertEquals('foo', $headers->get('Php-Auth-User'));
        $this->assertEquals('bar', $headers->get('Php-Auth-Pw'));
        $this->assertEquals('Basic bXl1c2VyOm15cGFzcw==', $headers->get('Php-Auth-Digest'));
        $this->assertEquals('basic', $headers->get('Auth-Type'));
    }

    public function testDoesNotUseCertainHeaders()
    {
        $env = new \Slim\Environment();
        $env->mock(array(
            'CONTENT_TYPE' => 'text/csv',
            'HTTP_CONTENT_TYPE' => 'text/plain',
            'CONTENT_LENGTH' => 10,
            'HTTP_CONTENT_LENGTH' => 20
        ));
        $headers = new \Slim\Http\Headers($env);
        $this->assertEquals('text/csv', $headers->get('HTTP_CONTENT_TYPE'));
        $this->assertEquals(10, $headers->get('HTTP_CONTENT_LENGTH'));
    }
}
