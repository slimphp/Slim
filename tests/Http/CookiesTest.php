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
class CookiesTest extends PHPUnit_Framework_TestCase
{
    public function testInstanceOfInterface()
    {
        $env = new \Slim\Http\Cookies();
        $this->assertInstanceOf('\Slim\Interfaces\Http\CookiesInterface', $env);
    }

    public function testCreateFromHeaders()
    {
        $environment = new \Slim\Environment(array('HTTP_COOKIE' => 'foo=bar;abc=123'));
        $headers = new \Slim\Http\Headers($environment);
        $cookies = new \Slim\Http\Cookies($headers);

        $this->assertEquals('bar', $cookies->get('foo'));
        $this->assertEquals('123', $cookies->get('abc'));
    }

    public function testSetWithStringValue()
    {
        $cookies = new \Slim\Http\Cookies();
        $cookies->set('foo', 'bar');

        $this->assertAttributeEquals(
            array(
                'foo' => array(
                    'value' => 'bar',
                    'expires' => null,
                    'domain' => null,
                    'path' => null,
                    'secure' => false,
                    'httponly' => false
                )
            ),
            'values',
            $cookies
        );
    }

    public function testSetWithArrayValue()
    {
        $now = time();
        $cookies = new \Slim\Http\Cookies();
        $cookies->set('foo', array(
            'value' => 'bar',
            'expires' => $now + 86400,
            'domain' => '.example.com',
            'path' => '/',
            'secure' => true,
            'httponly' => true
        ));
        $this->assertAttributeEquals(
            array(
                'foo' => array(
                    'value' => 'bar',
                    'expires' => $now + 86400,
                    'domain' => '.example.com',
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true
                )
            ),
            'values',
            $cookies
        );
    }

    public function testRemove()
    {
        $cookies = new \Slim\Http\Cookies();
        $cookies->remove('foo');
        $prop = new \ReflectionProperty($cookies, 'values');
        $prop->setAccessible(true);
        $cValue = $prop->getValue($cookies);
        $this->assertEquals('', $cValue['foo']['value']);
        $this->assertLessThan(time(), $cValue['foo']['expires']);
    }

    public function testSetCookieHeaderWithNameAndValue()
    {
        $name = 'foo';
        $value = 'bar';
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $cookies->setHeader($headers, $name, $value);
        $this->assertEquals('foo=bar', $headers->get('Set-Cookie'));
    }

    public function testSetCookieHeaderWithNameAndValueWhenCookieAlreadySet()
    {
        $name = 'foo';
        $value = 'bar';
        $headers = new \Slim\Http\Headers();
        $headers->set('Set-Cookie', 'one=two');
        $cookies = new \Slim\Http\Cookies();
        $cookies->setHeader($headers, $name, $value);
        $this->assertEquals("one=two\nfoo=bar", $headers->get('Set-Cookie'));
    }

    public function testSetCookieHeaderWithNameAndValueAndDomain()
    {
        $name = 'foo';
        $value = 'bar';
        $domain = 'foo.com';
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $cookies->setHeader($headers, $name, array(
            'value' => $value,
            'domain' => $domain
        ));
        $this->assertEquals('foo=bar; domain=foo.com', $headers->get('Set-Cookie'));
    }

    public function testSetCookieHeaderWithNameAndValueAndDomainAndPath()
    {
        $name = 'foo';
        $value = 'bar';
        $domain = 'foo.com';
        $path = '/foo';
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $cookies->setHeader($headers, $name, array(
            'value' => $value,
            'domain' => $domain,
            'path' => $path
        ));
        $this->assertEquals('foo=bar; domain=foo.com; path=/foo', $headers->get('Set-Cookie'));
    }

    public function testSetCookieHeaderWithNameAndValueAndDomainAndPathAndExpiresAsString()
    {
        $name = 'foo';
        $value = 'bar';
        $domain = 'foo.com';
        $path = '/foo';
        $expires = '2 days';
        $expiresFormat = gmdate('D, d-M-Y H:i:s e', strtotime($expires));
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $cookies->setHeader($headers, $name, array(
            'value' => $value,
            'domain' => $domain,
            'path' => '/foo',
            'expires' => $expires
        ));
        $this->assertEquals('foo=bar; domain=foo.com; path=/foo; expires=' . $expiresFormat, $headers->get('Set-Cookie'));
    }

    public function testSetCookieHeaderWithNameAndValueAndDomainAndPathAndExpiresAsInteger()
    {
        $name = 'foo';
        $value = 'bar';
        $domain = 'foo.com';
        $path = '/foo';
        $expires = strtotime('2 days');
        $expiresFormat = gmdate('D, d-M-Y H:i:s e', $expires);
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $cookies->setHeader($headers, $name, array(
            'value' => $value,
            'domain' => $domain,
            'path' => '/foo',
            'expires' => $expires
        ));
        $this->assertEquals('foo=bar; domain=foo.com; path=/foo; expires=' . $expiresFormat, $headers->get('Set-Cookie'));
    }

