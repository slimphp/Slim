<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\CallableResolver;
use Slim\Error\Renderers\HtmlErrorRenderer;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouterInterface;
use Slim\Handlers\Strategies\RequestResponseArgs;
use Slim\Route;
use Slim\Router;
use Slim\Tests\Mocks\MockAction;
use Slim\Tests\Mocks\MockMiddleware;
use Slim\Tests\Mocks\MockMiddlewareWithoutInterface;

class AppTest extends TestCase
{
    public static function setupBeforeClass()
    {
        ini_set('error_log', tempnam(sys_get_temp_dir(), 'slim'));
    }

    public function testDoesNotUseContainerAsServiceLocator()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory, $containerProphecy->reveal());

        $containerProphecy->has(Argument::type('string'))->shouldNotHaveBeenCalled();
        $containerProphecy->get(Argument::type('string'))->shouldNotHaveBeenCalled();
    }

    /********************************************************************************
     * Getter methods
     *******************************************************************************/

    public function testGetContainer()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory, $containerProphecy->reveal());

        $this->assertEquals($containerProphecy->reveal(), $app->getContainer());
    }

    public function testGetCallableResolverReturnsInjectedInstance()
    {
        $responseFactory = $this->getResponseFactory();
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $app = new App($responseFactory, null, $callableResolverProphecy->reveal());

        $this->assertSame($callableResolverProphecy->reveal(), $app->getCallableResolver());
    }

    public function testCreatesCallableResolverWhenNull()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $callableResolver = new CallableResolver($containerProphecy->reveal());
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory, $containerProphecy->reveal(), null);

        $this->assertEquals($callableResolver, $app->getCallableResolver());
    }

    public function testGetRouterReturnsInjectedInstance()
    {
        $responseFactory = $this->getResponseFactory();
        $routerProphecy = $this->prophesize(RouterInterface::class);
        $app = new App($responseFactory, null, null, $routerProphecy->reveal());

        $this->assertSame($routerProphecy->reveal(), $app->getRouter());
    }

    public function testCreatesRouterWhenNullWithInjectedContainer()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $responseFactory = $this->getResponseFactory();
        $router = new Router($responseFactory, $callableResolverProphecy->reveal(), $containerProphecy->reveal(), null);
        $app = new App($responseFactory, $containerProphecy->reveal(), $callableResolverProphecy->reveal());

        $this->assertEquals($router, $app->getRouter());
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
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $route = $app->get($path, $callable);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeContains('GET', 'methods', $route);
    }

    public function testPostRoute()
    {
        $path = '/foo';
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            // Do something
        };
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $route = $app->post($path, $callable);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeContains('POST', 'methods', $route);
    }

    public function testPutRoute()
    {
        $path = '/foo';
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            // Do something
        };

        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $route = $app->put($path, $callable);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeContains('PUT', 'methods', $route);
    }

    public function testPatchRoute()
    {
        $path = '/foo';
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            // Do something
        };

        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $route = $app->patch($path, $callable);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeContains('PATCH', 'methods', $route);
    }

    public function testDeleteRoute()
    {
        $path = '/foo';
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            // Do something
        };

        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $route = $app->delete($path, $callable);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeContains('DELETE', 'methods', $route);
    }

    public function testOptionsRoute()
    {
        $path = '/foo';
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            // Do something
        };

        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $route = $app->options($path, $callable);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeContains('OPTIONS', 'methods', $route);
    }

    public function testAnyRoute()
    {
        $path = '/foo';
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            // Do something
        };

        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $route = $app->any($path, $callable);

        $this->assertInstanceOf(Route::class, $route);
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

        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $route = $app->map(['GET', 'POST'], $path, $callable);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeContains('GET', 'methods', $route);
        $this->assertAttributeContains('POST', 'methods', $route);
    }

    public function testMapRouteWithLowercaseMethod()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App($this->getResponseFactory());
        $route = $app->map(['get'], $path, $callable);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeContains('get', 'methods', $route);
    }

    public function testRedirectRoute()
    {
        $source = '/foo';
        $destination = '/bar';

        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $route = $app->redirect($source, $destination, 301);

        $this->assertInstanceOf(Route::class, $route);
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
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/новости', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello');
            return $response;
        });

        $request = $this->createServerRequest('/новости');
        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello', (string)$response->getBody());
    }

    /********************************************************************************
     * Route Patterns
     *******************************************************************************/

    public function testSegmentRouteThatDoesNotEndInASlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            // Do something
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo', 'pattern', $router->lookupRoute('route0'));
    }

    public function testSegmentRouteThatEndsInASlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/foo/', function (ServerRequestInterface $request, ResponseInterface $response) {
            // Do something
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testSegmentRouteThatDoesNotStartWithASlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            // Do something
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('foo', 'pattern', $router->lookupRoute('route0'));
    }

    public function testSingleSlashRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            // Do something
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('', function (ServerRequestInterface $request, ResponseInterface $response) {
            // Do something
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('', 'pattern', $router->lookupRoute('route0'));
    }

    /********************************************************************************
     * Route Groups
     *******************************************************************************/

    public function testGroupClosureIsBoundToThisClass()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $testCase = $this;
        $app->group('/foo', function (App $app) use ($testCase) {
            $testCase->assertSame($testCase, $this);
        });
    }

    public function testGroupSegmentWithSegmentRouteThatDoesNotEndInASlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/foo', function (App $app) {
            $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                // Do something
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithSegmentRouteThatEndsInASlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/foo', function (App $app) {
            $app->get('/bar/', function (ServerRequestInterface $request, ResponseInterface $response) {
                // Do something
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithSingleSlashRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/foo', function (App $app) {
            $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
                // Do something
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithEmptyRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/foo', function (App $app) {
            $app->get('', function (ServerRequestInterface $request, ResponseInterface $response) {
                // Do something
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo', 'pattern', $router->lookupRoute('route0'));
    }

    public function testTwoGroupSegmentsWithSingleSlashRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/foo', function (App $app) {
            $app->group('/baz', function (App $app) {
                $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/baz/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testTwoGroupSegmentsWithAnEmptyRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/foo', function (App $app) {
            $app->group('/baz', function (App $app) {
                $app->get('', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/baz', 'pattern', $router->lookupRoute('route0'));
    }

    public function testTwoGroupSegmentsWithSegmentRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/foo', function (App $app) {
            $app->group('/baz', function (App $app) {
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/baz/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testTwoGroupSegmentsWithSegmentRouteThatHasATrailingSlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/foo', function (App $app) {
            $app->group('/baz', function (App $app) {
                $app->get('/bar/', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/baz/bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithSingleSlashNestedGroupAndSegmentRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/foo', function (App $app) {
            $app->group('/', function (App $app) {
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo//bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithSingleSlashGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/foo', function (App $app) {
            $app->group('/', function (App $app) {
                $app->get('bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithEmptyNestedGroupAndSegmentRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/foo', function (App $app) {
            $app->group('', function (App $app) {
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/foo/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithEmptyNestedGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/foo', function (App $app) {
            $app->group('', function (App $app) {
                $app->get('bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/foobar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithSegmentRouteThatDoesNotEndInASlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/', function (App $app) {
            $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                // Do something
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('//bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithSegmentRouteThatEndsInASlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/', function (App $app) {
            $app->get('/bar/', function (ServerRequestInterface $request, ResponseInterface $response) {
                // Do something
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('//bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithSingleSlashRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/', function (App $app) {
            $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
                // Do something
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('//', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithEmptyRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/', function (App $app) {
            $app->get('', function (ServerRequestInterface $request, ResponseInterface $response) {
                // Do something
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithNestedGroupSegmentWithSingleSlashRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/', function (App $app) {
            $app->group('/baz', function (App $app) {
                $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('//baz/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithNestedGroupSegmentWithAnEmptyRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/', function (App $app) {
            $app->group('/baz', function (App $app) {
                $app->get('', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('//baz', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithNestedGroupSegmentWithSegmentRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/', function (App $app) {
            $app->group('/baz', function (App $app) {
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('//baz/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithNestedGroupSegmentWithSegmentRouteThatHasATrailingSlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/', function (App $app) {
            $app->group('/baz', function (App $app) {
                $app->get('/bar/', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('//baz/bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithSingleSlashNestedGroupAndSegmentRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/', function (App $app) {
            $app->group('/', function (App $app) {
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('///bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithSingleSlashGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/', function (App $app) {
            $app->group('/', function (App $app) {
                $app->get('bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('//bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithEmptyNestedGroupAndSegmentRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/', function (App $app) {
            $app->group('', function (App $app) {
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('//bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithEmptyNestedGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/', function (App $app) {
            $app->group('', function (App $app) {
                $app->get('bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithSegmentRouteThatDoesNotEndInASlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('', function (App $app) {
            $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                // Do something
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithSegmentRouteThatEndsInASlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('', function (App $app) {
            $app->get('/bar/', function (ServerRequestInterface $request, ResponseInterface $response) {
                // Do something
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithSingleSlashRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('', function (App $app) {
            $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
                // Do something
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithEmptyRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('', function (App $app) {
            $app->get('', function (ServerRequestInterface $request, ResponseInterface $response) {
                // Do something
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithNestedGroupSegmentWithSingleSlashRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('', function (App $app) {
            $app->group('/baz', function (App $app) {
                $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/baz/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithNestedGroupSegmentWithAnEmptyRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('', function (App $app) {
            $app->group('/baz', function (App $app) {
                $app->get('', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/baz', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithNestedGroupSegmentWithSegmentRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('', function (App $app) {
            $app->group('/baz', function (App $app) {
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/baz/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithNestedGroupSegmentWithSegmentRouteThatHasATrailingSlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('', function (App $app) {
            $app->group('/baz', function (App $app) {
                $app->get('/bar/', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/baz/bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithSingleSlashNestedGroupAndSegmentRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('', function (App $app) {
            $app->group('/', function (App $app) {
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('//bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithSingleSlashGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('', function (App $app) {
            $app->group('/', function (App $app) {
                $app->get('bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithEmptyNestedGroupAndSegmentRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('', function (App $app) {
            $app->group('', function (App $app) {
                $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithEmptyNestedGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('', function (App $app) {
            $app->group('', function (App $app) {
                $app->get('bar', function (ServerRequestInterface $request, ResponseInterface $response) {
                    // Do something
                });
            });
        });

        $router = $app->getRouter();
        $this->assertAttributeEquals('bar', 'pattern', $router->lookupRoute('route0'));
    }

    /********************************************************************************
     * Middleware
     *******************************************************************************/

    public function testAddMiddleware()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $called = 0;

        $app->add(function ($request, $handler) use (&$called, $responseFactory) {
            $called++;
            return $responseFactory->createResponse();
        });

        $request = $this->createServerRequest('/');
        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });
        $app->handle($request);

        $this->assertSame($called, 1);
    }

    public function testAddMiddlewareUsingDeferredResolution()
    {
        $responseFactory = $this->getResponseFactory();

        $mw = new MockMiddleware($responseFactory);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(MockMiddleware::class)->willReturn(true);
        $containerProphecy->get(MockMiddleware::class)->willReturn($mw);

        $app = new App($responseFactory, $containerProphecy->reveal());
        $app->add(MockMiddleware::class);

        $request = $this->createServerRequest('/');
        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });

        $response = $app->handle($request);
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testAddMiddlewareOnRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $appendToOutput = $request->getAttribute('appendToOutput');
            $appendToOutput('Center');
            return $response;
        })->add(function ($request, $handler) {
            $appendToOutput = $request->getAttribute('appendToOutput');
            $appendToOutput('In1');
            $response = $handler->handle($request);
            $appendToOutput('Out1');
            return $response;
        })->add(function ($request, $handler) {
            $appendToOutput = $request->getAttribute('appendToOutput');
            $appendToOutput('In2');
            $response = $handler->handle($request);
            $appendToOutput('Out2');
            return $response;
        });

        $output = '';
        $appendToOutput = function (string $value) use (&$output) {
            $output .= $value;
        };
        $request = $this->createServerRequest('/');
        $request = $request->withAttribute('appendToOutput', $appendToOutput);

        $app->run($request);

        $this->assertEquals('In2In1CenterOut1Out2', $output);
    }

    public function testAddMiddlewareOnRouteGroup()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/foo', function (App $app) {
            $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
                $appendToOutput = $request->getAttribute('appendToOutput');
                $appendToOutput('Center');
                return $response;
            });
        })->add(function ($request, $handler) {
            $appendToOutput = $request->getAttribute('appendToOutput');
            $appendToOutput('In1');
            $response = $handler->handle($request);
            $appendToOutput('Out1');
            return $response;
        })->add(function ($request, $handler) {
            $appendToOutput = $request->getAttribute('appendToOutput');
            $appendToOutput('In2');
            $response = $handler->handle($request);
            $appendToOutput('Out2');
            return $response;
        });

        $output = '';
        $appendToOutput = function (string $value) use (&$output) {
            $output .= $value;
        };
        $request = $this->createServerRequest('/foo/');
        $request = $request->withAttribute('appendToOutput', $appendToOutput);

        $app->run($request);

        $this->assertEquals('In2In1CenterOut1Out2', $output);
    }

    public function testAddMiddlewareOnTwoRouteGroup()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/foo', function (App $app) {
            $app->group('/baz', function (App $app) {
                $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
                    $appendToOutput = $request->getAttribute('appendToOutput');
                    $appendToOutput('Center');
                    return $response;
                });
            })->add(function ($request, $handler) {
                $appendToOutput = $request->getAttribute('appendToOutput');
                $appendToOutput('In2');
                $response = $handler->handle($request);
                $appendToOutput('Out2');
                return $response;
            });
        })->add(function ($request, $handler) {
            $appendToOutput = $request->getAttribute('appendToOutput');
            $appendToOutput('In1');
            $response = $handler->handle($request);
            $appendToOutput('Out1');
            return $response;
        });

        // Prepare request object
        $output = '';
        $appendToOutput = function (string $value) use (&$output) {
            $output .= $value;
        };
        $request = $this->createServerRequest('/foo/baz/');
        $request = $request->withAttribute('appendToOutput', $appendToOutput);

        $app->run($request);

        $this->assertEquals('In1In2CenterOut2Out1', $output);
    }

    public function testAddMiddlewareOnRouteAndOnTwoRouteGroup()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->group('/foo', function (App $app) {
            $app->group('/baz', function (App $app) {
                $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
                    $appendToOutput = $request->getAttribute('appendToOutput');
                    $appendToOutput('Center');
                    return $response;
                })->add(function ($request, $handler) {
                    $appendToOutput = $request->getAttribute('appendToOutput');
                    $appendToOutput('In3');
                    $response = $handler->handle($request);
                    $appendToOutput('Out3');
                    return $response;
                });
            })->add(function ($request, $handler) {
                $appendToOutput = $request->getAttribute('appendToOutput');
                $appendToOutput('In2');
                $response = $handler->handle($request);
                $appendToOutput('Out2');
                return $response;
            });
        })->add(function ($request, $handler) {
            $appendToOutput = $request->getAttribute('appendToOutput');
            $appendToOutput('In1');
            $response = $handler->handle($request);
            $appendToOutput('Out1');
            return $response;
        });

        $output = '';
        $appendToOutput = function (string $value) use (&$output) {
            $output .= $value;
        };
        $request = $this->createServerRequest('/foo/baz/');
        $request = $request->withAttribute('appendToOutput', $appendToOutput);

        $app->run($request);

        $this->assertEquals('In1In2In3CenterOut3Out2Out1', $output);
    }

    public function testAddMiddlewareAsStringNotImplementingInterfaceThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'A middleware must be an object/class name referencing an implementation of ' .
            'MiddlewareInterface or a callable with a matching signature.'
        );

        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->add(new MockMiddlewareWithoutInterface());
    }

    /********************************************************************************
     * Runner
     *******************************************************************************/

    /**
     * @expectedException \Slim\Exception\HttpMethodNotAllowedException
     */
    public function testInvokeReturnMethodNotAllowed()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello');
            return $response;
        });

        $request = $this->createServerRequest('/foo', 'POST');

        $exception = new HttpMethodNotAllowedException($request);
        $exception->setAllowedMethods(['GET']);

        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(405, (string)$response->getStatusCode());
        $this->assertEquals(['GET'], $response->getHeader('Allow'));
        $this->assertContains(
            (new HtmlErrorRenderer())->render($exception, false),
            (string)$response->getBody()
        );
    }

    public function testInvokeWithMatchingRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello');
            return $response;
        });

        $request = $this->createServerRequest('/foo');
        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello', (string)$response->getBody());
    }

    public function testInvokeWithMatchingRouteWithSetArgument()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/foo/bar', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write("Hello {$args['attribute']}");
            return $response;
        })->setArgument('attribute', 'world!');

        $request = $this->createServerRequest('/foo/bar');
        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello world!', (string)$response->getBody());
    }

    public function testInvokeWithMatchingRouteWithSetArguments()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/foo/bar', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write("Hello {$args['attribute1']} {$args['attribute2']}");
            return $response;
        })->setArguments(['attribute1' => 'there', 'attribute2' => 'world!']);

        $request = $this->createServerRequest('/foo/bar');
        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello there world!', (string)$response->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameter()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/foo/{name}', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write("Hello {$args['name']}");
            return $response;
        });

        $request = $this->createServerRequest('/foo/test!');
        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello test!', (string)$response->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterRequestResponseArgStrategy()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->getRouter()->setDefaultInvocationStrategy(new RequestResponseArgs());
        $app->get('/foo/{name}', function (ServerRequestInterface $request, ResponseInterface $response, $name) {
            $response->getBody()->write("Hello {$name}");
            return $response;
        });

        $request = $this->createServerRequest('/foo/test!');
        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello test!', (string)$response->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterOverwritesSetArgument()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/foo/{name}', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write("Hello {$args['extra']} {$args['name']}");
            return $response;
        })->setArguments(['extra' => 'there', 'name' => 'world!']);

        $request = $this->createServerRequest('/foo/test!');
        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello there test!', (string)$response->getBody());
    }

    /**
     * @expectedException \Slim\Exception\HttpNotFoundException
     */
    public function testInvokeWithoutMatchingRoute()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello');
            return $response;
        });

        $request = $this->createServerRequest('/foo');
        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertAttributeEquals(404, 'status', $response);
    }

    public function testInvokeWithCallableRegisteredInContainer()
    {
        $request = $this->createServerRequest('/foo');
        $response = $this->createResponse();
        $response->getBody()->write('Hello');

        $mock = $this->getMockBuilder('StdClass')->setMethods(['bar'])->getMock();
        $mock->method('bar')->willReturn($response);

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('foo')->willReturn(true);
        $containerProphecy->get('foo')->willReturn($mock);

        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory, $containerProphecy->reveal());
        $app->get('/foo', 'foo:bar');

        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello', (string)$response->getBody());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvokeWithNonExistentMethodOnCallableRegisteredInContainer()
    {
        $request = $this->createServerRequest('/foo');

        $mock = $this->getMockBuilder('StdClass')->getMock();
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('foo')->willReturn(true);
        $containerProphecy->get('foo')->willReturn($mock);

        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory, $containerProphecy->reveal());
        $app->get('/foo', 'foo:bar');

        $app->handle($request);
    }

    public function testInvokeWithCallableInContainerViaMagicMethod()
    {
        $request = $this->createServerRequest('/foo');

        $mock = new MockAction();
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('foo')->willReturn(true);
        $containerProphecy->get('foo')->willReturn($mock);

        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory, $containerProphecy->reveal());
        $app->get('/foo', 'foo:bar');

        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(json_encode(['name'=>'bar', 'arguments' => []]), (string)$response->getBody());
    }

    public function testInvokeFunctionName()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);

        // @codingStandardsIgnoreStart
        function handle(ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('foo');
            return $response;
        }
        // @codingStandardsIgnoreEnd

        $app->get('/foo', __NAMESPACE__ . '\handle');

        $request = $this->createServerRequest('/foo');
        $response = $app->handle($request);

        $this->assertEquals('foo', (string)$response->getBody());
    }

    public function testCurrentRequestAttributesAreNotLostWhenAddingRouteArguments()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/foo/{name}', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write($request->getAttribute('one') . $args['name']);
            return $response;
        });

        $request = $this->createServerRequest('/foo/rob')->withAttribute("one", 1);
        $response = $app->handle($request);

        $this->assertEquals('1rob', (string)$response->getBody());
    }

    public function testCurrentRequestAttributesAreNotLostWhenAddingRouteArgumentsRequestResponseArg()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->getRouter()->setDefaultInvocationStrategy(new RequestResponseArgs());
        $app->get('/foo/{name}', function (ServerRequestInterface $request, ResponseInterface $response, $name) {
            $response->getBody()->write($request->getAttribute('one') . $name);
            return $response;
        });

        $request = $this->createServerRequest('/foo/rob')->withAttribute("one", 1);
        $response = $app->handle($request);

        $this->assertEquals('1rob', (string)$response->getBody());
    }

    public function testRun()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');
            return $response;
        });

        $request = $this->createServerRequest('/');
        $app->run($request);

        $this->expectOutputString('Hello World');
    }

    public function testHandleReturnsEmptyResponseBodyWithHeadRequestMethod()
    {
        $called = 0;
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) use (&$called) {
            $called += 1;
            $response->getBody()->write('Hello World');
            return $response;
        });

        $request = $this->createServerRequest('/', 'HEAD');
        $response = $app->handle($request);

        $this->assertEquals(1, $called);
        $this->assertEmpty((string) $response->getBody());
    }

    public function testCanBeReExecutedRecursivelyDuringDispatch()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->add(function (ServerRequestInterface $request) use ($app, $responseFactory) {
            if ($request->hasHeader('X-NESTED')) {
                return $responseFactory->createResponse(204)->withAddedHeader('X-TRACE', 'nested');
            }

            // Perform the subrequest, by invoking App::handle (again)
            $response = $app->handle($request->withAddedHeader('X-NESTED', '1'));

            return $response->withAddedHeader('X-TRACE', 'outer');
        })->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $response = $handler->handle($request);
            $response->getBody()->write('1');
            return $response;
        });

        $request = $this->createServerRequest('/');
        $response = $app->handle($request);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame(['nested', 'outer'], $response->getHeader('X-TRACE'));
        $this->assertEquals('11', (string) $response->getBody());
    }

    // TODO: Re-add testUnsupportedMethodWithoutRoute

    // TODO: Re-add testUnsupportedMethodWithRoute

    public function testContainerSetToRoute()
    {
        $mock = new MockAction();
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('foo')->willReturn(true);
        $containerProphecy->get('foo')->willReturn($mock);

        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory, $containerProphecy->reveal());

        $router = $app->getRouter();
        $router->map(['GET'], '/foo', 'foo:bar');

        $request = $this->createServerRequest('/foo');
        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(json_encode(['name'=>'bar', 'arguments' => []]), (string)$response->getBody());
    }

    public function testAppIsARequestHandler()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $this->assertInstanceOf(RequestHandlerInterface::class, $app);
    }

    public function testInvokeSequentialProccessToAPathWithOptionalArgsAndWithoutOptionalArgs()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/foo[/{bar}]', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write((string) count($args));
            return $response;
        });

        $request = $this->createServerRequest('/foo/bar', 'GET');
        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('1', (string) $response->getBody());

        $request = $this->createServerRequest('/foo', 'GET');
        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('0', (string) $response->getBody());
    }

    public function testInvokeSequentialProccessToAPathWithOptionalArgsAndWithoutOptionalArgsAndKeepSetedArgs()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $app->get('/foo[/{bar}]', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write((string) count($args));
            return $response;
        })->setArgument('baz', 'quux');

        $request = $this->createServerRequest('/foo/bar', 'GET');
        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('2', (string) $response->getBody());

        $request = $this->createServerRequest('/foo', 'GET');
        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('1', (string) $response->getBody());
    }

    public function testInvokeSequentialProccessAfterAddingAnotherRouteArgument()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $route = $app->get('/foo[/{bar}]', function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            $args
        ) {
            $response->getBody()->write((string) count($args));
            return $response;
        })->setArgument('baz', 'quux');

        $request = $this->createServerRequest('/foo/bar', 'GET');
        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('2', (string) $response->getBody());

        // Add Another Argument To Route
        $route->setArgument('one', '1');

        $request = $this->createServerRequest('/foo/bar', 'GET');
        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('3', (string) $response->getBody());
    }
}
