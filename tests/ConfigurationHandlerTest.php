<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@joshlockhart.com>
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

use \Slim\ConfigurationHandler;

/**
 * Configuration Test
 */
class ConfigurationHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testFlattenArray()
    {
        $con = new ConfigurationHandler;
        $con->setArray(array(
            '123' => array(
                '456' => array(
                    '789' => 1
                ),
            ),
        ));

        $this->assertArrayHasKey('123.456.789', $con->getAllFlat());
    }
    public function testSetArray()
    {
        $con = new ConfigurationHandler;
        $con->setArray(array(
            'foo' => 'bar'
        ));

        $this->assertEquals($con['foo'], 'bar');
    }

    public function testSetAndGet()
    {
        $con = new ConfigurationHandler;
        $con['foo'] = 'bar';

        $this->assertEquals($con['foo'], 'bar');
    }

    public function testKeys()
    {
        $con = new ConfigurationHandler;
        $con->setArray(array(
            'foo' => 'bar'
        ));
        $keys = $con->getKeys();

        $this->assertEquals($keys[0], 'foo');
    }

    public function  testWithNamespacedKey()
    {
        $con = new ConfigurationHandler;
        $con['my.namespaced.keyname'] = 'My Value';

        $this->arrayHasKey($con, 'my');
        $this->arrayHasKey($con['my'], 'namespaced');
        $this->arrayHasKey($con['my.namespaced'], 'keyname');
        $this->assertEquals('My Value', $con['my.namespaced.keyname']);
    }

    public function testWithString()
    {
        $con = new ConfigurationHandler;
        $con['keyname'] = 'My Value';

        $this->assertEquals('My Value', $con['keyname']);
    }

    public function testIsset()
    {
        $con = new ConfigurationHandler;
        $con['param'] = 'value';

        $this->assertTrue(isset($con['param']));
        $this->assertFalse(isset($con['non_existent']));
    }

    public function testUnset()
    {
        $con = new ConfigurationHandler;
        $con['param'] = 'value';

        unset($con['param'], $con['service']);
        $this->assertFalse(isset($con['param']));
        $this->assertFalse(isset($con['service']));
    }
}
