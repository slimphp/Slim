<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Http;

use ReflectionProperty;
use ReflectionClass;
use InvalidArgumentException;
use Slim\Http\Cookies;

class CookiesTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $cookies = new Cookies([
            'test' => 'Works',
        ]);
        $prop = new ReflectionProperty($cookies, 'requestCookies');
        $prop->setAccessible(true);
        $this->assertNotEmpty($prop->getValue($cookies)['test']);
        $this->assertEquals('Works', $prop->getValue($cookies)['test']);
    }

    public function testSetDefaults()
    {
        $defaults = [
            'value' => 'toast',
            'domain' => null,
            'hostonly' => null,
            'path' => null,
            'expires' => null,
            'secure' => true,
            'httponly' => true
        ];

        $cookies = new Cookies;

        $prop = new ReflectionProperty($cookies, 'defaults');
        $prop->setAccessible(true);

        $origDefaults = $prop->getValue($cookies);

        $cookies->setDefaults($defaults);

        $this->assertEquals($defaults, $prop->getValue($cookies));
        $this->assertNotEquals($origDefaults, $prop->getValue($cookies));
    }

    public function testSetCookieValues()
    {
        $cookies = new Cookies;
        $cookies->set('foo', 'bar');

        $prop = new ReflectionProperty($cookies, 'responseCookies');
        $prop->setAccessible(true);

        //we expect all of these values with null/false defaults
        $expectedValue = [
            'foo' => [
                'value' => 'bar',
                'domain' => null,
                'hostonly' => null,
                'path' => null,
                'expires' => null,
                'secure' => false,
                'httponly' => false
            ]
        ];

        $this->assertEquals($expectedValue, $prop->getValue($cookies));
    }

    public function testSetCookieValuesContainDefaults()
    {
        $cookies = new Cookies;
        $defaults = [
            'value' => 'toast',
            'domain' => null,
            'hostonly' => null,
            'path' => null,
            'expires' => null,
            'secure' => true,
            'httponly' => true
        ];

        $cookies->setDefaults($defaults);
        $cookies->set('foo', 'bar');

        $prop = new ReflectionProperty($cookies, 'responseCookies');
        $prop->setAccessible(true);

        //we expect to have secure and httponly from defaults
        $expectedValue = [
            'foo' => [
                'value' => 'bar',
                'domain' => null,
                'hostonly' => null,
                'path' => null,
                'expires' => null,
                'secure' => true,
                'httponly' => true
            ]
        ];

        $this->assertEquals($expectedValue, $prop->getValue($cookies));
    }

    public function testSetCookieValuesCanOverrideDefaults()
    {
        $cookies = new Cookies;
        $defaults = [
            'value' => 'toast',
            'domain' => null,
            'hostonly' => null,
            'path' => null,
            'expires' => null,
            'secure' => true,
            'httponly' => true
        ];

        $cookies->setDefaults($defaults);

        //default has secure true, lets override it to false
        $cookies->set('foo', ['value' => 'bar', 'secure' => false]);

        $prop = new ReflectionProperty($cookies, 'responseCookies');
        $prop->setAccessible(true);

        $expectedValue = [
            'foo' => [
                'value' => 'bar',
                'domain' => null,
                'hostonly' => null,
                'path' => null,
                'expires' => null,
                'secure' => false,
                'httponly' => true
            ]
        ];

        $this->assertEquals($expectedValue, $prop->getValue($cookies));
    }

    public function testGet()
    {
        $cookies = new Cookies(['foo' => 'bar']);
        $this->assertEquals('bar', $cookies->get('foo'));
        $this->assertNull($cookies->get('missing'));
        $this->assertEquals('defaultValue', $cookies->get('missing', 'defaultValue'));
    }

    public function testParseHeader()
    {
        $cookies = Cookies::parseHeader('foo=bar; name=Josh');
        $this->assertEquals('bar', $cookies['foo']);
        $this->assertEquals('Josh', $cookies['name']);
    }

    public function testParseHeaderWithJsonArray()
    {
        $cookies = Cookies::parseHeader('foo=bar; testarray=["someVar1","someVar2","someVar3"]');
        $this->assertEquals('bar', $cookies['foo']);
        $this->assertContains('someVar3', json_decode($cookies['testarray']));
    }

    public function testToHeaders()
    {
        $cookies = new Cookies;
        $cookies->set('test', 'Works');
        $cookies->set('test_array', ['value' => 'bar', 'domain' => 'example.com']);
        $this->assertEquals('test=Works', $cookies->toHeaders()[0]);
        $this->assertEquals('test_array=bar; domain=example.com', $cookies->toHeaders()[1]);
    }

    public function testToHeader()
    {
        $cookies = new Cookies();
        $class = new ReflectionClass($cookies);
        $method = $class->getMethod('toHeader');
        $method->setAccessible(true);
        $properties = [
            'name' => 'test',
            'properties' => [
                'value' => 'Works'
            ]
        ];
        $time = time();
        $formattedDate = gmdate('D, d-M-Y H:i:s e', $time);
        $propertiesComplex = [
            'name' => 'test_complex',
            'properties' => [
                'value' => 'Works',
                'domain' => 'example.com',
                'expires' => $time,
                'path' => '/',
                'secure' => true,
                'hostonly' => true,
                'httponly' => true
            ]
        ];
        $stringDate = '2016-01-01 12:00:00';
        $formattedStringDate = gmdate('D, d-M-Y H:i:s e', strtotime($stringDate));
        $propertiesStringDate = [
            'name' => 'test_date',
            'properties' => [
                'value' => 'Works',
                'expires' => $stringDate,
            ]
        ];
        $cookie = $method->invokeArgs($cookies, $properties);
        $cookieComplex = $method->invokeArgs($cookies, $propertiesComplex);
        $cookieStringDate = $method->invokeArgs($cookies, $propertiesStringDate);
        $this->assertEquals('test=Works', $cookie);
        $this->assertEquals(
            'test_complex=Works; domain=example.com; path=/; expires='
            . $formattedDate . '; secure; HostOnly; HttpOnly',
            $cookieComplex
        );
        $this->assertEquals('test_date=Works; expires=' . $formattedStringDate, $cookieStringDate);
    }

    public function testParseHeaderException()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        Cookies::parseHeader(new \StdClass);
    }
}
