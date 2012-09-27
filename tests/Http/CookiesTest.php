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

class SlimHttpCookiesTest extends PHPUnit_Framework_TestCase
{
    protected $settings;

    public function setUp() {
        $this->settings = array(
            'cookies.lifetime' => '20 minutes',
            'cookies.path' => '/',
            'cookies.domain' => null,
            'cookies.secure' => false,
            'cookies.httponly' => false,
            'cookies.secret_key' => 'CHANGE_ME',
            'cookies.cipher' => MCRYPT_RIJNDAEL_256,
            'cookies.cipher_mode' => MCRYPT_MODE_CBC,
        );
    }
    /************************************************
     * COOKIES
     ************************************************/

    /**
     * Set cookie
     *
     * This tests that the Slim application instance sets
     * a cookie in the HTTP response header. This does NOT
     * test the implementation of setting the cookie; that is
     * tested in a separate file.
     */
    public function testSetCookie()
    {
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
        ));
        $request = new \Slim\Http\Request(\Slim\Environment::getInstance());
        $response = new \Slim\Http\Response();
        $c = new \Slim\Http\Cookies($request, $response, $this->settings);
        $c->setCookie('foo', 'bar', '2 days');
        $c->setCookie('foo1', 'bar1', '2 days');
        list($status, $header, $body) = $response->finalize();
        $cookies = explode("\n", $header['Set-Cookie']);
        $this->assertEquals(2, count($cookies));
        $this->assertEquals(1, preg_match('@foo=bar@', $cookies[0]));
        $this->assertEquals(1, preg_match('@foo1=bar1@', $cookies[1]));
    }

    /**
     * Test get cookie
     *
     * This method ensures that the `Cookie:` HTTP request
     * header is parsed if present, and made accessible via the
     * Request object.
     */
    public function testGetCookie()
    {
        \Slim\Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com',
            'SERVER_PORT' => 80,
            'COOKIE' => 'foo=bar; foo2=bar2',
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => @fopen('php://stderr', 'w')
        ));
        $request = new \Slim\Http\Request(\Slim\Environment::getInstance());
        $response = new \Slim\Http\Response();
        $c = new \Slim\Http\Cookies($request, $response, $this->settings);
        $this->assertEquals('bar', $c->getCookie('foo'));
        $this->assertEquals('bar2', $c->getCookie('foo2'));
    }

    /**
     * Test get cookie when cookie does not exist
     */
    public function testGetCookieThatDoesNotExist()
    {
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
        ));
        $request = new \Slim\Http\Request(\Slim\Environment::getInstance());
        $response = new \Slim\Http\Response();
        $c = new \Slim\Http\Cookies($request, $response, $this->settings);
        $this->assertNull($c->getCookie('foo'));
    }

    /**
     * Test delete cookie
     *
     * This method ensures that the `Set-Cookie:` HTTP response
     * header is set. The implementation of setting the response
     * cookie is tested separately in another file.
     */
    public function testDeleteCookie()
    {
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'COOKIE' => 'foo=bar; foo2=bar2',
        ));
        $request = new \Slim\Http\Request(\Slim\Environment::getInstance());
        $response = new \Slim\Http\Response();
        $c = new \Slim\Http\Cookies($request, $response, $this->settings);
        $c->setCookie('foo', 'bar');
        $c->deleteCookie('foo');
        list($status, $header, $body) = $response->finalize();
        $cookies = explode("\n", $header['Set-Cookie']);
        $this->assertEquals(1, count($cookies));
        $this->assertEquals(1, preg_match('@^foo=;@', $cookies[0]));
    }

    /**
     * Test set encrypted cookie
     *
     * This method ensures that the `Set-Cookie:` HTTP request
     * header is set. The implementation is tested in a separate file.
     */
    public function testSetEncryptedCookie()
    {
        $request = new \Slim\Http\Request(\Slim\Environment::getInstance());
        $response = new \Slim\Http\Response();
        $c = new \Slim\Http\Cookies($request, $response, $this->settings);
        $c->setEncryptedCookie('foo', 'bar');
        $this->assertEquals(1, preg_match("@^foo=.+%7C.+%7C.+@", $response['Set-Cookie'])); //<-- %7C is a url-encoded pipe
    }

    /**
     * Test get encrypted cookie
     *
     * This only tests that this method runs without error. The implementation of
     * fetching the encrypted cookie is tested separately.
     */
    public function testGetEncryptedCookieAndDeletingIt()
    {
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
        ));
        $request = new \Slim\Http\Request(\Slim\Environment::getInstance());
        $response = new \Slim\Http\Response();
        $c = new \Slim\Http\Cookies($request, $response, $this->settings);
        $this->assertFalse($c->getEncryptedCookie('foo'));
        $this->assertEquals(1, preg_match("@foo=;.*@", $response['Set-Cookie']));
    }

    /**
     * Test get encrypted cookie WITHOUT deleting it
     *
     * This only tests that this method runs without error. The implementation of
     * fetching the encrypted cookie is tested separately.
     */
    public function testGetEncryptedCookieWithoutDeletingIt()
    {
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/foo', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
        ));
        $request = new \Slim\Http\Request(\Slim\Environment::getInstance());
        $response = new \Slim\Http\Response();
        $c = new \Slim\Http\Cookies($request, $response, $this->settings);
        $this->assertFalse($c->getEncryptedCookie('foo', false));
        $this->assertEquals(0, preg_match("@foo=;.*@", $response['Set-Cookie']));
    }
}
