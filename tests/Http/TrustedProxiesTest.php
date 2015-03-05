<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.0
 * @package     Slim
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

use Slim\Http\TrustedProxies;

class TrustedProxiesTest extends PHPUnit_Framework_TestCase
{
    public function testInstantiation()
    {
        $trustedProxies = [
            "8.0.0.0/8",
            "155.1.1.1"
        ];

        $trustedHeaderNames = [
            TrustedProxies::HEADER_CLIENT_PROTO => "X-TEST-Proto",
            TrustedProxies::HEADER_CLIENT_IP => "X-TEST-For",
            TrustedProxies::HEADER_CLIENT_PORT => "X-TEST-Port"
        ];

        $trustedProxiesObject = TrustedProxies::create($trustedProxies, $trustedHeaderNames);

        $this->assertEquals($trustedProxies, $trustedProxiesObject->getTrustedProxies(),
            "Trusted Proxies Instntiation Proxies Mismatch");
        $this->assertEquals($trustedHeaderNames, $trustedProxiesObject->getTrustedHeaderNames(),
            "Trusted Proxies Instntiation Headers Mismatch");
    }

    /**
     * @param string|array $range Range(s) of valid IPv4 addresses
     * @param string $address Address to validate
     * @param boolean $expected
     *
     * @dataProvider provideTestIPv4AddressValidation
     */
    public function testIPv4AddressValidation($range, $address, $expected)
    {
        $trustedProxiesObject = TrustedProxies::create($range);

        $this->assertEquals($expected, $trustedProxiesObject->check($address));
    }

    public function provideTestIPv4AddressValidation()
    {
        return [
            ["1.2.3.4", "1.2.3.4", true],
            ["1.0.0.0/8", "1.2.3.4", true]//TODO: Write more
        ];
    }

    //TODO: public function testIPv6AddressValidation();
}
 