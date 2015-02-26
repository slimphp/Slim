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
use \Slim\ConfigurationHandler;

class HandlerTest extends ConfigurationHandler
{}

/**
 * Configuration Test
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public $defaults = array(
        // Application
        'mode' => 'development',
        'view.templates' => '../templates',
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
        'session.handler' => null,
        'session.flash_key' => 'slimflash',
        'session.encrypt' => false,
        // HTTP
        'http.version' => '1.1'
    );

    public function testConstructorInjection()
    {
        $values = array("param" => "value");
        $con = new Configuration(new HandlerTest);
        $con->setArray($values);

        $this->assertSame($values['param'], $con['param']);
    }

    public function testSetDefaultValues()
    {
        $con = new Configuration(new HandlerTest);

        foreach ($this->defaults as $key => $value) {
            $this->assertEquals($con[$key], $value);
        }
    }

    public function testGetDefaultValues()
    {
        $con = new Configuration(new HandlerTest);
        $defaults = $con->getDefaults();

        foreach ($this->defaults as $key => $value) {
            $this->assertEquals($defaults[$key], $value);
        }
    }

    public function testCallHandlerMethod()
    {
        $con = new Configuration(new HandlerTest);
        $defaultKeys = array_keys($this->defaults);
        $defaultKeys = ksort($defaultKeys);
        $configKeys = $con->callHandlerMethod('getKeys');
        $configKeys = ksort($configKeys);

        $this->assertEquals($defaultKeys, $configKeys);
    }
}
