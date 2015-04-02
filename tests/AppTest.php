<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */

use \Slim\App;
use \Slim\Http\Collection;
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

    public function testMapRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->map(['GET', 'POST'], $path, $callable);

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertAttributeContains('GET', 'methods', $route);
        $this->assertAttributeContains('POST', 'methods', $route);
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
        } catch (\Slim\Exception $e) {
            $this->assertSame($res, $e->getResponse());
        }
    }

    public function testHalt()
    {
        $app = new App();
        try {
            $app->halt(400, 'Bad');
            $this->fail('Did not catch exception!');
        } catch (\Slim\Exception $e) {
            $res = $e->getResponse();
            $body = $res->getBody();
            $this->assertAttributeEquals(400, 'status', $res);
            $this->assertEquals('Bad', (string)$body);
        }
    }

    /********************************************************************************
     * Middleware
     *******************************************************************************/

    public function testBottomMiddlewareIsApp()
    {
        $app = new App();
        $mw = function ($req, $res, $next) {
            return $res;
        };
        $app->add($mw);

        $prop = new \ReflectionProperty($app, 'stack');
        $prop->setAccessible(true);

        $this->assertEquals($app, $prop->getValue($app)->bottom());
    }

    public function testAddMiddleware()
    {
        $app = new App();
        $mw = function ($req, $res, $next) {
            return $res;
        };
        $app->add($mw);

        $prop = new \ReflectionProperty($app, 'stack');
        $prop->setAccessible(true);

        $this->assertCount(2, $prop->getValue($app));
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
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = new Collection();
        $serverParams = new Collection($env->all());
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
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
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = new Collection();
        $serverParams = new Collection($env->all());
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertAttributeEquals(404, 'status', $resOut);
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
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = new Collection();
        $serverParams = new Collection($env->all());
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        try {
            $app($req, $res);
            $this->fail('Did not catch \Slim\Exception');
        } catch (\Slim\Exception $e) {
            $this->assertEquals(400, $e->getResponse()->getStatusCode());
        }
    }

    public function testInvokeWithPimpleCallable()
    {
        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = new Collection();
        $serverParams = new Collection($env->all());
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        $mock = $this->getMock('StdClass', ['bar']);

        $app = new App();

        $app['foo'] = function() use ($mock, $res) {
            $mock->method('bar')
                ->willReturn(
                    $res->write('Hello')
                );
            return $mock;
        };

        $app->get('/foo', 'foo:bar');

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello', (string)$res->getBody());
    }

    public function testInvokeWithPimpleUndefinedCallable()
    {
        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = new Collection();
        $serverParams = new Collection($env->all());
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        $mock = $this->getMock('StdClass');

        $app = new App();

        $app['foo'] = function() use ($mock, $res) {
            return $mock;
        };

        $app->get('/foo', 'foo:bar');

        $this->setExpectedException('\RuntimeException', 'Route callable method does not exist');

        // Invoke app
        $app($req, $res);
    }

    // TODO: Test subRequest()

    // TODO: Test finalize()

    // TODO: Test run()
}
