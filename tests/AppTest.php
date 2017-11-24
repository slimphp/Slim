<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests;

use PHPUnit\Framework\TestCase;
use Pimple\Container as Pimple;
use Pimple\Psr11\Container as Psr11Container;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Exception\HttpNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler;
use Slim\Handlers\ErrorRenderers\HtmlErrorRenderer;
use Slim\Handlers\Strategies\RequestResponseArgs;
use Slim\Http\Body;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\RequestBody;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Router;
use Slim\Tests\Mocks\MockAction;
use Slim\Tests\Mocks\MockCustomException;
use Slim\Tests\Mocks\MockErrorHandler;

class AppTest extends TestCase
{
    public static function setupBeforeClass()
    {
        // ini_set('log_errors', 0);
        ini_set('error_log', tempnam(sys_get_temp_dir(), 'slim'));
    }

    public static function tearDownAfterClass()
    {
        // ini_set('log_errors', 1);
    }

    /**
     * helper to create a request object
     * @return Request
     */
    private function requestFactory($requestUri, $method = 'GET', $data = [])
    {
        $defaults = [
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => $requestUri,
            'REQUEST_METHOD' => $method,
        ];

        $data = array_merge($defaults, $data);

        $env = Environment::mock($data);
        $uri = Uri::createFromGlobals($env);
        $headers = Headers::createFromGlobals($env);
        $cookies = [];
        $serverParams = $env;
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request($method, $uri, $headers, $cookies, $serverParams, $body);

        return $request;
    }

    /********************************************************************************
     * Settings management methods
     *******************************************************************************/
    public function testHasSetting()
    {
        $app = new App();
        $this->assertTrue($app->hasSetting('httpVersion'));
        $this->assertFalse($app->hasSetting('foo'));
    }

    public function testGetSettings()
    {
        $app = new App();
        $appSettings = $app->getSettings();
        $this->assertAttributeEquals($appSettings, 'settings', $app);
    }

    public function testGetSettingExists()
    {
        $app = new App();
        $this->assertEquals('1.1', $app->getSetting('httpVersion'));
    }

    public function testGetSettingNotExists()
    {
        $app = new App();
        $this->assertNull($app->getSetting('foo'));
    }

    public function testGetSettingNotExistsWithDefault()
    {
        $app = new App();
        $this->assertEquals('what', $app->getSetting('foo', 'what'));
    }

    public function testAddSettings()
    {
        $app = new App();
        $app->addSettings(['foo' => 'bar']);
        $this->assertAttributeContains('bar', 'settings', $app);
    }

    public function testAddSetting()
    {
        $app = new App();
        $app->addSetting('foo', 'bar');
        $this->assertAttributeContains('bar', 'settings', $app);
    }

    public function testGetDefaultErrorHandler()
    {
        $app = new App();
        $this->assertInstanceOf('\Slim\Handlers\ErrorHandler', $app->getDefaultErrorHandler());
    }

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

    public function testAnyRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->any($path, $callable);

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertAttributeContains('GET', 'methods', $route);
        $this->assertAttributeContains('POST', 'methods', $route);
        $this->assertAttributeContains('PUT', 'methods', $route);
        $this->assertAttributeContains('PATCH', 'methods', $route);
        $this->assertAttributeContains('DELETE', 'methods', $route);
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

    /********************************************************************************
     * Route Patterns
     *******************************************************************************/
    public function testSegmentRouteThatDoesNotEndInASlash()
    {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            // Do something
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo', 'pattern', $router->lookupRoute('route0'));
    }

