<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@joshlockhart.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.5.0
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

set_include_path(dirname(__FILE__) . '/../../' . PATH_SEPARATOR . get_include_path());

require_once 'Slim/Http/Uri.php';
require_once 'Slim/Http/Request.php';

class RequestTest extends PHPUnit_Framework_TestCase {

    public function setUp(){
        ini_set('magic_quotes_gpc', 1);
        $_SERVER['REDIRECT_STATUS'] = "200";
        $_SERVER['HTTP_HOST'] = "slim";
        $_SERVER['HTTP_CONNECTION'] = "keep-alive";
        $_SERVER['HTTP_CACHE_CONTROL'] = "max-age=0";
        $_SERVER['HTTP_ACCEPT'] = "application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
        $_SERVER['HTTP_USER_AGENT'] = "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_4; en-US) AppleWebKit/534.3 (KHTML, like Gecko) Chrome/6.0.472.63 Safari/534.3";
        $_SERVER['HTTP_ACCEPT_ENCODING'] = "gzip,deflate,sdch";
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = "en-US,en;q=0.8";
        $_SERVER['HTTP_ACCEPT_CHARSET'] = "ISO-8859-1,utf-8;q=0.7,*;q=0.3";
        $_SERVER['PATH'] = "/usr/bin:/bin:/usr/sbin:/sbin";
        $_SERVER['SERVER_SIGNATURE'] = "";
        $_SERVER['SERVER_SOFTWARE'] = "Apache";
        $_SERVER['SERVER_NAME'] = "slim";
        $_SERVER['SERVER_ADDR'] = "127.0.0.1";
        $_SERVER['SERVER_PORT'] = "80";
        $_SERVER['REMOTE_ADDR'] = "127.0.0.1";
        $_SERVER['DOCUMENT_ROOT'] = '/home/slimTest/public';
        $_SERVER['SERVER_ADMIN'] = "you@example.com";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;
        $_SERVER['REMOTE_PORT'] = "55426";
        $_SERVER['GATEWAY_INTERFACE'] = "CGI/1.1";
        $_SERVER['SERVER_PROTOCOL'] = "HTTP/1.1";
        $_SERVER['REQUEST_METHOD'] = "GET";
        $_SERVER['QUERY_STRING'] = "";
        $_SERVER['REQUEST_URI'] = '/foo/bar/';
        $_SERVER['SCRIPT_NAME'] = basename(__FILE__);
        $_SERVER['PHP_SELF'] = '/foo/bar/bootstrap.php';
        $_SERVER['REQUEST_TIME'] = "1285647051";
        $_SERVER['argv'] = array();
        $_SERVER['argc'] = 0;
    }

    /**
     * Test request Root is set when in subdirectory
     *
     * Pre-conditions:
     * The HTTP request URI is /foo/bar/. The mock HTTP request simulates
     * a scenario where the Slim app resides in the base document root directory.
     *
     * Post-conditions:
     * The Request root should be "/"
     */
    public function testRequestUriInRootDirectory(){
        $_SERVER['REQUEST_URI'] = '/foo/bar/';
        $_SERVER['SCRIPT_NAME'] = '/bootstrap.php';
        $r = new Slim_Http_Request();
        $this->assertEquals('', $r->getRootUri());
        $this->assertEquals('/foo/bar/', $r->getResourceUri());
    }

    /**
     * Test request Root is set when not in subdirectory
     *
     * Pre-conditions:
     * The HTTP request URI is /foo/bar/. The mock HTTP request simulates
     * a scenario where the Slim app resides in the subdirectory /foo/bar/.
     *
     * Post-conditions:
     * The Request root should be "/foo/bar"
     */
    public function testRequestUriInSubDirectory() {
        $_SERVER['REQUEST_URI'] = '/foo/bar/?foo=bar';
        $_SERVER['SCRIPT_NAME'] = '/foo/boostrap.php';
        $r = new Slim_Http_Request();
        $this->assertEquals('/foo', $r->getRootUri());
        $this->assertEquals('/bar/', $r->getResourceUri());
    }

    /**
     * Test request URI without htaccess
     *
     * Pre-conditions:
     * The HTTP request URI is /index.php/foo/bar/. The mock HTTP request simulates
     * a scenario where the Slim app resides in the base document root directory
     * without htaccess URL rewriting.
     *
     * Post-conditions:
     * The Request root should be "/index.php" and the resource "/foo/bar"
     */
    public function testRequestUriInRootDirectoryWitoutHtaccess(){
        $_SERVER['REQUEST_URI'] = '/bootstrap.php/foo/bar/';
        $_SERVER['SCRIPT_NAME'] = '/bootstrap.php';
        $r = new Slim_Http_Request();
        $this->assertEquals('/bootstrap.php', $r->getRootUri());
        $this->assertEquals('/foo/bar/', $r->getResourceUri());
    }

    /**
     * Test request URI without htaccess
     *
     * Pre-conditions:
     * The HTTP request URI is /foo/index.php/foo/bar/. The mock HTTP request simulates
     * a scenario where the Slim app resides in a subdirectory of the document root directory
     * without htaccess URL rewriting.
     *
     * Post-conditions:
     * The Request root should be "/foo/index.php" and the resource "/foo/bar"
     */
    public function testRequestUriInSubDirectoryWitoutHtaccess(){
        $_SERVER['REQUEST_URI'] = '/foo/bootstrap.php/foo/bar/';
        $_SERVER['SCRIPT_NAME'] = '/foo/bootstrap.php';
        $r = new Slim_Http_Request();
        $this->assertEquals('/foo/bootstrap.php', $r->getRootUri());
        $this->assertEquals('/foo/bar/', $r->getResourceUri());
    }

