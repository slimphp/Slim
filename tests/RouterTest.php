<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.5.2
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

require_once 'Slim/Environment.php';
require_once 'Slim/Http/Headers.php';
require_once 'Slim/Http/Request.php';
require_once 'Slim/Http/Response.php';
require_once 'Slim/Router.php';
require_once 'Slim/Route.php';

class RouterTest extends PHPUnit_Framework_TestCase {

    protected $env;
    protected $req;
    protected $res;

    public function setUp() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'HTTP_HOST' => 'slim'
        ));
        $this->env = Slim_Environment::getInstance();
        $this->req = new Slim_Http_Request($this->env);
        $this->res = new Slim_Http_Response();
    }

    /**
     * Test sets and gets Request and Response
     */
    public function testRouterRequestAndResponse() {
        $router = new Slim_Router($this->req, $this->res);
        $this->assertSame($this->req, $router->getRequest());
        $this->assertSame($this->res, $router->getResponse());
    }

    /**
     * Router::urlFor should return a full route pattern
     * even if no params data is provided.
     */
    public function testUrlForNamedRouteWithoutParams() {
        $router = new Slim_Router($this->req, $this->res);
        $route = $router->map('/foo/bar', function () {})->via('GET');
        $router->addNamedRoute('foo', $route);
        $this->assertEquals('/foo/bar', $router->urlFor('foo'));
    }

    /**
     * Router::urlFor should return a full route pattern if
     * param data is provided.
     */
    public function testUrlForNamedRouteWithParams() {
        $router = new Slim_Router($this->req, $this->res);
        $route = $router->map('/foo/:one/and/:two', function ($one, $two) {})->via('GET');
        $router->addNamedRoute('foo', $route);
        $this->assertEquals('/foo/Josh/and/John', $router->urlFor('foo', array('one' => 'Josh', 'two' => 'John')));
    }

    /**
     * Router::urlFor should throw an exception if Route with name
     * does not exist.
     */
    public function testUrlForNamedRouteThatDoesNotExist() {
        $this->setExpectedException('RuntimeException');
        $router = new Slim_Router($this->req, $this->res);
        $route = $router->map('/foo/bar', function () {})->via('GET');
        $router->addNamedRoute('bar', $route);
        $router->urlFor('foo');
    }

    /**
     * Router::addNamedRoute should throw an exception if named Route
     * with same name already exists.
     */
    public function testNamedRouteWithExistingName() {
        $this->setExpectedException('RuntimeException');
        $router = new Slim_Router($this->req, $this->res);
        $route1 = $router->map('/foo/bar', function () {})->via('GET');
        $route2 = $router->map('/foo/bar/2', function () {})->via('GET');
        $router->addNamedRoute('bar', $route1);
        $router->addNamedRoute('bar', $route2);
    }

    /**
     * Test if named route exists
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Named route created;
     *
     * Post-conditions:
     * Named route found to exist;
     * Non-existant route found not to exist;
     */
    public function testHasNamedRoute() {
        $router = new Slim_Router($this->req, $this->res);
        $route = $router->map('/foo', function () {})->via('GET');
        $router->addNamedRoute('foo', $route);
        $this->assertTrue($router->hasNamedRoute('foo'));
        $this->assertFalse($router->hasNamedRoute('bar'));
    }

    /**
     * Test Router gets named route
     *
     * Pre-conditions;
     * Slim app instantiated;
     * Named route created;
     *
     * Post-conditions:
     * Named route fetched by named;
     * NULL is returned if named route does not exist;
     */
    public function testGetNamedRoute() {
        $router = new Slim_Router($this->req, $this->res);
        $route1 = $router->map('/foo', function () {})->via('GET');
        $router->addNamedRoute('foo', $route1);
        $this->assertSame($route1, $router->getNamedRoute('foo'));
        $this->assertNull($router->getNamedRoute('bar'));
    }

    /**
     * Test external iterator for Router's named routes
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Named routes created;
     *
     * Post-conditions:
     * Array iterator returned for named routes;
     */
    public function testGetNamedRoutes() {
        $router = new Slim_Router($this->req, $this->res);
        $route1 = $router->map('/foo', function () {})->via('GET');
        $route2 = $router->map('/bar', function () {})->via('POST');
        $router->addNamedRoute('foo', $route1);
        $router->addNamedRoute('bar', $route2);
        $namedRoutesIterator = $router->getNamedRoutes();
        $this->assertInstanceOf('ArrayIterator', $namedRoutesIterator);
        $this->assertEquals(2, $namedRoutesIterator->count());
    }

    /**
     * Router should keep reference to a callable NotFound callback
     */
    public function testNotFoundHandler() {
        $router = new Slim_Router($this->req, $this->res);
        $notFoundCallback = function () { echo "404"; };
        $callback = $router->notFound($notFoundCallback);
        $this->assertSame($notFoundCallback, $callback);
    }

    /**
     * Router should NOT keep reference to a callback that is not callable
     */
    public function testNotFoundHandlerIfNotCallable() {
        $router = new Slim_Router($this->req, $this->res);
        $notFoundCallback = 'foo';
        $callback = $router->notFound($notFoundCallback);
        $this->assertNull($callback);
    }

    /**
     * Router should keep reference to a callable NotFound callback
     */
    public function testErrorHandler() {
        $router = new Slim_Router($this->req, $this->res);
        $errCallback = function () { echo "404"; };
        $callback = $router->error($errCallback);
        $this->assertSame($errCallback, $callback);
    }

    /**
     * Router should NOT keep reference to a callback that is not callable
     */
    public function testErrorHandlerIfNotCallable() {
        $router = new Slim_Router($this->req, $this->res);
        $errCallback = 'foo';
        $callback = $router->error($errCallback);
        $this->assertNull($callback);
    }

    /**
     * Router considers HEAD requests as GET requests
     */
    public function testRouterConsidersHeadAsGet() {
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'HEAD',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'HTTP_HOST' => 'slim'
        ));
        $this->env = Slim_Environment::getInstance();
        $this->req = new Slim_Http_Request($this->env);
        $this->res = new Slim_Http_Response();
        $router = new Slim_Router($this->req, $this->res);
        $route = $router->map('/bar', function () {})->via('GET', 'HEAD');
        $numberOfMatchingRoutes = count($router->getMatchedRoutes());
        $this->assertEquals(1, $numberOfMatchingRoutes);
    }

    /**
     * Router::urlFor
     */
    public function testRouterUrlFor() {
        $router = new Slim_Router($this->req, $this->res);
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
        Slim_Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '', //<-- Physical
            'PATH_INFO' => '/foo', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w'),
            'HTTP_HOST' => 'slim'
        ));
        $this->env = Slim_Environment::getInstance();
        $this->req = new Slim_Http_Request($this->env);
        $this->res = new Slim_Http_Response();
        $router = new Slim_Router($this->req, $this->res);
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
        $router = new Slim_Router($this->req, $this->res);
        $router->map('/bar', function () {})->via('GET');
        $router->map('/foo1', function () {})->via('POST');
        $router->map('/bar', function () {})->via('PUT');
        $router->map('/foo/bar/xyz', function () {})->via('DELETE');
        $iterator = $router->getIterator();
        $this->assertInstanceOf('ArrayIterator', $iterator);
        $this->assertEquals(2, $iterator->count());
    }
}