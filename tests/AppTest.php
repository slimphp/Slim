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
use \Slim\App;
use \Slim\Collection;
use \Slim\Http\Environment;
use \Slim\Http\Uri;
use \Slim\Http\Body;
use \Slim\Http\Headers;
use \Slim\Http\Request;
use \Slim\Http\Response;

class AppTest extends PHPUnit_Framework_TestCase
{
    /********************************************************************************
     * Router proxy methods
     *******************************************************************************/

    public function testMapRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->map($path, $callable);

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertAttributeEmpty('methods', $route);
    }

    public function testGetRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->get($path, $callable);

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertAttributeContains('GET', 'methods', $route);
    }

    public function testPostRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->post($path, $callable);

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertAttributeContains('POST', 'methods', $route);
    }

    public function testPutRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->put($path, $callable);

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertAttributeContains('PUT', 'methods', $route);
    }

    public function testPatchRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->patch($path, $callable);

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertAttributeContains('PATCH', 'methods', $route);
    }

    public function testDeleteRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->delete($path, $callable);

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertAttributeContains('DELETE', 'methods', $route);
    }

    public function testOptionsRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->options($path, $callable);

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertAttributeContains('OPTIONS', 'methods', $route);
    }

    public function testAnyRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->any($path, $callable);

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertAttributeContains('ANY', 'methods', $route);
    }

    public function testGroup()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $app->group('/foo', function () use ($app) {
            $route = $app->get('/bar', function ($req, $res) {
                // Do something
            });
            $this->assertAttributeEquals('/foo/bar', 'pattern', $route);
        });
    }

    /********************************************************************************
     * Application behaviors
     *******************************************************************************/

    public function testStop()
    {
        $app = new App();
        $res = new Response();
        try {
            $app->stop($res);
            $this->fail('Did not catch exception!');
        } catch (\Slim\Exception\Stop $e) {
            $this->assertSame($res, $e->getResponse());
        }
    }

    public function testHalt()
    {
        $app = new App();
        try {
            $app->halt(400, 'Bad');
            $this->fail('Did not catch exception!');
        } catch (\Slim\Exception\Stop $e) {
            $res = $e->getResponse();
            $body = $res->getBody();
            $this->assertAttributeEquals(400, 'status', $res);
            $this->assertEquals('Bad', (string)$body);
        }
    }

    /**
     * @expectedException \Slim\Exception\Pass
     */
    public function testPass()
    {
        $app = new App();
        $app->pass();
    }

    public function testRedirect()
    {
        $app = new App();
        try {
            $app->redirect('http://slimframework.com', 301);
            $this->fail('Did not catch exception!');
        } catch (\Slim\Exception\Stop $e) {
            $res = $e->getResponse();
            $this->assertAttributeEquals(301, 'status', $res);
            $this->assertEquals('http://slimframework.com', $res->getHeader('Location'));
        }
    }

    /********************************************************************************
     * Hooks
     *******************************************************************************/

    // TODO: Test hook methods... pending improvements

    /********************************************************************************
     * Middleware
     *******************************************************************************/

    public function testDefaultMiddlewareStack()
    {
        $app = new App();
        $prop = new \ReflectionProperty($app, 'middleware');
        $prop->setAccessible(true);

        $this->assertEquals(1, count($prop->getValue($app)));
        $this->assertSame($app, $prop->getValue($app)[0]);
    }

    public function testAddMiddleware()
    {
        $app = new App();
        $mw = function ($req, $res, $next) {
            return $res;
        };
        $app->add($mw);
        $prop = new \ReflectionProperty($app, 'middleware');
        $prop->setAccessible(true);

        $this->assertEquals(2, count($prop->getValue($app)));
        $this->assertInstanceOf('\Slim\Middleware', $prop->getValue($app)[0]);
        $this->assertAttributeSame($app, 'next', $prop->getValue($app)[0]);
    }

    public function testHaltInMiddleware()
    {
        $app = new App();
        $app['environment'] = function () {
            return Environment::mock([
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_URI' => '/foo',
                'REQUEST_METHOD' => 'GET'
            ]);
        };
        $app->add(function ($req, $res, $next) use ($app) {
            $app->halt(500, 'Halt');
            $res->write('Foo');
            return $res;
        });
        $app->get('/foo', function ($req, $res) {
            return $res->withStatus(302);
        });

        // Invoke app
        ob_start();
        $app->run();
        $output = ob_get_clean();

        $this->assertEquals('Halt', $output);
    }

    /********************************************************************************
     * Runner
     *******************************************************************************/

    public function testInvokeWithMatchingRoute()
    {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            $res->write('Hello');

            return $res;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET'
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = new Collection();
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello', (string)$res->getBody());
    }

    public function testInvokeWithoutMatchingRoute()
    {
        $app = new App();
        $app->get('/bar', function ($req, $res) {
            $res->write('Hello');

            return $res;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET'
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = new Collection();
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertAttributeEquals(404, 'status', $resOut);
    }

    public function testInvokeWithMatchingRouteAndPass()
    {
        $app = new App();
        $app->get('/foo/:one', function ($req, $res) use ($app) {
            $app->pass();
            return $res->withStatus(200);
        });
        $app->get('/foo/:two', function ($req, $res) {
            return $res->withStatus(400);
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/test',
            'REQUEST_METHOD' => 'GET'
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = new Collection();
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertAttributeEquals(400, 'status', $resOut);
    }

    public function testInvokeWithMatchingRouteAndHalt()
    {
        $app = new App();
        $app->get('/foo', function ($req, $res) use ($app) {
            $app->halt(400, 'Bad');
            return $res->withStatus(200);
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET'
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = new Collection();
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertAttributeEquals(400, 'status', $resOut);
    }

    // TODO: Test subRequest()

    // TODO: Test finalize()

    // TODO: Test run()
}
