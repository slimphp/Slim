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

use \Slim\Configuration;

/**
 * Blank Service class
 */
class Service
{
}

/**
 * Simple Invokable class
 */
class Invokable
{
    public function __invoke($value = null)
    {
        $service = new Service();
        $service->value = $value;

        return $service;
    }
}

/**
 * Simple NonInvokable class
 */
class NonInvokable
{
    public function __call($a, $b)
    {
    }
}

/**
 * Configuration Test
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public $defaults = array(
        // Application
        'mode' => 'development',
        'view' => null,
        // Cookies
        'cookies.encrypt' => false,
        'cookies.lifetime' => '20 minutes',
        'cookies.path' => '/',
        'cookies.domain' => null,
        'cookies.secure' => false,
        'cookies.httponly' => false,
        // Encryption
        'crypt.key' => 'A9s_lWeIn7cML8M]S6Xg4aR^GwovA&UN',
        'crypt.cipher' => MCRYPT_RIJNDAEL_256,
        'crypt.mode' => MCRYPT_MODE_CBC,
        // Session
        'session.options' => array(),
        'session.handler' => null,
        'session.flash_key' => 'slimflash',
        'session.encrypt' => false,
        // HTTP
        'http.version' => '1.1'
    );

    public function testConstructorInjection()
    {
        $values = array("param" => "value");
        $con = new Configuration($values);

        $this->assertSame($values['param'], $con['param']);
    }

    public function testDefaultValues()
    {
        $con = new Configuration();

        foreach ($this->defaults as $key => $value) {
            $this->assertEquals($con[$key], $value);
        }
    }

    public function testKeys()
    {
        $con = new Configuration();
        $defaultKeys = array_keys($this->defaults);
        $defaultKeys = ksort($defaultKeys);
        $configKeys = $con->getKeys();
        $configKeys = ksort($configKeys);

        $this->assertEquals($defaultKeys, $configKeys);
    }

    public function  testWithNamespacedKey()
    {
        $con = new Configuration();
        $con['my.namespaced.keyname'] = 'My Value';

        $this->arrayHasKey($con, 'my');
        $this->arrayHasKey($con['my'], 'namespaced');
        $this->arrayHasKey($con['my.namespaced'], 'keyname');
        $this->assertEquals('My Value', $con['my.namespaced.keyname']);
    }

    public function testWithString()
    {
        $con = new Configuration();
        $con['keyname'] = 'My Value';

        $this->assertEquals('My Value', $con['keyname']);
    }

    public function testIsset()
    {
        $con = new Configuration();
        $con['param'] = 'value';
        $con['service'] = function () {
            return new Service();
        };

        $this->assertTrue(isset($con['param']));
        $this->assertTrue(isset($con['service']));
        $this->assertFalse(isset($con['non_existent']));
    }

    public function testUnset()
    {
        $con = new Configuration();
        $con['param'] = 'value';
        $con['service'] = function () {
            return new Service();
        };

        unset($con['param'], $con['service']);
        $this->assertFalse(isset($con['param']));
        $this->assertFalse(isset($con['service']));
    }
}
