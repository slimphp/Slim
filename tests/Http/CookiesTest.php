<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Tests\Http;

use Slim\Http\Cookies;

class CookiesTest extends \PHPUnit_Framework_TestCase
{
    public function testExample()
    {
        $this->assertTrue(true);
    }

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
}