    /* TEST STRIP SLASHES */

    public function testStripSlashesIfMagicQuotes() {
        $_GET['foo1'] = "bar\'d";
        $getData = Slim_Http_Request::stripSlashesIfMagicQuotes($_GET);
        if ( get_magic_quotes_gpc() ) {
            $this->assertEquals("bar'd", $getData['foo1']);
        } else {
            $this->assertEquals("bar\'d", $getData['foo1']);
        }
    }

    /* TEST REQUEST METHODS */

    public function testIsGet() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $r = new Slim_Http_Request();
        $this->assertTrue($r->isGet());
    }

    public function testIsPost() {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $r = new Slim_Http_Request();
        $this->assertTrue($r->isPost());
    }

    public function testIsPut() {
        //Case A
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $r = new Slim_Http_Request();
        $this->assertTrue($r->isPut());
        //Case B
        $_POST['_METHOD'] = 'PUT';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $r = new Slim_Http_Request();
        $this->assertTrue($r->isPut());
    }

    public function testIsDelete() {
        //Case A
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $r = new Slim_Http_Request();
        $this->assertTrue($r->isDelete());
        //Case B
        $_POST['_METHOD'] = 'DELETE';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $r = new Slim_Http_Request();
        $this->assertTrue($r->isDelete());
    }

    public function testIsHead() {
        $_SERVER['REQUEST_METHOD'] = 'HEAD';
        $r = new Slim_Http_Request();
        $this->assertTrue($r->isHead());
    }

    public function testIsOptions() {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $r = new Slim_Http_Request();
        $this->assertTrue($r->isOptions());
    }

    public function testIsAjax() {
        //Case A
        $_SERVER['X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $r = new Slim_Http_Request();
        $this->assertTrue($r->isAjax());
        //Case B
        $_SERVER['X_REQUESTED_WITH'] = 'foo';
        $r = new Slim_Http_Request();
        $this->assertFalse($r->isAjax());
        //Case C
        unset($_SERVER['X_REQUESTED_WITH']);
        $r = new Slim_Http_Request();
        $this->assertFalse($r->isAjax());
        //Case D
        unset($_SERVER['X_REQUESTED_WITH']);
        $_GET['isajax'] = 1;
        $r = new Slim_Http_Request();
        $this->assertTrue($r->isAjax());
    }

    /* TEST REQUEST PARAMS */

    public function testParams() {
        //Case A: PUT params
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array(
            '_METHOD' => 'PUT',
            'foo1' => 'bar1'
        );
        $r = new Slim_Http_Request();
        $this->assertEquals('bar1', $r->params('foo1'));
        $this->assertEquals('bar1', $r->put('foo1'));
        $this->assertEquals(array('foo1' => 'bar1'), $r->put());

        //Case B: POST params
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array('foo1' => 'bar1');
        $r = new Slim_Http_Request();
        $this->assertEquals('bar1', $r->params('foo1'));
        $this->assertEquals('bar1', $r->post('foo1'));
        $this->assertEquals($_POST, $r->post());

        //Case C: GET params
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = array();
        $_GET = array('foo1' => 'bar1');
        $r = new Slim_Http_Request();
        $this->assertEquals('bar1', $r->params('foo1'));
        $this->assertEquals('bar1', $r->get('foo1'));
        $this->assertEquals($_GET, $r->get());

        //Case D: COOKIE params
        $_COOKIE['foo'] = 'bar';
        $r = new Slim_Http_Request();
        $this->assertEquals($_COOKIE, $r->cookies());
        $this->assertEquals('bar', $r->cookies('foo'));

        //Case E: NULL params
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = array();
        $_POST = array();
        $r = new Slim_Http_Request();
        $this->assertNull($r->params('foo1'));
        $this->assertNull($r->put('foo1'));
        $this->assertNull($r->post('foo1'));
        $this->assertNull($r->get('foo1'));
        $this->assertNull($r->cookies('foo1'));
    }

    /* TEST HEADERS */

    public function testHeaders() {
        //Case A
        $_SERVER['X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $r = new Slim_Http_Request();
        $this->assertEquals('slim', $r->headers('HOST'));
        $this->assertEquals('XMLHttpRequest', $r->headers('X_REQUESTED_WITH'));
        $this->assertTrue(is_array($r->headers()));
        //Case B - HTTP headers may be case insensitive
        $_SERVER['x-requested-with'] = 'XMLHttpRequest';
        $r = new Slim_Http_Request();
        $this->assertEquals('XMLHttpRequest', $r->headers('X_REQUESTED_WITH'));
        //Case C - HTTP headers may be case insensitive
        $_SERVER['X-Requested-With'] = 'XMLHttpRequest';
        $r = new Slim_Http_Request();
        $this->assertEquals('XMLHttpRequest', $r->headers('X_REQUESTED_WITH'));
    }

    /* MISC TESTS */

    public function testGetMethod() {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $r = new Slim_Http_Request();
        $this->assertEquals($_SERVER['REQUEST_METHOD'], $r->getMethod());
    }

    public function testGetContentType() {
        //Case A
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $r1 = new Slim_Http_Request();
        $this->assertEquals($_SERVER['CONTENT_TYPE'], $r1->getContentType());
        //Case B
        unset($_SERVER['CONTENT_TYPE']);
        $r2 = new Slim_Http_Request();
        $this->assertEquals('application/x-www-form-urlencoded', $r2->getContentType());
        //Case C
        $_SERVER['CONTENT_TYPE'] = 'text/html; charset=ISO-8859-4';
        $r3 = new Slim_Http_Request();
        $this->assertEquals('text/html', $r3->getContentType());
    }
}