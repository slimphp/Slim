<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@joshlockhart.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.5.0
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

set_include_path(dirname(__FILE__) . '/../../' . PATH_SEPARATOR . get_include_path());

require_once 'Slim/Http/Cookie.php';

class CookieTest extends PHPUnit_Framework_TestCase {

    /**
     * Test cookie sets and gets properties
     *
     * Pre-conditions:
     * A cookie is instantiated
     *
     * Post-conditions:
     * Case A: Name is set and is as string
     * Case B: Value is set and is as string
     * Case C: Expires is set as an integer
     * Case D: Expires is set as a string
     * Case E: Path is set and is a string
     * Case F: Domain is set and is a string
     * Case G: Secure is set and is bool
     * Case H: HTTP only is set and is bool
     */
    public function testNewCookie() {
        $hourFromNow = time() + 3600;
        $c1 = new Slim_Http_Cookie('foo1', 'bar1', $hourFromNow, '/foo', 'domain.com', true, true);
        $c2 = new Slim_Http_Cookie('foo2', 'bar2', '1 hour', '/foo', 'domain.com', false, false);
        //Case A
        $this->assertEquals('foo1', $c1->getName());
        //Case B
        $this->assertEquals('bar1', $c1->getValue());
        //Case C
        $this->assertEquals($hourFromNow, $c1->getExpires());
        //Case D
        $this->assertGreaterThanOrEqual($hourFromNow, $c2->getExpires());
        //Case E
        $this->assertEquals('/foo', $c1->getPath());
        //Case F
        $this->assertEquals('domain.com', $c1->getDomain());
        //Case G
        $this->assertTrue($c1->getSecure());
        $this->assertFalse($c2->getSecure());
        //Case H
        $this->assertTrue($c1->getHttpOnly());
        $this->assertFalse($c2->getHttpOnly());
    }

}
