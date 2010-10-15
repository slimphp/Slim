<?php
/**
 * Slim
 *
 * A simple PHP framework for PHP 5 or newer
 *
 * @author		Josh Lockhart <info@joshlockhart.com>
 * @link		http://slim.joshlockhart.com
 * @copyright	2010 Josh Lockhart
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

require_once '../slim/Request.php';
require_once 'PHPUnit/Framework.php';

class RequestTest extends PHPUnit_Framework_TestCase {

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
	 * Test request Root is set when not in subdirectory
	 *
	 * Pre-conditions:
	 * The HTTP request URI is /foo/bar/. The mock HTTP request simulates
	 * a scenario where the Slim app resides in the subdirectory /foo/bar/.
	 *
	 * Post-conditions:
	 * The Request root should be "/foo/bar/"
	 */
	public function testRequestRootWithSubdirectory(){
		$r = new Request();
		$this->assertEquals($r->root, '/foo/bar/');
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
	public function testRequestRootWithoutSubdirectory(){
		$_SERVER['PHP_SELF'] = '/bootstrap.php';
		$r = new Request();
		$this->assertEquals($r->root, '/');
	}

    /**
     * Test isAjax is set to true, when HTTP_X_REQUESTED_WITH is set to
     * 'XMLHttpRequest'.
     *
     * Pre-conditions:
     * Case A: HTTP_X_REQUESTED_WITH is set to XMLHttpRequest.
     * Case B: HTTP_X_REQUESTED_WITH is not set to XMLHttpRequest.
     * Case C: HTTP_X_REQUESTED_WITH is not set.
     * 
     * Post-conditions:
     * Case A: Request::isAjax should be true.
     * Case B: Request::isAjax should be false.
     * Case C: Request::isAjax should be false.
     */
    public function testIsAjaxSet(){
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $r = new Request();
        $this->assertTrue($r->isAjax);

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'foo';
        $r = new Request();
        $this->assertFalse($r->isAjax);

        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        $r = new Request();
        $this->assertFalse($r->isAjax);
    }

}

?>
