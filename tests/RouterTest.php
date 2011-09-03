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

set_include_path(dirname(__FILE__) . '/../' . PATH_SEPARATOR . get_include_path());

require_once 'Slim/Router.php';
require_once 'Slim/Http/Uri.php';
require_once 'Slim/Http/Request.php';
require_once 'Slim/Route.php';

class RouterTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
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
        $_SERVER['DOCUMENT_ROOT'] = "/home/account/public";
        $_SERVER['SERVER_ADMIN'] = "you@example.com";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;
        $_SERVER['REMOTE_PORT'] = "55426";
        $_SERVER['REDIRECT_URL'] = "/";
        $_SERVER['GATEWAY_INTERFACE'] = "CGI/1.1";
        $_SERVER['SERVER_PROTOCOL'] = "HTTP/1.1";
        $_SERVER['REQUEST_METHOD'] = "GET";
        $_SERVER['QUERY_STRING'] = "";
        $_SERVER['REQUEST_URI'] = "/";
        $_SERVER['SCRIPT_NAME'] = "/bootstrap.php";
        $_SERVER['PHP_SELF'] = "/bootstrap.php";
        $_SERVER['REQUEST_TIME'] = "1285647051";
        $_SERVER['argv'] = array();
        $_SERVER['argc'] = 0;
    }

    /**
     * Test sets and gets Request
     */
    public function testGetsAndSetsRequest() {
        $request1 = new Slim_Http_Request();
        $request2 = new Slim_Http_Request();
        $router = new Slim_Router($request1);
        $this->assertSame($request1, $router->getRequest());
        $router->setRequest($request2);
        $this->assertSame($request2, $router->getRequest());
    }

    /**
     * Router::urlFor should return a full route pattern
     * even if no params data is provided.
     */
    public function testUrlForNamedRouteWithoutParams() {
        $request = new Slim_Http_Request();
        $router = new Slim_Router($request);
        $route = $router->map('/foo/bar', function () {})->via('GET');
        $router->cacheNamedRoute('foo', $route);
        $this->assertEquals('/foo/bar', $router->urlFor('foo'));
    }

    /**
     * Router::urlFor should return a full route pattern if
     * param data is provided.
     */
    public function testUrlForNamedRouteWithParams() {
        $request = new Slim_Http_Request();
        $router = new Slim_Router($request);
        $route = $router->map('/foo/:one/and/:two', function ($one, $two) {})->via('GET');
        $router->cacheNamedRoute('foo', $route);
        $this->assertEquals('/foo/Josh/and/John', $router->urlFor('foo', array('one' => 'Josh', 'two' => 'John')));
    }

    /**
     * Router::urlFor should throw an exception if Route with name
     * does not exist.
     */
    public function testUrlForNamedRouteThatDoesNotExist() {
        $this->setExpectedException('RuntimeException');
        $request = new Slim_Http_Request();
        $router = new Slim_Router($request);
        $route = $router->map('/foo/bar', function () {})->via('GET');
        $router->cacheNamedRoute('bar', $route);
        $router->urlFor('foo');
    }

    /**
     * Router::cacheNamedRoute should throw an exception if named Route
     * with same name already exists.
     */
    public function testNamedRouteWithExistingName() {
        $this->setExpectedException('RuntimeException');
        $request = new Slim_Http_Request();
        $router = new Slim_Router($request);
        $route1 = $router->map('/foo/bar', function () {})->via('GET');
        $route2 = $router->map('/foo/bar/2', function () {})->via('GET');
        $router->cacheNamedRoute('bar', $route1);
        $router->cacheNamedRoute('bar', $route2);
    }

    /**
     * Router should keep reference to a callable NotFound callback
     */
    public function testNotFoundHandler() {
        $request = new Slim_Http_Request();
        $router = new Slim_Router($request);
        $notFoundCallback = function () { echo "404"; };
        $callback = $router->notFound($notFoundCallback);
        $this->assertSame($notFoundCallback, $callback);
    }

    /**
     * Router should NOT keep reference to a callback that is not callable
     */
    public function testNotFoundHandlerIfNotCallable() {
        $request = new Slim_Http_Request();
        $router = new Slim_Router($request);
        $notFoundCallback = 'foo';
        $callback = $router->notFound($notFoundCallback);
        $this->assertEquals($callback, null);
    }

    /**
     * Router should keep reference to a callable NotFound callback
     */
    public function testErrorHandler() {
        $request = new Slim_Http_Request();
        $router = new Slim_Router($request);
        $errCallback = function () { echo "404"; };
        $callback = $router->error($errCallback);
        $this->assertSame($errCallback, $callback);
    }

    /**
     * Router should NOT keep reference to a callback that is not callable
     */
    public function testErrorHandlerIfNotCallable() {
        $request = new Slim_Http_Request();
        $router = new Slim_Router($request);
        $errCallback = 'foo';
        $callback = $router->error($errCallback);
        $this->assertEquals($callback, null);
    }

    /**
     * Router considers HEAD requests as GET requests
     */
    public function testRouterConsidersHeadAsGet() {
        $_SERVER['REQUEST_METHOD'] = 'HEAD';
        $router = new Slim_Router(new Slim_Http_Request());
        $route = $router->map('/', function () {})->via('GET', 'HEAD');
        $numberOfMatchingRoutes = count($router->getMatchedRoutes());
        $this->assertEquals(1, $numberOfMatchingRoutes);
    }

    /**
     * Router::urlFor
     */
    public function testRouterUrlFor() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Slim_Http_Request();
        $router = new Slim_Router($request);
        $route1 = $router->map('/foo/bar', function () {})->via('GET');
        $route2 = $router->map('/foo/:one/:two', function () {})->via('GET');
        $route3 = $router->map('/foo/:one(/:two)', function () {})->via('GET');
        $route4 = $router->map('/foo/:one/(:two/)', function () {})->via('GET');
        $route5 = $router->map('/foo/:one/(:two/(:three/))', function () {})->via('GET');
        $route1->setName('route1');
        $route2->setName('route2');
        $route3->setName('route3');
        $route4->setName('route4');
        $route5->setName('route5');
        //Route
        $this->assertEquals('/foo/bar', $router->urlFor('route1'));
        //Route with params
        $this->assertEquals('/foo/foo/bar', $router->urlFor('route2', array('one' => 'foo', 'two' => 'bar')));
        $this->assertEquals('/foo/foo/:two', $router->urlFor('route2', array('one' => 'foo')));
        $this->assertEquals('/foo/:one/bar', $router->urlFor('route2', array('two' => 'bar')));
        //Route with params and optional segments
        $this->assertEquals('/foo/foo/bar', $router->urlFor('route3', array('one' => 'foo', 'two' => 'bar')));
        $this->assertEquals('/foo/foo', $router->urlFor('route3', array('one' => 'foo')));
        $this->assertEquals('/foo/:one/bar', $router->urlFor('route3', array('two' => 'bar')));
        $this->assertEquals('/foo/:one', $router->urlFor('route3'));
        //Route with params and optional segments
        $this->assertEquals('/foo/foo/bar/', $router->urlFor('route4', array('one' => 'foo', 'two' => 'bar')));
        $this->assertEquals('/foo/foo/', $router->urlFor('route4', array('one' => 'foo')));
        $this->assertEquals('/foo/:one/bar/', $router->urlFor('route4', array('two' => 'bar')));
        $this->assertEquals('/foo/:one/', $router->urlFor('route4'));
        //Route with params and optional segments
        $this->assertEquals('/foo/foo/bar/what/', $router->urlFor('route5', array('one' => 'foo', 'two' => 'bar', 'three' => 'what')));
        $this->assertEquals('/foo/foo/', $router->urlFor('route5', array('one' => 'foo')));
        $this->assertEquals('/foo/:one/bar/', $router->urlFor('route5', array('two' => 'bar')));
        $this->assertEquals('/foo/:one/bar/what/', $router->urlFor('route5', array('two' => 'bar', 'three' => 'what')));
        $this->assertEquals('/foo/:one/', $router->urlFor('route5'));
    }

    /**
     * Test that router returns matched routes based on URI only, not
     * based on the HTTP method.
     */
    public function testRouterMatchesRoutesByUriOnly() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/foo';
        $request = new Slim_Http_Request();
        $router = new Slim_Router($request);
        $router->map('/foo', function () {})->via('GET');
        $router->map('/foo', function () {})->via('POST');
        $router->map('/foo', function () {})->via('PUT');
        $router->map('/foo/bar/xyz', function () {})->via('DELETE');
        $this->assertEquals(3, count($router->getMatchedRoutes()));
    }

    /**
     * Test that Router implements IteratorAggregate interface
     */
    public function testRouterImplementsIteratorAggregate() {
        $request = new Slim_Http_Request();
        $router = new Slim_Router($request);
        $router->map('/', function () {})->via('GET');
        $router->map('/foo1', function () {})->via('POST');
        $router->map('/', function () {})->via('PUT');
        $router->map('/foo/bar/xyz', function () {})->via('DELETE');
        $iterator = $router->getIterator();
        $this->assertInstanceOf('ArrayIterator', $iterator);
        $this->assertEquals(2, $iterator->count());
    }
}