    public function testSetCookieHeaderWithNameAndValueAndDomainAndPathAndExpiresAsZero()
    {
        $name = 'foo';
        $value = 'bar';
        $domain = 'foo.com';
        $path = '/foo';
        $expires = 0;
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $cookies->setHeader($headers, $name, array(
            'value' => $value,
            'domain' => $domain,
            'path' => '/foo',
            'expires' => $expires
        ));
        $this->assertEquals('foo=bar; domain=foo.com; path=/foo', $headers->get('Set-Cookie'));
    }

    public function testSetCookieHeaderWithNameAndValueAndDomainAndPathAndExpiresAndSecure()
    {
        $name = 'foo';
        $value = 'bar';
        $domain = 'foo.com';
        $path = '/foo';
        $expires = strtotime('2 days');
        $expiresFormat = gmdate('D, d-M-Y H:i:s e', $expires);
        $secure = true;
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $cookies->setHeader($headers, $name, array(
            'value' => $value,
            'domain' => $domain,
            'path' => '/foo',
            'expires' => $expires,
            'secure' => $secure
        ));
        $this->assertEquals('foo=bar; domain=foo.com; path=/foo; expires=' . $expiresFormat . '; secure', $headers->get('Set-Cookie'));
    }

    public function testSetCookieHeaderWithNameAndValueAndDomainAndPathAndExpiresAndSecureAndHttpOnly()
    {
        $name = 'foo';
        $value = 'bar';
        $domain = 'foo.com';
        $path = '/foo';
        $expires = strtotime('2 days');
        $expiresFormat = gmdate('D, d-M-Y H:i:s e', $expires);
        $secure = true;
        $httpOnly = true;
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $cookies->setHeader($headers, $name, array(
            'value' => $value,
            'domain' => $domain,
            'path' => '/foo',
            'expires' => $expires,
            'secure' => $secure,
            'httponly' => $httpOnly
        ));
        $this->assertEquals('foo=bar; domain=foo.com; path=/foo; expires=' . $expiresFormat . '; secure; HttpOnly', $headers->get('Set-Cookie'));
    }

    public function testDeleteCookieHeaderWithSurvivingCookie()
    {
        $headers = new \Slim\Http\Headers();
        $headers->replace(array('Set-Cookie' => "foo=bar\none=two"));
        $cookies = new \Slim\Http\Cookies();
        $cookies->deleteHeader($headers, 'foo');
        $this->assertEquals(1, preg_match("@^one=two\nfoo=; expires=@", $headers->get('Set-Cookie')));
    }

    public function testDeleteCookieHeaderWithoutSurvivingCookie()
    {
        $headers = new \Slim\Http\Headers();
        $headers->replace(array('Set-Cookie' => "foo=bar"));
        $cookies = new \Slim\Http\Cookies();
        $cookies->deleteHeader($headers, 'foo');
        $this->assertEquals(1, preg_match("@foo=; expires=@", $headers->get('Set-Cookie')));
    }

    public function testDeleteCookieHeaderWithMatchingDomain()
    {
        $headers = new \Slim\Http\Headers();
        $headers->replace(array('Set-Cookie' => "foo=bar; domain=foo.com"));
        $cookies = new \Slim\Http\Cookies();
        $cookies->deleteHeader($headers, 'foo', array(
            'domain' => 'foo.com'
        ));
        $this->assertEquals(1, preg_match("@foo=; domain=foo.com; expires=@", $headers->get('Set-Cookie')));
    }

    public function testDeleteCookieHeaderWithoutMatchingDomain()
    {
        $headers = new \Slim\Http\Headers();
        $headers->replace(array('Set-Cookie' => "foo=bar; domain=foo.com"));
        $cookies = new \Slim\Http\Cookies();
        $cookies->deleteHeader($headers, 'foo', array(
            'domain' => 'bar.com'
        ));
        $this->assertEquals(1, preg_match("@foo=bar; domain=foo\.com\nfoo=; domain=bar\.com@", $headers->get('Set-Cookie')));
    }

    /**
     * Test parses Cookie: HTTP header
     */
    public function testParsesCookieHeader()
    {
        $header = 'foo=bar; one=two; colors=blue';
        $cookies = new \Slim\Http\Cookies();
        $result = $cookies->parseHeader($header);
        $this->assertEquals(3, count($result));
        $this->assertEquals('bar', $result['foo']);
        $this->assertEquals('two', $result['one']);
        $this->assertEquals('blue', $result['colors']);
    }

    public function testParsesCookieHeaderWithCommaSeparator()
    {
        $header = 'foo=bar, one=two, colors=blue';
        $cookies = new \Slim\Http\Cookies();
        $result = $cookies->parseHeader($header);
        $this->assertEquals(3, count($result));
        $this->assertEquals('bar', $result['foo']);
        $this->assertEquals('two', $result['one']);
        $this->assertEquals('blue', $result['colors']);
    }

    public function testPrefersLeftmostCookieWhenManyCookiesWithSameName()
    {
        $header = 'foo=bar; foo=beer';
        $cookies = new \Slim\Http\Cookies();
        $result = $cookies->parseHeader($header);
        $this->assertEquals('bar', $result['foo']);
    }
}
