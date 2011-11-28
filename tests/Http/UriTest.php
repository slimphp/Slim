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

class UriTest extends PHPUnit_Framework_TestCase {

    public function setUp(){
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
        $_SERVER['SCRIPT_FILENAME'] = '/home/slimTest/public/bootstrap.php';
        $_SERVER['REMOTE_PORT'] = "55426";
        $_SERVER['GATEWAY_INTERFACE'] = "CGI/1.1";
        $_SERVER['SERVER_PROTOCOL'] = "HTTP/1.1";
        $_SERVER['REQUEST_METHOD'] = "GET";
        $_SERVER['QUERY_STRING'] = "";
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SCRIPT_NAME'] = '/bootstrap.php';
        $_SERVER['PHP_SELF'] = '/bootstrap.php';
        $_SERVER['REQUEST_TIME'] = "1285647051";
        $_SERVER['argv'] = array();
        $_SERVER['argc'] = 0;
    }

    public function testUris() {
        //BASE ROUTE IN ROOT DIRECTORY WITH HTACCESS
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SCRIPT_NAME'] = '/bootstrap.php';
        $this->assertEquals('', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/', Slim_Http_Uri::getUri(true));

        //BASE ROUTE IN ROOT DIRECTORY WITHOUT HTACCESS
        $_SERVER['REQUEST_URI'] = '/bootstrap.php';
        $_SERVER['SCRIPT_NAME'] = '/bootstrap.php';
        $this->assertEquals('/bootstrap.php', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/', Slim_Http_Uri::getUri(true));

        $_SERVER['REQUEST_URI'] = '/bootstrap.php/';
        $_SERVER['SCRIPT_NAME'] = '/bootstrap.php';
        $this->assertEquals('/bootstrap.php', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/', Slim_Http_Uri::getUri(true));

        //BASE ROUTE IN A SUBDIRECTORY WITH HTACCESS
        $_SERVER['REQUEST_URI'] = '/foo';
        $_SERVER['SCRIPT_NAME'] = '/foo/bootstrap.php';
        $this->assertEquals('/foo', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/', Slim_Http_Uri::getUri(true));

        $_SERVER['REQUEST_URI'] = '/foo/';
        $_SERVER['SCRIPT_NAME'] = '/foo/bootstrap.php';
        $this->assertEquals('/foo', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/', Slim_Http_Uri::getUri(true));

        //BASE ROUTE IN A SUBDIRECTORY WITHOUT HTACCESS
        $_SERVER['REQUEST_URI'] = '/foo/bootstrap.php';
        $_SERVER['SCRIPT_NAME'] = '/foo/bootstrap.php';
        $this->assertEquals('/foo/bootstrap.php', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/', Slim_Http_Uri::getUri(true));

        $_SERVER['REQUEST_URI'] = '/foo/bootstrap.php/';
        $_SERVER['SCRIPT_NAME'] = '/foo/bootstrap.php';
        $this->assertEquals('/foo/bootstrap.php', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/', Slim_Http_Uri::getUri(true));

        //EXTENDED ROUTE IN ROOT DIRECTORY WITH HTACCESS
        $_SERVER['REQUEST_URI'] = '/foo/bar';
        $_SERVER['SCRIPT_NAME'] = '/bootstrap.php';
        $this->assertEquals('', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/foo/bar', Slim_Http_Uri::getUri(true));

        $_SERVER['REQUEST_URI'] = '/foo/bar/';
        $_SERVER['SCRIPT_NAME'] = '/bootstrap.php';
        $this->assertEquals('', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/foo/bar/', Slim_Http_Uri::getUri(true));

        //EXTENDED ROUTE IN ROOT DIRECTORY WITOUT HTACCESS
        $_SERVER['REQUEST_URI'] = '/bootstrap.php/foo/bar';
        $_SERVER['SCRIPT_NAME'] = '/bootstrap.php';
        $this->assertEquals('/bootstrap.php', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/foo/bar', Slim_Http_Uri::getUri(true));

        $_SERVER['REQUEST_URI'] = '/bootstrap.php/foo/bar/';
        $_SERVER['SCRIPT_NAME'] = '/bootstrap.php';
        $this->assertEquals('/bootstrap.php', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/foo/bar/', Slim_Http_Uri::getUri(true));

        //EXTENDED ROUTE IN SUBDIRECTORY WITH HTACCESS
        $_SERVER['REQUEST_URI'] = '/foo/one/two';
        $_SERVER['SCRIPT_NAME'] = '/foo/bootstrap.php';
        $this->assertEquals('/foo', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/one/two', Slim_Http_Uri::getUri(true));

        $_SERVER['REQUEST_URI'] = '/foo/one/two/';
        $_SERVER['SCRIPT_NAME'] = '/foo/bootstrap.php';
        $this->assertEquals('/foo', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/one/two/', Slim_Http_Uri::getUri(true));

        //EXTENDED ROUTE IN SUBDIRECTORY WITOUT HTACCESS
        $_SERVER['REQUEST_URI'] = '/foo/bootstrap.php/one/two';
        $_SERVER['SCRIPT_NAME'] = '/foo/bootstrap.php';
        $this->assertEquals('/foo/bootstrap.php', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/one/two', Slim_Http_Uri::getUri(true));

        $_SERVER['REQUEST_URI'] = '/foo/bootstrap.php/one/two/';
        $_SERVER['SCRIPT_NAME'] = '/foo/bootstrap.php';
        $this->assertEquals('/foo/bootstrap.php', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/one/two/', Slim_Http_Uri::getUri(true));
    }

    /**
     * Test URIs if SERVER[REQUEST_URI] is not available (ie. IIS)
     */
    public function testUrisIfNoRequestUri() {
        unset($_SERVER['REQUEST_URI']);
        //With htaccess
        $_SERVER['PHP_SELF'] = '/foo/one/two';
        $_SERVER['SCRIPT_NAME'] = '/foo/bootstrap.php';
        $this->assertEquals('/foo', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/one/two', Slim_Http_Uri::getUri(true));
        //Without htaccess
        $_SERVER['PHP_SELF'] = '/foo/bootstrap.php/one/two';
        $_SERVER['SCRIPT_NAME'] = '/foo/bootstrap.php';
        $this->assertEquals('/foo/bootstrap.php', Slim_Http_Uri::getBaseUri(true));
        $this->assertEquals('/one/two', Slim_Http_Uri::getUri(true));
    }

    /**
     * Test URI if PATH_INFO
     */
    public function testUriWithPathInfo() {
        $_SERVER['PATH_INFO'] = '/foo/bar';
        $this->assertEquals('/foo/bar', Slim_Http_Uri::getUri(true));
    }

    /**
     * Test URI if no information source available
     */
    public function testUriWithoutDataSource() {
        unset($_SERVER['PATH_INFO']);
        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['PHP_SELF']);
        try {
            $uri = Slim_Http_Uri::getUri(true);
            $this->fail('Did not catch RuntimeException when determining URI without a data source');
        } catch( RuntimeException $e ) {}
    }

    /**
     * Test query string
     */
    public function testQueryString() {
        $_SERVER['QUERY_STRING'] = 'foo=bar&one=1';
        $this->assertEquals('foo=bar&one=1', Slim_Http_Uri::getQueryString());
    }
}
