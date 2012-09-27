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

class SlimHttpCachelTest extends PHPUnit_Framework_TestCase
{
    /************************************************
     * HTTP CACHING
     ************************************************/

    /**
     * Test Last-Modified match
     */
    public function testLastModifiedMatch()
    {
        $this->setExpectedException('\Slim\Exception\Stop');
        \Slim\Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'IF_MODIFIED_SINCE' => 'Sun, 03 Oct 2010 17:00:52 -0400',
        ));
        try {
            $request = new \Slim\Http\Request(\Slim\Environment::getInstance());
            $response = new \Slim\Http\Response();
            $httpCache = new \Slim\Http\HttpCache($request, $response);
            $httpCache->lastModified(1286139652);
        } catch(Exception $e) {
            $this->assertEquals(304, $response->status());
            throw $e;
        }
    }

    /**
     * Test Last-Modified match
     */
    public function testLastModifiedDoesNotMatch()
    {
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'IF_MODIFIED_SINCE' => 'Sun, 03 Oct 2010 17:00:52 -0400',
        ));
        $request = new \Slim\Http\Request(\Slim\Environment::getInstance());
        $response = new \Slim\Http\Response();
        $httpCache = new \Slim\Http\HttpCache($request, $response);
        $httpCache->lastModified(1286139250);
        $this->assertEquals(200, $response->status());
    }

    /**
     * Test Last-Modified only accepts integers
     */
    public function testLastModifiedOnlyAcceptsIntegers()
    {
        $this->setExpectedException('\InvalidArgumentException');
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
        ));
        $request = new \Slim\Http\Request(\Slim\Environment::getInstance());
        $response = new \Slim\Http\Response();
        $httpCache = new \Slim\Http\HttpCache($request, $response);
        $httpCache->lastModified('Test');
    }

    /**
     * Test ETag matches
     */
    public function testEtagMatches()
    {
        $this->setExpectedException('\Slim\Exception\Stop');
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'IF_NONE_MATCH' => '"abc123"',
        ));
        try {
            $request = new \Slim\Http\Request(\Slim\Environment::getInstance());
            $response = new \Slim\Http\Response();
            $httpCache = new \Slim\Http\HttpCache($request, $response);
            $httpCache->etag('abc123');
        } catch (Exception $e) {
            $this->assertEquals(304, $response->status());
            throw $e;
        }
    }

    /**
     * Test ETag does not match
     */
    public function testEtagDoesNotMatch()
    {
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'IF_NONE_MATCH' => '"abc1234"',
        ));
        $request = new \Slim\Http\Request(\Slim\Environment::getInstance());
        $response = new \Slim\Http\Response();
        $httpCache = new \Slim\Http\HttpCache($request, $response);
        $httpCache->etag('abc123');
        $this->assertEquals(200, $response->status());
    }

    /**
     * Test ETag with invalid type
     */
    public function testETagWithInvalidType()
    {
        $this->setExpectedException('\InvalidArgumentException');
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'IF_NONE_MATCH' => '"abc1234"',
        ));
        $request = new \Slim\Http\Request(\Slim\Environment::getInstance());
        $response = new \Slim\Http\Response();
        $httpCache = new \Slim\Http\HttpCache($request, $response);
        $httpCache->etag('123','foo');
    }

    /**
     * Test Expires
     */
    public function testExpiresAsString()
    {
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
        ));
        $expectedDate = gmdate('D, d M Y', strtotime('5 days')); //Just the day, month, and year
        $request = new \Slim\Http\Request(\Slim\Environment::getInstance());
        $response = new \Slim\Http\Response();
        $httpCache = new \Slim\Http\HttpCache($request, $response);
        $httpCache->expires('5 days');
        list($status, $header, $body) = $response->finalize();
        $this->assertTrue(isset($header['Expires']));
        $this->assertEquals(0, strpos($header['Expires'], $expectedDate));
    }

    /**
     * Test Expires
     */
    public function testExpiresAsInteger()
    {
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
        ));
        $fiveDaysFromNow = time() + (60 * 60 * 24 * 5);
        $expectedDate = gmdate('D, d M Y', $fiveDaysFromNow); //Just the day, month, and year
        $request = new \Slim\Http\Request(\Slim\Environment::getInstance());
        $response = new \Slim\Http\Response();
        $httpCache = new \Slim\Http\HttpCache($request, $response);
        $httpCache->expires($fiveDaysFromNow);
        list($status, $header, $body) = $response->finalize();
        $this->assertTrue(isset($header['Expires']));
        $this->assertEquals(0, strpos($header['Expires'], $expectedDate));
    }
}
