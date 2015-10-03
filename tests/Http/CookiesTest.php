<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Http;

use ReflectionProperty;
use Slim\Http\Cookies;

class CookiesTest extends \PHPUnit_Framework_TestCase
{
    // public function testArrayToString()
    // {
    //     $expiresAt = time();
    //     $result = Cookies::arrayToString([
    //         'value' => 'bar',
    //         'expires' => $expiresAt,
    //         'path' => '/foo',
    //         'domain' => 'example.com',
    //         'secure' => true,
    //         'httponly' => true
    //     ]);

    //     $this->assertEquals('bar; domain=example.com; path=/foo; expires=' . gmdate('D, d-M-Y H:i:s e', $expiresAt) . '; secure; HttpOnly', $result);
    // }

    // /**
    //  * @expectedException \InvalidArgumentException
    //  */
    // public function testArrayToStringWithoutValue()
    // {
    //     $result = Cookies::arrayToString([
    //         'expires' => time(),
    //         'path' => '/foo',
    //         'domain' => 'example.com',
    //         'secure' => true,
    //         'httponly' => true
    //     ]);
    // }

    // public function testParseHeader()
    // {
    //     $value = 'Abc=One;Def=Two;Ghi=Three';
    //     $shouldBe = [
    //         'Abc' => 'One',
    //         'Def' => 'Two',
    //         'Ghi' => 'Three',
    //     ];

    //     $this->assertEquals($shouldBe, Cookies::parseHeader($value));
    // }

    // public function testParseHeaderWithOneValue()
    // {
    //     $value = 'Abc=One';

    //     $this->assertEquals(['Abc' => 'One'], Cookies::parseHeader($value));
    // }

    // public function testParseHeaderArray()
    // {
    //     $value = ['Abc=One;Def=Two;Ghi=Three'];
    //     $shouldBe = [
    //         'Abc' => 'One',
    //         'Def' => 'Two',
    //         'Ghi' => 'Three',
    //     ];

    //     $this->assertEquals($shouldBe, Cookies::parseHeader($value));
    // }

    // /**
    //  * @expectedException \InvalidArgumentException
    //  */
    // public function testParseHeaderInvalid()
    // {
    //     Cookies::parseHeader(100);
    // }

    // public function testParseEmptyHeader()
    // {
    //     $value = '';

    //     $this->assertEquals([], Cookies::parseHeader($value));
    // }

    public function testSetDefaults()
    {
        $defaults = [
            'value' => 'toast',
            'domain' => null,
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
}
