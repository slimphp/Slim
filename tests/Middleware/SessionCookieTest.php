<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.6.0
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

set_include_path(dirname(__FILE__) . '/../' . PATH_SEPARATOR . get_include_path());

require_once 'Slim/Middleware/Interface.php';
require_once 'Slim/Http/Util.php';
require_once 'Slim/Http/Request.php';
require_once 'Slim/Http/Response.php';
require_once 'Slim/Http/Headers.php';
require_once 'Slim/Middleware/SessionCookie.php';

class CustomAppSet {
    function call( &$env ) {
        $_SESSION['foo'] = 'bar';
        return array(200, array(), 'Test');
    }
}

class CustomAppGet {
    function call( &$env ) {
        if ( isset($_SESSION['foo']) ) {
            return array(200, array(), $_SESSION['foo']);
        } else {
            return array(200, array(), 'Not Set');
        }
    }
}

class SessionCookieTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $_SESSION = array();
    }

    /**
     * Test session cookie is set and constructed correctly
     *
     * We test for two things:
     * 1) That the HTTP cookie is added to the `Set-Cookie:` response header;
     * 2) That the HTTP cookie is constructed in the expected format;
     */
    public function testSessionCookieIsCreatedAndEncrypted() {
        $env = array(
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
        );
        $app = new CustomAppSet();
        $mw = new Slim_Middleware_SessionCookie($app, array(
            'expires' => '10 years'
        ));
        list($status, $header, $body) = $mw->call($env);
        $matches = array();
        preg_match_all('@^slim_session=.+|.+|.+; expires=@', $header['Set-Cookie'], $matches, PREG_SET_ORDER);
        $this->assertEquals(1, count($matches));
    }

    /**
     * Test $_SESSION is populated from HTTP cookie
     *
     * The HTTP cookie in this test was created using the previous test; the encrypted cookie contains
     * the serialized array ['foo' => 'bar']. The middleware secret, cipher, and cipher mode are assumed
     * to be the default values.
     */
    public function testSessionIsPopulatedFromCookie() {
        $env = array(
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'COOKIE' => 'slim_session=1644004961%7CLKkYPwqKIMvBK7MWl6D%2BxeuhLuMaW4quN%2F512ZAaVIY%3D%7Ce0f007fa852c7101e8224bb529e26be4d0dfbd63',
        );
        $app = new CustomAppGet();
        $mw = new Slim_Middleware_SessionCookie($app);
        list($status, $header, $body) = $mw->call($env);
        $this->assertEquals(array('foo' => 'bar'), $_SESSION);
    }

    /**
     * Test $_SESSION is populated as empty array if no HTTP cookie
     */
    public function testSessionIsPopulatedAsEmptyIfNoCookie() {
        $env = array(
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
        );
        $app = new CustomAppGet();
        $mw = new Slim_Middleware_SessionCookie($app);
        list($status, $header, $body) = $mw->call($env);
        $this->assertEquals(array(), $_SESSION);
    }
}