    public function testSegmentRouteThatEndsInASlash()
    {
        $app = new App();
        $app->get('/foo/', function ($req, $res) {
            // Do something
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testSegmentRouteThatDoesNotStartWithASlash()
    {
        $app = new App();
        $app->get('foo', function ($req, $res) {
            // Do something
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('foo', 'pattern', $router->lookupRoute('route0'));
    }

    public function testSingleSlashRoute()
    {
        $app = new App();
        $app->get('/', function ($req, $res) {
            // Do something
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyRoute()
    {
        $app = new App();
        $app->get('', function ($req, $res) {
            // Do something
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('', 'pattern', $router->lookupRoute('route0'));
    }

    /********************************************************************************
     * Route Groups
     *******************************************************************************/
    public function testGroupSegmentWithSegmentRouteThatDoesNotEndInASlash()
    {
        $app = new App();
        $app->group('/foo', function ($app) {
            $app->get('/bar', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithSegmentRouteThatEndsInASlash()
    {
        $app = new App();
        $app->group('/foo', function ($app) {
            $app->get('/bar/', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithSingleSlashRoute()
    {
        $app = new App();
        $app->group('/foo', function ($app) {
            $app->get('/', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithEmptyRoute()
    {
        $app = new App();
        $app->group('/foo', function ($app) {
            $app->get('', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo', 'pattern', $router->lookupRoute('route0'));
    }

    public function testTwoGroupSegmentsWithSingleSlashRoute()
    {
        $app = new App();
        $app->group('/foo', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('/', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/baz/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testTwoGroupSegmentsWithAnEmptyRoute()
    {
        $app = new App();
        $app->group('/foo', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/baz', 'pattern', $router->lookupRoute('route0'));
    }

    public function testTwoGroupSegmentsWithSegmentRoute()
    {
        $app = new App();
        $app->group('/foo', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/baz/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testTwoGroupSegmentsWithSegmentRouteThatHasATrailingSlash()
    {
        $app = new App();
        $app->group('/foo', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('/bar/', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/baz/bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithSingleSlashNestedGroupAndSegmentRoute()
    {
        $app = new App();
        $app->group('/foo', function ($app) {
            $app->group('/', function ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo//bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithSingleSlashGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $app = new App();
        $app->group('/foo', function ($app) {
            $app->group('/', function ($app) {
                $app->get('bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithEmptyNestedGroupAndSegmentRoute()
    {
        $app = new App();
        $app->group('/foo', function ($app) {
            $app->group('', function ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithEmptyNestedGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $app = new App();
        $app->group('/foo', function ($app) {
            $app->group('', function ($app) {
                $app->get('bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/foobar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithSegmentRouteThatDoesNotEndInASlash()
    {
        $app = new App();
        $app->group('/', function ($app) {
            $app->get('/bar', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('//bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithSegmentRouteThatEndsInASlash()
    {
        $app = new App();
        $app->group('/', function ($app) {
            $app->get('/bar/', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('//bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithSingleSlashRoute()
    {
        $app = new App();
        $app->group('/', function ($app) {
            $app->get('/', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('//', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithEmptyRoute()
    {
        $app = new App();
        $app->group('/', function ($app) {
            $app->get('', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithNestedGroupSegmentWithSingleSlashRoute()
    {
        $app = new App();
        $app->group('/', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('/', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('//baz/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithNestedGroupSegmentWithAnEmptyRoute()
    {
        $app = new App();
        $app->group('/', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('//baz', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithNestedGroupSegmentWithSegmentRoute()
    {
        $app = new App();
        $app->group('/', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('//baz/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithNestedGroupSegmentWithSegmentRouteThatHasATrailingSlash()
    {
        $app = new App();
        $app->group('/', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('/bar/', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('//baz/bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithSingleSlashNestedGroupAndSegmentRoute()
    {
        $app = new App();
        $app->group('/', function ($app) {
            $app->group('/', function ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('///bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithSingleSlashGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $app = new App();
        $app->group('/', function ($app) {
            $app->group('/', function ($app) {
                $app->get('bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('//bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithEmptyNestedGroupAndSegmentRoute()
    {
        $app = new App();
        $app->group('/', function ($app) {
            $app->group('', function ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('//bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithEmptyNestedGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $app = new App();
        $app->group('/', function ($app) {
            $app->group('', function ($app) {
                $app->get('bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithSegmentRouteThatDoesNotEndInASlash()
    {
        $app = new App();
        $app->group('', function ($app) {
            $app->get('/bar', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithSegmentRouteThatEndsInASlash()
    {
        $app = new App();
        $app->group('', function ($app) {
            $app->get('/bar/', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithSingleSlashRoute()
    {
        $app = new App();
        $app->group('', function ($app) {
            $app->get('/', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithEmptyRoute()
    {
        $app = new App();
        $app->group('', function ($app) {
            $app->get('', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithNestedGroupSegmentWithSingleSlashRoute()
    {
        $app = new App();
        $app->group('', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('/', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/baz/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithNestedGroupSegmentWithAnEmptyRoute()
    {
        $app = new App();
        $app->group('', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/baz', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithNestedGroupSegmentWithSegmentRoute()
    {
        $app = new App();
        $app->group('', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/baz/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithNestedGroupSegmentWithSegmentRouteThatHasATrailingSlash()
    {
        $app = new App();
        $app->group('', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('/bar/', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/baz/bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithSingleSlashNestedGroupAndSegmentRoute()
    {
        $app = new App();
        $app->group('', function ($app) {
            $app->group('/', function ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('//bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithSingleSlashGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $app = new App();
        $app->group('', function ($app) {
            $app->group('/', function ($app) {
                $app->get('bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithEmptyNestedGroupAndSegmentRoute()
    {
        $app = new App();
        $app->group('', function ($app) {
            $app->group('', function ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithEmptyNestedGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $app = new App();
        $app->group('', function ($app) {
            $app->group('', function ($app) {
                $app->get('bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('bar', 'pattern', $router->lookupRoute('route0'));
    }

    /********************************************************************************
     * Middleware
     *******************************************************************************/
    public function testBottomMiddlewareIsApp()
    {
        $app = new App();
        $bottom = null;
        $mw = function ($req, $res, $next) use (&$bottom) {
            $bottom = $next;
            return $res;
        };
        $app->add($mw);

        $app->callMiddlewareStack(
            $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder('Psr\Http\Message\ResponseInterface')->disableOriginalConstructor()->getMock()
        );

        $this->assertEquals($app, $bottom);
    }

    public function testAddMiddleware()
    {
        $app = new App();
        $called = 0;

        $mw = function ($req, $res, $next) use (&$called) {
            $called++;
            return $res;
        };
        $app->add($mw);

        $app->callMiddlewareStack(
            $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder('Psr\Http\Message\ResponseInterface')->disableOriginalConstructor()->getMock()
        );

        $this->assertSame($called, 1);
    }

    public function testAddMiddlewareOnRoute()
    {
        $app = new App();

        $app->get('/', function ($req, $res) {
            return $res->write('Center');
        })->add(function ($req, $res, $next) {
            $res->write('In1');
            $res = $next($req, $res);
            $res->write('Out1');

            return $res;
        })->add(function ($req, $res, $next) {
            $res->write('In2');
            $res = $next($req, $res);
            $res->write('Out2');

            return $res;
        });

        // Prepare request and response objects
        $request = $this->requestFactory('/');
        $response = new Response();

        // Invoke app
        $response = $app($request, $response);

        $this->assertEquals('In2In1CenterOut1Out2', (string)$response->getBody());
    }


    public function testAddMiddlewareOnRouteGroup()
    {
        $app = new App();

        $app->group('/foo', function ($app) {
            $app->get('/', function ($req, $res) {
                return $res->write('Center');
            });
        })->add(function ($req, $res, $next) {
            $res->write('In1');
            $res = $next($req, $res);
            $res->write('Out1');

            return $res;
        })->add(function ($req, $res, $next) {
            $res->write('In2');
            $res = $next($req, $res);
            $res->write('Out2');

            return $res;
        });

        // Prepare request and response objects
        $request = $this->requestFactory('/foo/');
        $response = new Response();

        // Invoke app
        $response = $app($request, $response);

        $this->assertEquals('In2In1CenterOut1Out2', (string)$response->getBody());
    }

    public function testAddMiddlewareOnTwoRouteGroup()
    {
        $app = new App();

        $app->group('/foo', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('/', function ($req, $res) {
                    return $res->write('Center');
                });
            })->add(function ($req, $res, $next) {
                $res->write('In2');
                $res = $next($req, $res);
                $res->write('Out2');

                return $res;
            });
        })->add(function ($req, $res, $next) {
            $res->write('In1');
            $res = $next($req, $res);
            $res->write('Out1');

            return $res;
        });

        // Prepare request and response objects
        $request = $this->requestFactory('/foo/baz/');
        $response = new Response();

        // Invoke app
        $response = $app($request, $response);

        $this->assertEquals('In1In2CenterOut2Out1', (string)$response->getBody());
    }

    public function testAddMiddlewareOnRouteAndOnTwoRouteGroup()
    {
        $app = new App();

        $app->group('/foo', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('/', function ($req, $res) {
                    return $res->write('Center');
                })->add(function ($req, $res, $next) {
                    $res->write('In3');
                    $res = $next($req, $res);
                    $res->write('Out3');

                    return $res;
                });
            })->add(function ($req, $res, $next) {
                $res->write('In2');
                $res = $next($req, $res);
                $res->write('Out2');

                return $res;
            });
        })->add(function ($req, $res, $next) {
            $res->write('In1');
            $res = $next($req, $res);
            $res->write('Out1');

            return $res;
        });

        // Prepare request and response objects
        $request = $this->requestFactory('/foo/baz/');
        $response = new Response();

        // Invoke app
        $response = $app($request, $response);

        $this->assertEquals('In1In2In3CenterOut3Out2Out1', (string)$response->getBody());
    }

    /********************************************************************************
     * Error Handlers
     *******************************************************************************/
    public function testSetErrorHandler()
    {
        $app = $this->appFactory();
        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function () {
            throw new HttpNotFoundException();
        });
        $exception = HttpNotFoundException::class;
        $handler = function ($req, $res) {
            return $res->withJson(['Oops..']);
        };
        $app->setErrorHandler($exception, $handler);

        $request = $this->requestFactory('/foo/baz/');
        $response = new Response();
        $res = $app($request, $response);
        $expectedOutput = json_encode(['Oops..']);

        $this->assertEquals($res->getBody(), $expectedOutput);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSetErrorHandlerThrowsExceptionWhenInvalidCallableIsPassed()
    {
        $app = new App();
        $app->setErrorHandler(HttpNotFoundException::class, 'UnresolvableCallable');
    }

    public function testSetDefaultErrorHandler()
    {
        $app = $this->appFactory();
        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function () {
            throw new HttpNotFoundException();
        });
        $handler = function ($req, $res) {
            return $res->withJson(['Oops..']);
        };
        $app->setDefaultErrorHandler($handler);

        $request = $this->requestFactory('/foo/baz/');
        $response = new Response();
        $res = $app($request, $response);
        $expectedOutput = json_encode(['Oops..']);

        $this->assertEquals($res->getBody(), $expectedOutput);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSetDefaultErrorHandlerThrowsExceptionWhenInvalidCallableIsPassed()
    {
        $app = new App();
        $app->setDefaultErrorHandler(HttpNotFoundException::class, 'UnresolvableCallable');
    }

    public function testErrorHandlerShortcuts()
    {
        $app = new App();
        $handler = new MockErrorHandler();
        $app->setNotAllowedHandler($handler);
        $app->setNotFoundHandler($handler);

        $this->assertInstanceOf(MockErrorHandler::class, $app->getErrorHandler(HttpNotAllowedException::class));
        $this->assertInstanceOf(MockErrorHandler::class, $app->getErrorHandler(HttpNotFoundException::class));
    }

    public function testGetErrorHandlerWillReturnDefaultErrorHandlerForUnhandledExceptions()
    {
        $app = new App();
        $exception = MockCustomException::class;
        $handler = $app->getErrorHandler($exception);

        $this->assertInstanceOf(ErrorHandler::class, $handler);
    }

    public function testGetErrorHandlerResolvesContainerCallableWhenHandlerPassedIntoSettings()
    {
        $app = new App();
        $pimple = new Pimple();
        $container = new Psr11Container($pimple);
        $app->setContainer($container);
        $app->setNotAllowedHandler(MockErrorHandler::class);
        $handler = $app->getErrorHandler(HttpNotAllowedException::class);

        $this->assertEquals([new MockErrorHandler(), '__invoke'], $handler);
    }

    public function testGetDefaultHandlerResolvesContainerCallableWhenHandlerPassedIntoSettings()
    {
        $app = new App();
        $pimple = new Pimple();
        $container = new Psr11Container($pimple);
        $app->setContainer($container);
        $app->setDefaultErrorHandler(MockErrorHandler::class);
        $handler = $app->getDefaultErrorHandler();

        $this->assertEquals([new MockErrorHandler(), '__invoke'], $handler);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetErrorHandlersThrowsExceptionWhenErrorHandlersArgumentSettingIsNotArray()
    {
        $settings = ['errorHandlers' => 'ShouldBeArray'];
        $app = new App($settings);
        $handler = new MockErrorHandler();
        $app->setErrorHandler(HttpNotFoundException::class, $handler);
    }

    /********************************************************************************
     * Runner
     *******************************************************************************/
    public function testInvokeReturnMethodNotAllowed()
    {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            $res->write('Hello');

            return $res;
        });

        // Prepare request and response objects
        $request = $this->requestFactory('/foo', 'POST');
        $response = new Response();

        // Create Html Renderer and Assert Output
        $exception = new HttpNotAllowedException;
        $exception->setAllowedMethods(['GET']);
        $renderer = new HtmlErrorRenderer($exception, false);

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals(405, (string)$resOut->getStatusCode());
        $this->assertEquals(['GET'], $resOut->getHeader('Allow'));
        $this->assertContains(
            $renderer->render(),
            (string)$resOut->getBody()
        );
    }

    public function testInvokeWithMatchingRoute()
    {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            $res->write('Hello');

            return $res;
        });

        // Prepare request and response objects
        $request = $this->requestFactory('/foo');
        $response = new Response();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello', (string)$resOut->getBody());
    }

    public function testInvokeWithMatchingRouteWithSetArgument()
    {
        $app = new App();
        $app->get('/foo/bar', function ($req, $res, $args) {
            return $res->write("Hello {$args['attribute']}");
        })->setArgument('attribute', 'world!');

        // Prepare request and response objects
        $request = $this->requestFactory('/foo/bar');
        $response = new Response();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello world!', (string)$resOut->getBody());
    }

    public function testInvokeWithMatchingRouteWithSetArguments()
    {
        $app = new App();
        $app->get('/foo/bar', function ($req, $res, $args) {
            return $res->write("Hello {$args['attribute1']} {$args['attribute2']}");
        })->setArguments(['attribute1' => 'there', 'attribute2' => 'world!']);

        // Prepare request and response objects
        $request = $this->requestFactory('/foo/bar');
        $response = new Response();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello there world!', (string)$resOut->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameter()
    {
        $app = new App();
        $app->get('/foo/{name}', function ($req, $res, $args) {
            return $res->write("Hello {$args['name']}");
        });

        // Prepare request and response objects
        $request = $this->requestFactory('/foo/test!');
        $response = new Response();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello test!', (string)$resOut->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterRequestResponseArgStrategy()
    {
        $app = new App();
        $app->getRouter()->setDefaultInvocationStrategy(new RequestResponseArgs());
        $app->get('/foo/{name}', function ($req, $res, $name) {
            return $res->write("Hello {$name}");
        });

        // Prepare request and response objects
        $request = $this->requestFactory('/foo/test!');
        $response = new Response();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello test!', (string)$resOut->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterOverwritesSetArgument()
    {
        $app = new App();
        $app->get('/foo/{name}', function ($req, $res, $args) {
            return $res->write("Hello {$args['extra']} {$args['name']}");
        })->setArguments(['extra' => 'there', 'name' => 'world!']);

        // Prepare request and response objects
        $request = $this->requestFactory('/foo/test!');
        $response = new Response();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello there test!', (string)$resOut->getBody());
    }

    public function testInvokeWithoutMatchingRoute()
    {
        $app = new App();
        $app->get('/bar', function ($req, $res) {
            $res->write('Hello');

            return $res;
        });

        // Prepare request and response objects
        $request = $this->requestFactory('/foo');
        $response = new Response();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertAttributeEquals(404, 'status', $resOut);
    }

    public function testInvokeWithCallableRegisteredInContainer()
    {
        // Prepare request and response objects
        $request = $this->requestFactory('/foo');
        $response = new Response();

        $mock = $this->getMockBuilder('StdClass')->setMethods(['bar'])->getMock();

        $pimple = new Pimple();
        $pimple['foo'] = function () use ($mock, $response) {
            $mock->method('bar')
                ->willReturn(
                    $response->write('Hello')
                );
            return $mock;
        };

        $app = new App();
        $app->setContainer(new Psr11Container($pimple));
        $app->get('/foo', 'foo:bar');

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello', (string)$resOut->getBody());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvokeWithNonExistentMethodOnCallableRegisteredInContainer()
    {
        // Prepare request and response objects
        $request = $this->requestFactory('/foo');
        $response = new Response();

        $mock = $this->getMockBuilder('StdClass')->getMock();

        $pimple = new Pimple();
        $pimple['foo'] = function () use ($mock) {
            return $mock;
        };

        $app = new App();
        $app->setContainer(new Psr11Container($pimple));
        $app->get('/foo', 'foo:bar');

        // Invoke app
        $app($request, $response);
    }

    public function testInvokeWithCallableInContainerViaMagicMethod()
    {
        // Prepare request and response objects
        $request = $this->requestFactory('/foo');
        $response = new Response();

        $mock = new MockAction();

        $pimple = new Pimple();
        $pimple['foo'] = function () use ($mock) {
            return $mock;
        };

        $app = new App();
        $app->setContainer(new Psr11Container($pimple));
        $app->get('/foo', 'foo:bar');

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals(json_encode(['name'=>'bar', 'arguments' => []]), (string)$resOut->getBody());
    }

    public function testInvokeFunctionName()
    {
        $app = new App();

        // @codingStandardsIgnoreStart
        function handle($req, $res)
        {
            $res->write('foo');

            return $res;
        }
        // @codingStandardsIgnoreEnd

        $app->get('/foo', __NAMESPACE__ . '\handle');

        // Prepare request and response objects
        $request = $this->requestFactory('/foo');
        $response = new Response();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertEquals('foo', (string)$resOut->getBody());
    }

    public function testCurrentRequestAttributesAreNotLostWhenAddingRouteArguments()
    {
        $app = new App();
        $app->get('/foo/{name}', function ($req, $res, $args) {
            return $res->write($req->getAttribute('one') . $args['name']);
        });

        // Prepare request and response objects
        $request = $this->requestFactory('/foo/rob');
        $request = $request->withAttribute("one", 1);
        $response = new Response();

        // Invoke app
        $resOut = $app($request, $response);
        $this->assertEquals('1rob', (string)$resOut->getBody());
    }

    public function testCurrentRequestAttributesAreNotLostWhenAddingRouteArgumentsRequestResponseArg()
    {
        $app = new App();
        $app->getRouter()->setDefaultInvocationStrategy(new RequestResponseArgs());
        $app->get('/foo/{name}', function ($req, $res, $name) {
            return $res->write($req->getAttribute('one') . $name);
        });

        // Prepare request and response objects
        $request = $this->requestFactory('/foo/rob');
        $request = $request->withAttribute("one", 1);
        $response = new Response();

        // Invoke app
        $resOut = $app($request, $response);
        $this->assertEquals('1rob', (string)$resOut->getBody());
    }

    // TODO: Test finalize()

    // TODO: Test run()
    public function testRun()
    {
        $currentServer = $_SERVER; // backup $_SERVER

        $app = new App();
        $_SERVER = [
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ];

        $app->get('/foo', function ($req, $res) {
            $res->write('bar');

            return $res;
        });

        ob_start();
        $app->run();
        $resOut = ob_get_clean();

        $this->assertEquals('bar', (string)$resOut);

        $_SERVER = $currentServer; // restore $_SERVER
    }

    public function testRespond()
    {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            $res->write('Hello');

            return $res;
        });

        // Prepare request and response objects
        $request = $this->requestFactory('/foo');
        $response = new Response();

        // Invoke app
        $resOut = $app($request, $response);

        $app->respond($resOut);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->expectOutputString('Hello');
    }

    public function testRespondWithHeaderNotSent()
    {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            $res->write('Hello');

            return $res;
        });

        // Prepare request and response objects
        $request = $this->requestFactory('/foo');
        $response = new Response();

        // Invoke app
        $resOut = $app($request, $response);

        $app->respond($resOut);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->expectOutputString('Hello');
    }

    public function testRespondNoContent()
    {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            $res = $res->withStatus(204);
            return $res;
        });

        // Prepare request and response objects
        $request = $this->requestFactory('/foo');
        $response = new Response();

        // Invoke app
        $resOut = $app($request, $response);

        $app->respond($resOut);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals([], $resOut->getHeader('Content-Type'));
        $this->assertEquals([], $resOut->getHeader('Content-Length'));
        $this->expectOutputString('');
    }

    public function testRespondWithPaddedStreamFilterOutput()
    {
        $availableFilter = stream_get_filters();

        if (version_compare(phpversion(), '7.0.0', '>=')) {
            $filterName           = 'string.rot13';
            $unfilterName         = 'string.rot13';
            $specificFilterName   = 'string.rot13';
            $specificUnfilterName = 'string.rot13';
        } else {
            $filterName           = 'mcrypt.*';
            $unfilterName         = 'mdecrypt.*';
            $specificFilterName   = 'mcrypt.rijndael-128';
            $specificUnfilterName = 'mdecrypt.rijndael-128';
        }

        if (in_array($filterName, $availableFilter) && in_array($unfilterName, $availableFilter)) {
            $app = new App();
            $app->get('/foo', function ($req, $res) use ($specificFilterName, $specificUnfilterName) {
                $key = base64_decode('xxxxxxxxxxxxxxxx');
                $iv = base64_decode('Z6wNDk9LogWI4HYlRu0mng==');

                $data = 'Hello';
                $length = strlen($data);

                $stream = fopen('php://temp', 'r+');

                $filter = stream_filter_append($stream, $specificFilterName, STREAM_FILTER_WRITE, [
                    'key' => $key,
                    'iv' => $iv
                ]);

                fwrite($stream, $data);
                rewind($stream);
                stream_filter_remove($filter);

                stream_filter_append($stream, $specificUnfilterName, STREAM_FILTER_READ, [
                    'key' => $key,
                    'iv' => $iv
                ]);

                return $res->withHeader('Content-Length', $length)->withBody(new Body($stream));
            });

            // Prepare request and response objects
            $request = $this->requestFactory('/foo');
            $response = new Response();

            // Invoke app
            $resOut = $app($request, $response);
            $app->respond($resOut);

            $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
            $this->expectOutputString('Hello');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testRespondIndeterminateLength()
    {
        $app = new App();
        $body_stream = fopen('php://temp', 'r+');
        $response = new Response();
        $body = $this->getMockBuilder("\Slim\Http\Body")
            ->setMethods(["getSize"])
            ->setConstructorArgs([$body_stream])
            ->getMock();
        fwrite($body_stream, "Hello");
        rewind($body_stream);
        $body->method("getSize")->willReturn(null);
        $response = $response->withBody($body);
        $app->respond($response);
        $this->expectOutputString("Hello");
    }

    public function testResponseWithStreamReadYieldingLessBytesThanAsked()
    {
        $app = new App([
            'settings' => ['responseChunkSize' => Mocks\SmallChunksStream::CHUNK_SIZE * 2]
        ]);
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
        $uri = Uri::createFromGlobals($env);
        $headers = Headers::createFromGlobals($env);
        $cookies = [];
        $serverParams = $env;
        $body = new Mocks\SmallChunksStream();
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = (new Response())->withBody($body);

        // Invoke app
        $resOut = $app($request, $response);

        $app->respond($resOut);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->expectOutputString(str_repeat('.', Mocks\SmallChunksStream::SIZE));
    }

    public function testExceptionErrorHandlerDoesNotDisplayErrorDetails()
    {
        $app = new App();

        // Prepare request and response objects
        $request = $this->requestFactory('/foo');
        $response = new Response();

        $mw = function ($req, $res, $next) {
            throw new \Exception('middleware exception');
        };

        $app->add($mw);

        $app->get('/foo', function ($request, $response) {
            return $response;
        });

        $resOut = $app->process($request, $response);

        $this->assertEquals(500, $resOut->getStatusCode());
        $this->assertNotRegExp('/.*middleware exception.*/', (string)$resOut);
    }

    /**
     * @requires PHP 7.0
     */
    public function testExceptionPhpErrorHandlerDoesNotDisplayErrorDetails()
    {
        $app = new App();

        // Prepare request and response objects
        $request = $this->requestFactory('/foo');
        $response = new Response();

        $mw = function ($req, $res, $next) {
            dumpFonction();
        };

        $app->add($mw);

        $app->get('/foo', function ($request, $response) {
            return $res;
        });

        $resOut = $app->process($request, $response);

        $this->assertEquals(500, $resOut->getStatusCode());
        $this->assertNotRegExp('/.*middleware exception.*/', (string)$resOut);
    }

    public function appFactory()
    {
        $app = new App();

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromGlobals($env);
        $headers = Headers::createFromGlobals($env);
        $cookies = [];
        $serverParams = $env;
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();
        $app->getContainer()['request'] = $req;
        $app->getContainer()['response'] = $res;

        return $app;
    }

    /**
     * @requires PHP 7.0
     */
    public function testProcessThrowable()
    {
        $app = new App();
        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function ($req, $res, $args) {
            throw new \Error('Failed');
        });

        $request = $this->requestFactory('/foo');
        $response = new Response();
        $resOut = $app->process($request, $response);

        $resOut->getBody()->rewind();

        $this->assertSame(500, $resOut->getStatusCode());
        $this->assertSame('text/html', $resOut->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$resOut->getBody(), '<html>'));
    }

    public function testProcessNotFound()
    {
        $app = new App();

        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function () {
            throw new HttpNotFoundException;
        });

        $request = $this->requestFactory('/foo');
        $response = new Response();
        $res = $app->process($request, $response);

        $this->assertEquals(404, $res->getStatusCode());
    }

    public function testRunNotAllowed()
    {
        $app = $this->appFactory();
        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function () {
            throw new HttpNotAllowedException;
        });

        $request = $this->requestFactory('/foo');
        $response = new Response();
        $res = $app->process($request, $response);

        $this->assertEquals(405, $res->getStatusCode());
    }

    public function testExceptionErrorHandlerDisplaysErrorDetails()
    {
        $app = new App([
            'displayErrorDetails' => true
        ]);

        // Prepare request and response objects
        $request = $this->requestFactory('/foo');
        $response = new Response();

        $mw = function ($req, $res, $next) {
            throw new \RuntimeException('middleware exception');
        };

        $app->add($mw);

        $app->get('/foo', function ($req, $res) {
            return $res;
        });

        $resOut = $app->process($request, $response);

        $this->assertEquals(500, $resOut->getStatusCode());
        $this->assertRegExp('/.*middleware exception.*/', (string)$resOut);
    }

    public function testFinalize()
    {
        $method = new \ReflectionMethod('Slim\App', 'finalize');
        $method->setAccessible(true);

        $response = new Response();
        $response->getBody()->write('foo');
        $response = $response->withHeader('Content-Type', 'text/plain');

        $response = $method->invoke(new App(), $response);

        $this->assertTrue($response->hasHeader('Content-Type'));
    }

    public function testFinalizeWithoutBody()
    {
        $method = new \ReflectionMethod('Slim\App', 'finalize');
        $method->setAccessible(true);

        $response = $method->invoke(new App(), new Response(304));

        $this->assertFalse($response->hasHeader('Content-Length'));
        $this->assertFalse($response->hasHeader('Content-Type'));
    }

    public function testUnsupportedMethodWithoutRoute()
    {
        $app = new App();
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'BADMTHD',
        ]);
        $uri = Uri::createFromGlobals($env);
        $headers = Headers::createFromGlobals($env);
        $request = new Request('GET', $uri, $headers, [], $env, new RequestBody());
        $response = new Response();

        $resOut = $app->process($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals(404, $resOut->getStatusCode());
    }

    public function testUnsupportedMethodWithRoute()
    {
        $app = new App();
        $app->get('/', function () {
            // stubbed action to give us a route at /
        });

        $request = $this->requestFactory('/', 'BADMTHD');
        $response = new Response();

        $resOut = $app->process($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals(405, $resOut->getStatusCode());
    }

    public function testContainerSetToRoute()
    {
        // Prepare request and response objects
        $request = $this->requestFactory('/foo');
        $response = new Response();

        $mock = new MockAction();

        $pimple = new Pimple();
        $pimple['foo'] = function () use ($mock) {
            return $mock;
        };

        $app = new App();
        $app->setContainer(new Psr11Container($pimple));

        /** @var $router Router */
        $router = $app->getRouter();
        $router->map(['get'], '/foo', 'foo:bar');

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals(json_encode(['name'=>'bar', 'arguments' => []]), (string)$resOut->getBody());
    }

    public function testIsEmptyResponseWithEmptyMethod()
    {
        $method = new \ReflectionMethod('Slim\App', 'isEmptyResponse');
        $method->setAccessible(true);

        $response = new Response();
        $response = $response->withStatus(204);

        $result = $method->invoke(new App(), $response);
        $this->assertTrue($result);
    }

    public function testIsEmptyResponseWithoutEmptyMethod()
    {
        $method = new \ReflectionMethod('Slim\App', 'isEmptyResponse');
        $method->setAccessible(true);

        /** @var Response $response */
        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $response->method('getStatusCode')
            ->willReturn(204);

        $result = $method->invoke(new App(), $response);
        $this->assertTrue($result);
    }

    protected function skipIfPhp70()
    {
        if (version_compare(PHP_VERSION, '7.0', '>=')) {
            $this->markTestSkipped("Test is for PHP 5.6 or lower");
        }
    }
}
