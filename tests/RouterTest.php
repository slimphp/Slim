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

class RouterTest extends PHPUnit_Framework_TestCase
{
    protected $env;
    protected $req;
    protected $res;

    public function setUp()
    {
        \Slim\Environment::mock(array(
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
        $this->env = \Slim\Environment::getInstance();
        $this->req = new \Slim\Http\Request($this->env);
        $this->res = new \Slim\Http\Response();
    }

    /**
     * Router::urlFor should return a full route pattern
     * even if no params data is provided.
     */
    public function testUrlForNamedRouteWithoutParams()
    {
        $router = new \Slim\Router();
        $route = new \Slim\Route('/foo/bar', function () {});
        $router->addRoute($route->via('GET'));
        $router->addNamedRoute('foo', $route);
        $this->assertEquals('/foo/bar', $router->urlFor('foo'));
    }

    /**
     * Router::urlFor should return a full route pattern if
     * param data is provided.
     */
    public function testUrlForNamedRouteWithParams()
    {
        $router = new \Slim\Router();
        $route = new \Slim\Route('/foo/:one/and/:two', function ($one, $two) {});
        $router->addRoute($route->via('GET'));
        $router->addNamedRoute('foo', $route);
        $this->assertEquals('/foo/Josh/and/John', $router->urlFor('foo', array('one' => 'Josh', 'two' => 'John')));
    }

    /**
     * Router::urlFor should throw an exception if Route with name
     * does not exist.
     * @expectedException \RuntimeException
     */
    public function testUrlForNamedRouteThatDoesNotExist()
    {
        $router = new \Slim\Router();
        $route = new \Slim\Route('/foo/bar', function () {});
        $router->addRoute($route->via('GET'));
        $router->addNamedRoute('bar', $route);
        $router->urlFor('foo');
    }

    /**
     * Router::addNamedRoute should throw an exception if named Route
     * with same name already exists.
     */
    public function testNamedRouteWithExistingName()
    {
        $this->setExpectedException('\RuntimeException');
        $router = new \Slim\Router();
        $route1 = new \Slim\Route('/foo/bar', function () {});
        $router->addRoute($route1->via('GET'));
        $route2 = new \Slim\Route('/foo/bar/2', function () {});
        $router->addRoute($route2->via('GET'));
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
    public function testHasNamedRoute()
    {
        $router = new \Slim\Router();
        $route = new \Slim\Route('/foo', function () {});
        $router->addRoute($route->via('GET'));
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
    public function testGetNamedRoute()
    {
        $router = new \Slim\Router();
        $route1 = new \Slim\Route('/foo', function () {});
        $router->addRoute($route1->via('GET'));
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
    public function testGetNamedRoutes()
    {
        $router = new \Slim\Router();
        $route1 = new \Slim\Route('/foo', function () {});
        $router->addRoute($route1->via('GET'));
        $route2 = new \Slim\Route('/bar', function () {});
        $router->addRoute($route2->via('POST'));
        $router->addNamedRoute('foo', $route1);
        $router->addNamedRoute('bar', $route2);
        $namedRoutesIterator = $router->getNamedRoutes();
        $this->assertInstanceOf('ArrayIterator', $namedRoutesIterator);
        $this->assertEquals(2, $namedRoutesIterator->count());
    }

    /**
     * Router should keep reference to a callable NotFound callback
     */
    public function testNotFoundHandler()
    {
        $router = new \Slim\Router();
        $notFoundCallback = function () { echo "404"; };
        $callback = $router->notFound($notFoundCallback);
        $this->assertSame($notFoundCallback, $callback);
    }

    /**
     * Router should NOT keep reference to a callback that is not callable
     */
    public function testNotFoundHandlerIfNotCallable()
    {
        $router = new \Slim\Router();
        $notFoundCallback = 'foo';
        $callback = $router->notFound($notFoundCallback);
        $this->assertNull($callback);
    }

    /**
     * Router should keep reference to a callable NotFound callback
     */
    public function testErrorHandler()
    {
        $router = new \Slim\Router();
        $errCallback = function () { echo "404"; };
        $callback = $router->error($errCallback);
        $this->assertSame($errCallback, $callback);
    }

    /**
     * Router should NOT keep reference to a callback that is not callable
     */
    public function testErrorHandlerIfNotCallable()
    {
        $router = new \Slim\Router();
        $errCallback = 'foo';
        $callback = $router->error($errCallback);
        $this->assertNull($callback);
    }

    /**
     * Router::urlFor
     */
    public function testRouterUrlFor()
    {
        $router = new \Slim\Router();
        $route1 = new \Slim\Route('/foo/bar', function () {});
        $router->addRoute($route1->via('GET'));
        $route2 = new \Slim\Route('/foo/:one/:two', function () {});
        $router->addRoute($route2->via('GET'));
        $route3 = new \Slim\Route('/foo/:one(/:two)', function () {});
        $router->addRoute($route3->via('GET'));
        $route4 = new \Slim\Route('/foo/:one/(:two/)', function () {});
        $router->addRoute($route4->via('GET'));
        $route5 = new \Slim\Route('/foo/:one/(:two/(:three/))', function () {});
        $router->addRoute($route5->via('GET'));
        $route6 = new \Slim\Route('/foo/:path+/bar', function (){});
        $router->addRoute($route6->via('GET'));
        $route7 = new \Slim\Route('/foo/:path+/:path2+/bar', function (){});
        $router->addRoute($route7->via('GET'));
        $route8 = new \Slim\Route('/foo/:path+', function (){});
        $router->addRoute($route8->via('GET'));
        $route9 = new \Slim\Route('/foo/:var/:var2', function (){});
        $router->addRoute($route9->via('GET'));
        $route1->setName('route1');
        $route2->setName('route2');
        $route3->setName('route3');
        $route4->setName('route4');
        $route5->setName('route5');
        $route6->setName('route6');
        $route7->setName('route7');
        $route8->setName('route8');
        $route9->setName('route9');
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
        //Route with wildcard params
        $this->assertEquals('/foo/bar/bar', $router->urlFor('route6', array('path'=>'bar')));
        $this->assertEquals('/foo/foo/bar/bar', $router->urlFor('route7', array('path'=>'foo', 'path2'=>'bar')));
        $this->assertEquals('/foo/bar', $router->urlFor('route8', array('path'=>'bar')));
        //Route with similar param names, test greedy matching
        $this->assertEquals('/foo/1/2', $router->urlFor('route9', array('var2'=>'2', 'var'=>'1')));
        $this->assertEquals('/foo/1/2', $router->urlFor('route9', array('var'=>'1', 'var2'=>'2')));
    }

    /**
     * Test that router returns no matches when neither HTTP method nor URI match.
     */
    public function testRouterMatchesRoutesNone()
    {
        \Slim\Environment::mock(array(
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
        $this->env = \Slim\Environment::getInstance();
        $this->req = new \Slim\Http\Request($this->env);
        $this->res = new \Slim\Http\Response();
        $router = new \Slim\Router();
        $route1 = new \Slim\Route('/bar', function () {});
        $router->addRoute($route1->via('POST'));
        $route2 = new \Slim\Route('/foo', function () {});
        $router->addRoute($route2->via('POST'));
        $route3 = new \Slim\Route('/foo', function () {});
        $router->addRoute($route3->via('PUT'));
        $route4 = new \Slim\Route('/foo/bar/xyz', function () {});
        $router->addRoute($route4->via('DELETE'));
        $this->assertEquals(null, count($router->getMatchedRoute($this->req->getMethod(), $this->req->getResourceUri())));
    }

    /**
     * Test that router returns no matches when HTTP method matches but URI does not.
     */
    public function testRouterMatchesRoutesNoneWhenMethodMatchesUriDoesNot()
    {
        \Slim\Environment::mock(array(
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
        $this->env = \Slim\Environment::getInstance();
        $this->req = new \Slim\Http\Request($this->env);
        $this->res = new \Slim\Http\Response();
        $router = new \Slim\Router();
        $route1 = new \Slim\Route('/fooNOMATCH', function () {});
        $router->addRoute($route1->via('GET'));
        $route2 = new \Slim\Route('/foo', function () {});
        $router->addRoute($route2->via('POST'));
        $route3 = new \Slim\Route('/foo', function () {});
        $router->addRoute($route3->via('PUT'));
        $route4 = new \Slim\Route('/foo/bar/xyz', function () {});
        $router->addRoute($route4->via('DELETE'));
        $this->assertEquals(null, $router->getMatchedRoute($this->req->getMethod(), $this->req->getResourceUri()));
    }

    /**
     * Test that router returns no matches when HTTP method does not match but URI does.
     */
    public function testRouterMatchesRoutesNoneWhenMethodDoesNotMatchUriMatches()
    {
        \Slim\Environment::mock(array(
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
        $this->env = \Slim\Environment::getInstance();
        $this->req = new \Slim\Http\Request($this->env);
        $this->res = new \Slim\Http\Response();
        $router = new \Slim\Router();
        $route1 = new \Slim\Route('/foo', function () {});
        $router->addRoute($route1->via('OPTIONS'));
        $route2 = new \Slim\Route('/foo', function () {});
        $router->addRoute($route2->via('POST'));
        $route3 = new \Slim\Route('/foo', function () {});
        $router->addRoute($route3->via('PUT'));
        $route4 = new \Slim\Route('/foo/bar/xyz', function () {});
        $route4->via('DELETE');
        $this->assertEquals(null, $router->getMatchedRoute($this->req->getMethod(), $this->req->getResourceUri()));
    }

    /**
     * Test that router returns matched routes based on HTTP method and URI.
     */
    public function testRouterMatchesRoutes()
    {
        \Slim\Environment::mock(array(
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
        $this->env = \Slim\Environment::getInstance();
        $this->req = new \Slim\Http\Request($this->env);
        $this->res = new \Slim\Http\Response();
        $router = new \Slim\Router();
        $route = new \Slim\Route('/foo', function () {});
        $router->addRoute($route->via('GET'));
        $route1 = new \Slim\Route('/foo', function () {});
        $router->addRoute($route1->via('POST'));
        $route2 = new \Slim\Route('/foo', function () {});
        $router->addRoute($route2->via('PUT'));
        $route3 = new \Slim\Route('/foo/bar/xyz', function () {});
        $route3->via('DELETE');
        $this->assertEquals($route, $router->getMatchedRoute($this->req->getMethod(), $this->req->getResourceUri()));
    }

    /**
     * Test get current route
     */
    public function testGetCurrentRoute()
    {
        \Slim\Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '', //<-- Physical
            'PATH_INFO' => '/foo' //<-- Virtual
        ));
        $app = new \Slim\Slim();
        $route1 = $app->get('/bar', function () {
            echo "Bar";
        });
        $route2 = $app->get('/foo', function () {
            echo "Foo";
        });
        $app->call();
        $this->assertSame($route2, $app->router()->getCurrentRoute());
    }

    /**
     * Test calling get current route before routing doesn't cause errors
     */
    public function testGetCurrentRouteBeforeRoutingDoesntError()
    {
        \Slim\Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '', //<-- Physical
            'PATH_INFO' => '/foo' //<-- Virtual
        ));
        $app = new \Slim\Slim();
        $route1 = $app->get('/bar', function () {
            echo "Bar";
        });
        $route2 = $app->get('/foo', function () {
            echo "Foo";
        });

        $app->router()->getCurrentRoute();

        $app->call();
    }

    /**
     * Test get current route before routing returns null
     */
    public function testGetCurrentRouteBeforeRoutingReturnsNull()
    {
        \Slim\Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '', //<-- Physical
            'PATH_INFO' => '/foo' //<-- Virtual
        ));
        $app = new \Slim\Slim();
        $route1 = $app->get('/bar', function () {
            echo "Bar";
        });
        $route2 = $app->get('/foo', function () {
            echo "Foo";
        });

        $this->assertSame(null, $app->router()->getCurrentRoute());
    }

    /**
     * Test get current route during routing
     */
    public function testGetCurrentRouteDuringRouting()
    {
        $route = null;

        \Slim\Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '', //<-- Physical
            'PATH_INFO' => '/foo' //<-- Virtual
        ));
        $app = new \Slim\Slim();
        $route1 = $app->get('/bar', function () {
            echo "Bar";
        });
        $route2 = $app->get('/foo', function () use (&$route, $app) {
            echo "Foo";
            $route = $app->router()->getCurrentRoute();
        });

        $app->call();
        $this->assertSame($route2, $route);
    }

    /**
     * Test get current route after routing
     */
    public function testGetCurrentRouteAfterRouting()
    {
        \Slim\Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '', //<-- Physical
            'PATH_INFO' => '/foo' //<-- Virtual
        ));
        $app = new \Slim\Slim();
        $route1 = $app->get('/bar', function () {
            echo "Bar";
        });
        $route2 = $app->get('/foo', function () {
            echo "Foo";
        });
        $app->call();
        $this->assertSame($route2, $app->router()->getCurrentRoute());
    }
}
