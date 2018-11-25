<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use Pimple\Container as Pimple;
use Pimple\Psr11\Container as Psr11Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Slim\App;
use Slim\CallableResolver;
use Slim\Error\Renderers\HtmlErrorRenderer;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Handlers\Strategies\RequestResponseArgs;
use Slim\Router;
use Slim\Tests\Mocks\MockAction;

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

    public function testSetContainer()
    {
        $app = new App();
        $pimple = new Pimple();
        $container = new Psr11Container($pimple);
        $app->setContainer($container);
        $this->assertSame($container, $app->getContainer());
    }

    public function testSetCallableResolver()
    {
        $app = new App();
        $callableResolver = new CallableResolver();
        $app->setCallableResolver($callableResolver);
        $this->assertSame($callableResolver, $app->getCallableResolver());
    }

    public function testSetRouter()
    {
        $app = new App();
        $router = new Router();
        $app->setRouter($router);
        $this->assertSame($router, $app->getRouter());
    }

    /********************************************************************************
     * Router proxy methods
     *******************************************************************************/

    public function testGetRoute()
    {
        $path = '/foo';
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
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
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
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
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
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
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
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
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
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
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
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
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
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
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            // Do something
        };
        $app = new App();
        $route = $app->map(['GET', 'POST'], $path, $callable);

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertAttributeContains('GET', 'methods', $route);
        $this->assertAttributeContains('POST', 'methods', $route);
    }

    public function testRedirectRoute()
    {
        $source = '/foo';
        $destination = '/bar';

        $app = new App();
        $route = $app->redirect($source, $destination, 301);

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertAttributeContains('GET', 'methods', $route);

        $response = $route->run($this->createServerRequest($source), $this->createResponse());
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals($destination, $response->getHeaderLine('Location'));

        $routeWithDefaultStatus = $app->redirect($source, $destination);
        $response = $routeWithDefaultStatus->run($this->createServerRequest($source), $this->createResponse());
        $this->assertEquals(302, $response->getStatusCode());

        $uri = $this->getMockBuilder(UriInterface::class)->getMock();
        $uri->expects($this->once())->method('__toString')->willReturn($destination);

        $routeToUri = $app->redirect($source, $uri);
        $response = $routeToUri->run($this->createServerRequest($source), $this->createResponse());
        $this->assertEquals($destination, $response->getHeaderLine('Location'));
    }

    public function testRouteWithInternationalCharacters()
    {
        $app = new App();
        $app->get('/новости', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello');
            return $response;
        });

        // Prepare request and response objects
        $request = $this->createServerRequest('/новости');
        $response = $this->createResponse();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello', (string)$resOut->getBody());
    }

    /********************************************************************************
     * Route Patterns
     *******************************************************************************/
    public function testSegmentRouteThatDoesNotEndInASlash()
    {
        $app = new App();
        $app->get('/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            // Do something
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo', 'pattern', $router->lookupRoute('route0'));
    }

    public function testSegmentRouteThatEndsInASlash()
    {
        $app = new App();
        $app->get('/foo/', function (ServerRequestInterface $request, ResponseInterface $response) {
            // Do something
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testSegmentRouteThatDoesNotStartWithASlash()
    {
        $app = new App();
        $app->get('foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            // Do something
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('foo', 'pattern', $router->lookupRoute('route0'));
    }

    public function testSingleSlashRoute()
    {
        $app = new App();
        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            // Do something
        });
        /** @var \Slim\Router $router */
        $router = $app->getRouter();
        $this->assertAttributeEquals('/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyRoute()
    {
        $app = new App();
        $app->get('', function (ServerRequestInterface $request, ResponseInterface $response) {
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
            $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
            $app->get('/bar/', function (ServerRequestInterface $request, ResponseInterface $response) {
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
            $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
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
            $app->get('', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('/bar/', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
            $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
            $app->get('/bar/', function (ServerRequestInterface $request, ResponseInterface $response) {
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
            $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
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
            $app->get('', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('/bar/', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
            $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
            $app->get('/bar/', function (ServerRequestInterface $request, ResponseInterface $response) {
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
            $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
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
            $app->get('', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('/bar/', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
                $app->get('bar', function (ServerRequestInterface $request, ResponseInterface $response) {
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
        $mw = function (ServerRequestInterface $request, ResponseInterface $response, $next) use (&$bottom) {
            $bottom = $next;
            return $response;
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

        $mw = function (ServerRequestInterface $request, ResponseInterface $response, $next) use (&$called) {
            $called++;
            return $response;
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

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Center');
            return $response;
        })->add(function (ServerRequestInterface $request, ResponseInterface $response, $next) {
            $response->getBody()->write('In1');
            $response = $next($request, $response);
            $response->getBody()->write('Out1');
            return $response;
        })->add(function (ServerRequestInterface $request, ResponseInterface $response, $next) {
            $response->getBody()->write('In2');
            $response = $next($request, $response);
            $response->getBody()->write('Out2');
            return $response;
        });

        // Prepare request and response objects
        $request = $this->createServerRequest('/');
        $response = $this->createResponse();

        // Invoke app
        $response = $app($request, $response);

        $this->assertEquals('In2In1CenterOut1Out2', (string)$response->getBody());
    }


    public function testAddMiddlewareOnRouteGroup()
    {
        $app = new App();

        $app->group('/foo', function ($app) {
            $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
                $response->getBody()->write('Center');
                return $response;
            });
        })->add(function (ServerRequestInterface $request, ResponseInterface $response, $next) {
            $response->getBody()->write('In1');
            $response = $next($request, $response);
            $response->getBody()->write('Out1');
            return $response;
        })->add(function (ServerRequestInterface $request, ResponseInterface $response, $next) {
            $response->getBody()->write('In2');
            $response = $next($request, $response);
            $response->getBody()->write('Out2');
            return $response;
        });

        // Prepare request and response objects
        $request = $this->createServerRequest('/foo/');
        $response = $this->createResponse();

        // Invoke app
        $response = $app($request, $response);

        $this->assertEquals('In2In1CenterOut1Out2', (string)$response->getBody());
    }

    public function testAddMiddlewareOnTwoRouteGroup()
    {
        $app = new App();

        $app->group('/foo', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
                    $response->getBody()->write('Center');
                    return $response;
                });
            })->add(function (ServerRequestInterface $request, ResponseInterface $response, $next) {
                $response->getBody()->write('In2');
                $response = $next($request, $response);
                $response->getBody()->write('Out2');
                return $response;
            });
        })->add(function (ServerRequestInterface $request, ResponseInterface $response, $next) {
            $response->getBody()->write('In1');
            $response = $next($request, $response);
            $response->getBody()->write('Out1');
            return $response;
        });

        // Prepare request and response objects
        $request = $this->createServerRequest('/foo/baz/');
        $response = $this->createResponse();

        // Invoke app
        $response = $app($request, $response);

        $this->assertEquals('In1In2CenterOut2Out1', (string)$response->getBody());
    }

    public function testAddMiddlewareOnRouteAndOnTwoRouteGroup()
    {
        $app = new App();

        $app->group('/foo', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
                    $response->getBody()->write('Center');
                    return $response;
                })->add(function (ServerRequestInterface $request, ResponseInterface $response, $next) {
                    $response->getBody()->write('In3');
                    $response = $next($request, $response);
                    $response->getBody()->write('Out3');
                    return $response;
                });
            })->add(function (ServerRequestInterface $request, ResponseInterface $response, $next) {
                $response->getBody()->write('In2');
                $response = $next($request, $response);
                $response->getBody()->write('Out2');
                return $response;
            });
        })->add(function (ServerRequestInterface $request, ResponseInterface $response, $next) {
            $response->getBody()->write('In1');
            $response = $next($request, $response);
            $response->getBody()->write('Out1');
            return $response;
        });

        // Prepare request and response objects
        $request = $this->createServerRequest('/foo/baz/');
        $response = $this->createResponse();

        // Invoke app
        $response = $app($request, $response);

        $this->assertEquals('In1In2In3CenterOut3Out2Out1', (string)$response->getBody());
    }


    /********************************************************************************
     * Runner
     *******************************************************************************/

    /**
     * @expectedException \Slim\Exception\HttpMethodNotAllowedException
     */
    public function testInvokeReturnMethodNotAllowed()
    {
        $app = new App();
        $app->get('/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello');
            return $response;
        });

        // Prepare request and response objects
        $request = $this->createServerRequest('/foo', 'POST');
        $response = $this->createResponse();

        // Create Html Renderer and Assert Output
        $exception = new HttpMethodNotAllowedException($request);
        $exception->setAllowedMethods(['GET']);
        $renderer = new HtmlErrorRenderer();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals(405, (string)$resOut->getStatusCode());
        $this->assertEquals(['GET'], $resOut->getHeader('Allow'));
        $this->assertContains(
            $renderer->render($exception, false),
            (string)$resOut->getBody()
        );
    }

    public function testInvokeWithMatchingRoute()
    {
        $app = new App();
        $app->get('/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello');
            return $response;
        });

        // Prepare request and response objects
        $request = $this->createServerRequest('/foo');
        $response = $this->createResponse();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello', (string)$resOut->getBody());
    }

    public function testInvokeWithMatchingRouteWithSetArgument()
    {
        $app = new App();
        $app->get('/foo/bar', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write("Hello {$args['attribute']}");
            return $response;
        })->setArgument('attribute', 'world!');

        // Prepare request and response objects
        $request = $this->createServerRequest('/foo/bar');
        $response = $this->createResponse();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello world!', (string)$resOut->getBody());
    }

    public function testInvokeWithMatchingRouteWithSetArguments()
    {
        $app = new App();
        $app->get('/foo/bar', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write("Hello {$args['attribute1']} {$args['attribute2']}");
            return $response;
        })->setArguments(['attribute1' => 'there', 'attribute2' => 'world!']);

        // Prepare request and response objects
        $request = $this->createServerRequest('/foo/bar');
        $response = $this->createResponse();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello there world!', (string)$resOut->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameter()
    {
        $app = new App();
        $app->get('/foo/{name}', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write("Hello {$args['name']}");
            return $response;
        });

        // Prepare request and response objects
        $request = $this->createServerRequest('/foo/test!');
        $response = $this->createResponse();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello test!', (string)$resOut->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterRequestResponseArgStrategy()
    {
        $app = new App();
        $app->getRouter()->setDefaultInvocationStrategy(new RequestResponseArgs());
        $app->get('/foo/{name}', function (ServerRequestInterface $request, ResponseInterface $response, $name) {
            $response->getBody()->write("Hello {$name}");
            return $response;
        });

        // Prepare request and response objects
        $request = $this->createServerRequest('/foo/test!');
        $response = $this->createResponse();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello test!', (string)$resOut->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterOverwritesSetArgument()
    {
        $app = new App();
        $app->get('/foo/{name}', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write("Hello {$args['extra']} {$args['name']}");
            return $response;
        })->setArguments(['extra' => 'there', 'name' => 'world!']);

        // Prepare request and response objects
        $request = $this->createServerRequest('/foo/test!');
        $response = $this->createResponse();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello there test!', (string)$resOut->getBody());
    }

    /**
     * @expectedException \Slim\Exception\HttpNotFoundException
     */
    public function testInvokeWithoutMatchingRoute()
    {
        $app = new App();
        $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello');
            return $response;
        });

        // Prepare request and response objects
        $request = $this->createServerRequest('/foo');
        $response = $this->createResponse();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertAttributeEquals(404, 'status', $resOut);
    }

    public function testInvokeWithCallableRegisteredInContainer()
    {
        // Prepare request and response objects
        $request = $this->createServerRequest('/foo');
        $response = $this->createResponse();

        $mock = $this->getMockBuilder('StdClass')->setMethods(['bar'])->getMock();

        $pimple = new Pimple();
        $pimple['foo'] = function () use ($mock, $response) {
            $response->getBody()->write('Hello');
            $mock
                ->method('bar')
                ->willReturn($response);
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
        $request = $this->createServerRequest('/foo');
        $response = $this->createResponse();

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
        $request = $this->createServerRequest('/foo');
        $response = $this->createResponse();

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
        function handle(ServerRequestInterface $request, ResponseInterface $response)
        {
            $response->getBody()->write('foo');

            return $response;
        }
        // @codingStandardsIgnoreEnd

        $app->get('/foo', __NAMESPACE__ . '\handle');

        // Prepare request and response objects
        $request = $this->createServerRequest('/foo');
        $response = $this->createResponse();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertEquals('foo', (string)$resOut->getBody());
    }

    public function testCurrentRequestAttributesAreNotLostWhenAddingRouteArguments()
    {
        $app = new App();
        $app->get('/foo/{name}', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write($request->getAttribute('one') . $args['name']);
            return $response;
        });

        // Prepare request and response objects
        $request = $this->createServerRequest('/foo/rob');
        $request = $request->withAttribute("one", 1);
        $response = $this->createResponse();

        // Invoke app
        $resOut = $app($request, $response);
        $this->assertEquals('1rob', (string)$resOut->getBody());
    }

    public function testCurrentRequestAttributesAreNotLostWhenAddingRouteArgumentsRequestResponseArg()
    {
        $app = new App();
        $app->getRouter()->setDefaultInvocationStrategy(new RequestResponseArgs());
        $app->get('/foo/{name}', function (ServerRequestInterface $request, ResponseInterface $response, $name) {
            $response->getBody()->write($request->getAttribute('one') . $name);
            return $response;
        });

        // Prepare request and response objects
        $request = $this->createServerRequest('/foo/rob');
        $request = $request->withAttribute("one", 1);
        $response = $this->createResponse();

        // Invoke app
        $resOut = $app($request, $response);
        $this->assertEquals('1rob', (string)$resOut->getBody());
    }

    public function testRun()
    {
        $app = new App();
        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });

        $request = $this->createServerRequest('/');
        $response = $app->run($request, $this->getResponseFactory());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    // TODO: Re-add testUnsupportedMethodWithoutRoute

    // TODO: Re-add testUnsupportedMethodWithRoute

    public function testContainerSetToRoute()
    {
        // Prepare request and response objects
        $request = $this->createServerRequest('/foo');
        $response = $this->createResponse();

        $mock = new MockAction();

        $pimple = new Pimple();
        $pimple['foo'] = function () use ($mock) {
            return $mock;
        };

        $app = new App();
        $app->setContainer(new Psr11Container($pimple));

        /** @var Router $router */
        $router = $app->getRouter();
        $router->map(['get'], '/foo', 'foo:bar');

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals(json_encode(['name'=>'bar', 'arguments' => []]), (string)$resOut->getBody());
    }

    public function testAppIsARequestHandler()
    {
        $app = new App;
        $this->assertInstanceof('Psr\Http\Server\RequestHandlerInterface', $app);
    }

    protected function skipIfPhp70()
    {
        if (version_compare(PHP_VERSION, '7.0', '>=')) {
            $this->markTestSkipped("Test is for PHP 5.6 or lower");
        }
    }
}
