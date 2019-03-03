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
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionProperty;
use RuntimeException;
use Slim\App;
use Slim\CallableResolver;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\Strategies\RequestResponseArgs;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouterInterface;
use Slim\Route;
use Slim\Router;
use Slim\Tests\Mocks\MockAction;
use stdClass;

class AppTest extends TestCase
{
    public static function setupBeforeClass()
    {
        ini_set('error_log', tempnam(sys_get_temp_dir(), 'slim'));
    }

    public function testDoesNotUseContainerAsServiceLocator()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $app = new App($responseFactoryProphecy->reveal(), $containerProphecy->reveal());

        $containerProphecy->has(Argument::type('string'))->shouldNotHaveBeenCalled();
        $containerProphecy->get(Argument::type('string'))->shouldNotHaveBeenCalled();
    }

    /********************************************************************************
     * Getter methods
     *******************************************************************************/

    public function testGetContainer()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $app = new App($responseFactoryProphecy->reveal(), $containerProphecy->reveal());

        $this->assertSame($containerProphecy->reveal(), $app->getContainer());
    }

    public function testGetCallableResolverReturnsInjectedInstance()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $app = new App($responseFactoryProphecy->reveal(), null, $callableResolverProphecy->reveal());

        $this->assertSame($callableResolverProphecy->reveal(), $app->getCallableResolver());
    }

    public function testCreatesCallableResolverWhenNull()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $callableResolver = new CallableResolver($containerProphecy->reveal());
        $app = new App($responseFactoryProphecy->reveal(), $containerProphecy->reveal(), null);

        $this->assertEquals($callableResolver, $app->getCallableResolver());
    }

    public function testGetRouterReturnsInjectedInstance()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $routerProphecy = $this->prophesize(RouterInterface::class);
        $app = new App($responseFactoryProphecy->reveal(), null, null, $routerProphecy->reveal());

        $this->assertSame($routerProphecy->reveal(), $app->getRouter());
    }

    public function testCreatesRouterWhenNullWithInjectedContainer()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $router = new Router(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            $containerProphecy->reveal(),
            null
        );
        $app = new App(
            $responseFactoryProphecy->reveal(),
            $containerProphecy->reveal(),
            $callableResolverProphecy->reveal()
        );

        $this->assertEquals($router, $app->getRouter());
    }

    /********************************************************************************
     * Router proxy methods
     *******************************************************************************/

    public function testGetRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $route = $app->get('/', function () {
        });

        $methodsProperty = new ReflectionProperty(Route::class, 'methods');
        $methodsProperty->setAccessible(true);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(['GET'], $methodsProperty->getValue($route));
    }

    public function testPostRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $route = $app->post('/', function () {
        });

        $methodsProperty = new ReflectionProperty(Route::class, 'methods');
        $methodsProperty->setAccessible(true);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(['POST'], $methodsProperty->getValue($route));
    }

    public function testPutRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $route = $app->put('/', function () {
        });

        $methodsProperty = new ReflectionProperty(Route::class, 'methods');
        $methodsProperty->setAccessible(true);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(['PUT'], $methodsProperty->getValue($route));
    }

    public function testPatchRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $route = $app->patch('/', function () {
        });

        $methodsProperty = new ReflectionProperty(Route::class, 'methods');
        $methodsProperty->setAccessible(true);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(['PATCH'], $methodsProperty->getValue($route));
    }

    public function testDeleteRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $route = $app->delete('/', function () {
        });

        $methodsProperty = new ReflectionProperty(Route::class, 'methods');
        $methodsProperty->setAccessible(true);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(['DELETE'], $methodsProperty->getValue($route));
    }

    public function testOptionsRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $route = $app->options('/', function () {
        });

        $methodsProperty = new ReflectionProperty(Route::class, 'methods');
        $methodsProperty->setAccessible(true);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(['OPTIONS'], $methodsProperty->getValue($route));
    }

    public function testAnyRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $route = $app->any('/', function () {
        });

        $methodsProperty = new ReflectionProperty(Route::class, 'methods');
        $methodsProperty->setAccessible(true);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $methodsProperty->getValue($route));
    }

    public function testMapRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $route = $app->map(['GET', 'POST'], '/', function () {
        });

        $methodsProperty = new ReflectionProperty(Route::class, 'methods');
        $methodsProperty->setAccessible(true);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(['GET', 'POST'], $methodsProperty->getValue($route));
    }

    public function testMapRouteWithLowercaseMethod()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $route = $app->map(['get'], '/', function () {
        });

        $methodsProperty = new ReflectionProperty(Route::class, 'methods');
        $methodsProperty->setAccessible(true);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(['get'], $methodsProperty->getValue($route));
    }

    public function testRedirectRoute()
    {
        $from = '/from';
        $to = '/to';

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse(Argument::any())->will(function ($args) use ($responseProphecy, $to) {
            $responseProphecy->getStatusCode()->willReturn(isset($args[0]) ? $args[0] : 200);
            $responseProphecy->getHeaderLine('Location')->willReturn($to);
            $responseProphecy->withHeader(
                Argument::type('string'),
                Argument::type('string')
            )->will(function ($args) {
                $clone = clone $this;
                $clone->getHeader($args[0])->willReturn($args[1]);
                return $clone;
            });
            return $responseProphecy->reveal();
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn($from);
        $uriProphecy->__toString()->willReturn($to);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());

        // Test with provided status code
        $route = $app->redirect($from, $to, 301);
        $response = $route->run($requestProphecy->reveal());
        $this->assertEquals(301, $response->getStatusCode());

        // Test with default App::redirect() status code
        $route = $app->redirect($from, $to);
        $response = $route->run($requestProphecy->reveal());
        $this->assertEquals(302, $response->getStatusCode());

        $methodsProperty = new ReflectionProperty(Route::class, 'methods');
        $methodsProperty->setAccessible(true);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(['GET'], $methodsProperty->getValue($route));
    }

    public function testRouteWithInternationalCharacters()
    {
        $path = '/новости';

        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('Hello World');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get($path, function () use ($responseProphecy) {
            return $responseProphecy->reveal();
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn($path);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });
        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello World', (string) $response->getBody());
    }

    /********************************************************************************
     * Route Patterns
     *******************************************************************************/

    public function testSegmentRouteThatDoesNotEndInASlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/foo', function () {
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo', $patternProperty->getValue($route));
    }

    public function testSegmentRouteThatEndsInASlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/foo/', function () {
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo/', $patternProperty->getValue($route));
    }

    public function testSegmentRouteThatDoesNotStartWithASlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('foo', function () {
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('foo', $patternProperty->getValue($route));
    }

    public function testSingleSlashRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/', function () {
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/', $patternProperty->getValue($route));
    }

    public function testEmptyRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('', function () {
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('', $patternProperty->getValue($route));
    }

    /********************************************************************************
     * Route Groups
     *******************************************************************************/

    public function testGroupClosureIsBoundToThisClass()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());

        $testCase = $this;
        $app->group('/foo', function () use ($testCase) {
            $testCase->assertSame($testCase, $this);
        });
    }

    public function testGroupSegmentWithSegmentRouteThatDoesNotEndInASlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/foo', function (App $app) {
            $app->get('/bar', function () {
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo/bar', $patternProperty->getValue($route));
    }

    public function testGroupSegmentWithSegmentRouteThatEndsInASlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/foo', function (App $app) {
            $app->get('/bar/', function () {
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo/bar/', $patternProperty->getValue($route));
    }

    public function testGroupSegmentWithSingleSlashRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/foo', function (App $app) {
            $app->get('/', function () {
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo/', $patternProperty->getValue($route));
    }

    public function testGroupSegmentWithEmptyRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/foo', function (App $app) {
            $app->get('', function () {
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo', $patternProperty->getValue($route));
    }

    public function testTwoGroupSegmentsWithSingleSlashRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/foo', function (App $app) {
            $app->group('/bar', function (App $app) {
                $app->get('/', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo/bar/', $patternProperty->getValue($route));
    }

    public function testTwoGroupSegmentsWithAnEmptyRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/foo', function (App $app) {
            $app->group('/bar', function (App $app) {
                $app->get('', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo/bar', $patternProperty->getValue($route));
    }

    public function testTwoGroupSegmentsWithSegmentRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/foo', function (App $app) {
            $app->group('/bar', function (App $app) {
                $app->get('/baz', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo/bar/baz', $patternProperty->getValue($route));
    }

    public function testTwoGroupSegmentsWithSegmentRouteThatHasATrailingSlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/foo', function (App $app) {
            $app->group('/bar', function (App $app) {
                $app->get('/baz/', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo/bar/baz/', $patternProperty->getValue($route));
    }

    public function testGroupSegmentWithSingleSlashNestedGroupAndSegmentRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/foo', function (App $app) {
            $app->group('/', function (App $app) {
                $app->get('/bar', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo//bar', $patternProperty->getValue($route));
    }

    public function testGroupSegmentWithSingleSlashGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/foo', function (App $app) {
            $app->group('/', function (App $app) {
                $app->get('bar', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo/bar', $patternProperty->getValue($route));
    }

    public function testGroupSegmentWithEmptyNestedGroupAndSegmentRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/foo', function (App $app) {
            $app->group('', function (App $app) {
                $app->get('/bar', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo/bar', $patternProperty->getValue($route));
    }

    public function testGroupSegmentWithEmptyNestedGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/foo', function (App $app) {
            $app->group('', function (App $app) {
                $app->get('bar', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foobar', $patternProperty->getValue($route));
    }

    public function testGroupSingleSlashWithSegmentRouteThatDoesNotEndInASlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/', function (App $app) {
            $app->get('/foo', function () {
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('//foo', $patternProperty->getValue($route));
    }

    public function testGroupSingleSlashWithSegmentRouteThatEndsInASlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/', function (App $app) {
            $app->get('/foo/', function () {
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('//foo/', $patternProperty->getValue($route));
    }

    public function testGroupSingleSlashWithSingleSlashRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/', function (App $app) {
            $app->get('/', function () {
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('//', $patternProperty->getValue($route));
    }

    public function testGroupSingleSlashWithEmptyRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/', function (App $app) {
            $app->get('', function () {
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/', $patternProperty->getValue($route));
    }

    public function testGroupSingleSlashWithNestedGroupSegmentWithSingleSlashRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/', function (App $app) {
            $app->group('/foo', function (App $app) {
                $app->get('/', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('//foo/', $patternProperty->getValue($route));
    }

    public function testGroupSingleSlashWithNestedGroupSegmentWithAnEmptyRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/', function (App $app) {
            $app->group('/foo', function (App $app) {
                $app->get('', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('//foo', $patternProperty->getValue($route));
    }

    public function testGroupSingleSlashWithNestedGroupSegmentWithSegmentRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/', function (App $app) {
            $app->group('/foo', function (App $app) {
                $app->get('/bar', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('//foo/bar', $patternProperty->getValue($route));
    }

    public function testGroupSingleSlashWithNestedGroupSegmentWithSegmentRouteThatHasATrailingSlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/', function (App $app) {
            $app->group('/foo', function (App $app) {
                $app->get('/bar/', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('//foo/bar/', $patternProperty->getValue($route));
    }

    public function testGroupSingleSlashWithSingleSlashNestedGroupAndSegmentRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/', function (App $app) {
            $app->group('/', function (App $app) {
                $app->get('/foo', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('///foo', $patternProperty->getValue($route));
    }

    public function testGroupSingleSlashWithSingleSlashGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/', function (App $app) {
            $app->group('/', function (App $app) {
                $app->get('foo', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('//foo', $patternProperty->getValue($route));
    }

    public function testGroupSingleSlashWithEmptyNestedGroupAndSegmentRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/', function (App $app) {
            $app->group('', function (App $app) {
                $app->get('/foo', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('//foo', $patternProperty->getValue($route));
    }

    public function testGroupSingleSlashWithEmptyNestedGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/', function (App $app) {
            $app->group('', function (App $app) {
                $app->get('foo', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo', $patternProperty->getValue($route));
    }

    public function testEmptyGroupWithSegmentRouteThatDoesNotEndInASlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('', function (App $app) {
            $app->get('/foo', function () {
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo', $patternProperty->getValue($route));
    }

    public function testEmptyGroupWithSegmentRouteThatEndsInASlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('', function (App $app) {
            $app->get('/foo/', function () {
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo/', $patternProperty->getValue($route));
    }

    public function testEmptyGroupWithSingleSlashRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('', function (App $app) {
            $app->get('/', function () {
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/', $patternProperty->getValue($route));
    }

    public function testEmptyGroupWithEmptyRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('', function (App $app) {
            $app->get('', function () {
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('', $patternProperty->getValue($route));
    }

    public function testEmptyGroupWithNestedGroupSegmentWithSingleSlashRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('', function (App $app) {
            $app->group('/foo', function (App $app) {
                $app->get('/', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo/', $patternProperty->getValue($route));
    }

    public function testEmptyGroupWithNestedGroupSegmentWithAnEmptyRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('', function (App $app) {
            $app->group('/foo', function (App $app) {
                $app->get('', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo', $patternProperty->getValue($route));
    }

    public function testEmptyGroupWithNestedGroupSegmentWithSegmentRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('', function (App $app) {
            $app->group('/foo', function (App $app) {
                $app->get('/bar', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo/bar', $patternProperty->getValue($route));
    }

    public function testEmptyGroupWithNestedGroupSegmentWithSegmentRouteThatHasATrailingSlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('', function (App $app) {
            $app->group('/foo', function (App $app) {
                $app->get('/bar/', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo/bar/', $patternProperty->getValue($route));
    }

    public function testEmptyGroupWithSingleSlashNestedGroupAndSegmentRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('', function (App $app) {
            $app->group('/', function (App $app) {
                $app->get('/foo', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('//foo', $patternProperty->getValue($route));
    }

    public function testEmptyGroupWithSingleSlashGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('', function (App $app) {
            $app->group('/', function (App $app) {
                $app->get('foo', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo', $patternProperty->getValue($route));
    }

    public function testEmptyGroupWithEmptyNestedGroupAndSegmentRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('', function (App $app) {
            $app->group('', function (App $app) {
                $app->get('/foo', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('/foo', $patternProperty->getValue($route));
    }

    public function testEmptyGroupWithEmptyNestedGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->group('', function (App $app) {
            $app->group('', function (App $app) {
                $app->get('foo', function () {
                });
            });
        });

        $router = $app->getRouter();
        $route = $router->lookupRoute('route0');

        $patternProperty = new ReflectionProperty(Route::class, 'pattern');
        $patternProperty->setAccessible(true);

        $this->assertEquals('foo', $patternProperty->getValue($route));
    }

    /********************************************************************************
     * Middleware
     *******************************************************************************/

    public function testAddMiddleware()
    {
        $called = 0;

        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('Hello World');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());

        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(Argument::cetera())->will(function () use (&$called, $responseProphecy) {
            $called++;
            return $responseProphecy->reveal();
        });

        $middlewareProphecy2 = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy2->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )->will(function ($args) use (&$called, $responseProphecy) {
            $called++;

            /** @var ServerRequestInterface $request */
            $request = $args[0];

            /** @var RequestHandlerInterface $handler */
            $handler = $args[1];

            return $handler->handle($request);
        });

        $app->add($middlewareProphecy->reveal());
        $app->add($middlewareProphecy2->reveal());
        $app->get('/', function ($request, $response) {
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertEquals(2, $called);
        $this->assertSame($responseProphecy->reveal(), $response);
    }

    public function testAddMiddlewareUsingDeferredResolution()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('Hello World');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(Argument::cetera())->willReturn($responseProphecy->reveal());

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('middleware')->willReturn(true);
        $containerProphecy->get('middleware')->willReturn($middlewareProphecy);

        $app = new App($responseFactoryProphecy->reveal(), $containerProphecy->reveal());
        $app->add('middleware');
        $app->get('/', function ($request, ResponseInterface $response) {
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());

        $response = $app->handle($requestProphecy->reveal());
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testAddMiddlewareOnRoute()
    {
        $output = '';

        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('Hello World');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )->will(function ($args) use (&$output) {
            /** @var ServerRequestInterface $request */
            $request = $args[0];

            /** @var RequestHandlerInterface $handler */
            $handler = $args[1];

            $output .= 'In1';

            /** @var ResponseInterface $response */
            $response = $handler->handle($request);

            $output .= 'Out1';

            return $response;
        });

        $middlewareProphecy2 = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy2->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )->will(function ($args) use (&$output) {
            /** @var ServerRequestInterface $request */
            $request = $args[0];

            /** @var RequestHandlerInterface $handler */
            $handler = $args[1];

            $output .= 'In2';

            /** @var ResponseInterface $response */
            $response = $handler->handle($request);

            $output .= 'Out2';

            return $response;
        });

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/', function ($request, ResponseInterface $response) use (&$output) {
            $output .= 'Center';
            return $response;
        })
            ->add($middlewareProphecy->reveal())
            ->add($middlewareProphecy2->reveal());

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $app->handle($requestProphecy->reveal());

        $this->assertEquals('In2In1CenterOut1Out2', $output);
    }

    public function testAddMiddlewareOnRouteGroup()
    {
        $output = '';

        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('Hello World');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )->will(function ($args) use (&$output) {
            /** @var ServerRequestInterface $request */
            $request = $args[0];

            /** @var RequestHandlerInterface $handler */
            $handler = $args[1];

            $output .= 'In1';

            /** @var ResponseInterface $response */
            $response = $handler->handle($request);

            $output .= 'Out1';

            return $response;
        });

        $middlewareProphecy2 = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy2->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )->will(function ($args) use (&$output) {
            /** @var ServerRequestInterface $request */
            $request = $args[0];

            /** @var RequestHandlerInterface $handler */
            $handler = $args[1];

            $output .= 'In2';

            /** @var ResponseInterface $response */
            $response = $handler->handle($request);

            $output .= 'Out2';

            return $response;
        });

        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/foo', function (App $app) use (&$output) {
            $app->get('/bar', function ($request, ResponseInterface $response) use (&$output) {
                $output .= 'Center';
                return $response;
            });
        })
            ->add($middlewareProphecy->reveal())
            ->add($middlewareProphecy2->reveal());

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/foo/bar');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $app->handle($requestProphecy->reveal());

        $this->assertEquals('In2In1CenterOut1Out2', $output);
    }

    public function testAddMiddlewareOnTwoRouteGroup()
    {
        $output = '';

        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('Hello World');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )->will(function ($args) use (&$output) {
            /** @var ServerRequestInterface $request */
            $request = $args[0];

            /** @var RequestHandlerInterface $handler */
            $handler = $args[1];

            $output .= 'In1';

            /** @var ResponseInterface $response */
            $response = $handler->handle($request);

            $output .= 'Out1';

            return $response;
        });

        $middlewareProphecy2 = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy2->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )->will(function ($args) use (&$output) {
            /** @var ServerRequestInterface $request */
            $request = $args[0];

            /** @var RequestHandlerInterface $handler */
            $handler = $args[1];

            $output .= 'In2';

            /** @var ResponseInterface $response */
            $response = $handler->handle($request);

            $output .= 'Out2';

            return $response;
        });

        $middlewareProphecy3 = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy3->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )->will(function ($args) use (&$output) {
            /** @var ServerRequestInterface $request */
            $request = $args[0];

            /** @var RequestHandlerInterface $handler */
            $handler = $args[1];

            $output .= 'In3';

            /** @var ResponseInterface $response */
            $response = $handler->handle($request);

            $output .= 'Out3';

            return $response;
        });

        $app = new App($responseFactoryProphecy->reveal());
        $app->group('/foo', function (App $app) use ($middlewareProphecy2, $middlewareProphecy3, &$output) {
            $app->group('/bar', function (App $app) use ($middlewareProphecy3, &$output) {
                $app->get('/baz', function ($request, ResponseInterface $response) use (&$output) {
                    $output .= 'Center';
                    return $response;
                })->add($middlewareProphecy3->reveal());
            })->add($middlewareProphecy2->reveal());
        })->add($middlewareProphecy->reveal());

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/foo/bar/baz');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $app->handle($requestProphecy->reveal());

        $this->assertEquals('In1In2In3CenterOut3Out2Out1', $output);
    }

    public function testAddMiddlewareAsStringNotImplementingInterfaceThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'A middleware must be an object/class name referencing an implementation of ' .
            'MiddlewareInterface or a callable with a matching signature.'
        );

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->add(new stdClass());
    }

    /********************************************************************************
     * Runner
     *******************************************************************************/

    public function testInvokeReturnMethodNotAllowed()
    {
        $this->expectException(HttpMethodNotAllowedException::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/', function () {
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('POST');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $app->handle($requestProphecy->reveal());
    }

    public function testInvokeWithMatchingRoute()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('Hello World');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/', function ($request, $response) {
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello World', (string) $response->getBody());
    }

    public function testInvokeWithMatchingRouteWithSetArgument()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/', function ($request, ResponseInterface $response, $args) {
            $response->getBody()->write("Hello {$args['name']}");
            return $response;
        })->setArgument('name', 'World');

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello World', (string) $response->getBody());
    }

    public function testInvokeWithMatchingRouteWithSetArguments()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/', function ($request, ResponseInterface $response, $args) {
            $response->getBody()->write("{$args['greeting']} {$args['name']}");
            return $response;
        })->setArguments(['greeting' => 'Hello', 'name' => 'World']);

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello World', (string) $response->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameter()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/Hello/{name}', function ($request, ResponseInterface $response, $args) {
            $response->getBody()->write("Hello {$args['name']}");
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello World', (string) $response->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterRequestResponseArgStrategy()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->getRouter()->setDefaultInvocationStrategy(new RequestResponseArgs());
        $app->get('/Hello/{name}', function ($request, ResponseInterface $response, $name) {
            $response->getBody()->write("Hello {$name}");
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello World', (string) $response->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterOverwritesSetArgument()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/Hello/{name}', function ($request, ResponseInterface $response, $args) {
            $response->getBody()->write("Hello {$args['name']}");
            return $response;
        })->setArgument('name', 'World!');

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello World', (string) $response->getBody());
    }

    public function testInvokeWithoutMatchingRoute()
    {
        $this->expectException(HttpNotFoundException::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $app->handle($requestProphecy->reveal());
    }

    public function testInvokeWithCallableRegisteredInContainer()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('Hello World');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $handler = new Class {
            public function foo(ServerRequestInterface $request, ResponseInterface $response) {
                return $response;
            }
        };

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('handler')->willReturn(true);
        $containerProphecy->get('handler')->willReturn($handler);

        $app = new App($responseFactoryProphecy->reveal(), $containerProphecy->reveal());
        $app->get('/', 'handler:foo');

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello World', (string) $response->getBody());
    }

    public function testInvokeWithNonExistentMethodOnCallableRegisteredInContainer()
    {
        $this->expectException(RuntimeException::class);

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $handler = new Class {
        };

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('handler')->willReturn(true);
        $containerProphecy->get('handler')->willReturn($handler);

        $app = new App($responseFactoryProphecy->reveal(), $containerProphecy->reveal());
        $app->get('/', 'handler:method_does_not_exist');

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());

        $app->handle($requestProphecy->reveal());
    }

    public function testInvokeWithCallableInContainerViaCallMagicMethod()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $mockAction = new MockAction();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('handler')->willReturn(true);
        $containerProphecy->get('handler')->willReturn($mockAction);

        $app = new App($responseFactoryProphecy->reveal(), $containerProphecy->reveal());
        $app->get('/', 'handler:foo');

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal());

        $expectedPayload = json_encode(['name'=>'foo', 'arguments' => []]);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals($expectedPayload, (string) $response->getBody());
    }

    public function testInvokeFunctionName()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        // @codingStandardsIgnoreStart
        function handle($request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');
            return $response;
        }
        // @codingStandardsIgnoreEnd

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/', __NAMESPACE__ . '\handle');

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello World', (string) $response->getBody());
    }

    public function testCurrentRequestAttributesAreNotLostWhenAddingRouteArguments()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/Hello/{name}', function ($request, ResponseInterface $response, $args) {
            $response->getBody()->write($request->getAttribute('greeting') . ' ' . $args['name']);
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal()->withAttribute('greeting', 'Hello'));

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello World', (string) $response->getBody());
    }

    public function testCurrentRequestAttributesAreNotLostWhenAddingRouteArgumentsRequestResponseArg()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->getRouter()->setDefaultInvocationStrategy(new RequestResponseArgs());
        $app->get('/Hello/{name}', function ($request, ResponseInterface $response, $name) {
            $response->getBody()->write($request->getAttribute('greeting') . ' ' . $name);
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal()->withAttribute('greeting', 'Hello'));

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Hello World', (string) $response->getBody());
    }

    public function testRun()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
        });
        $streamProphecy->read('11')->will(function () {
            return $this->reveal()->__toString();
        });
        $streamProphecy->eof()->will(function () {
            $this->eof()->willReturn(true);
            return false;
        });
        $streamProphecy->isSeekable()->willReturn(true);
        $streamProphecy->rewind()->willReturn(true);

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());
        $responseProphecy->getStatusCode()->willReturn(200);
        $responseProphecy->getHeaders()->willReturn(['Content-Length' => ['11']]);
        $responseProphecy->getProtocolVersion()->willReturn('1.1');
        $responseProphecy->getReasonPhrase()->willReturn('');
        $responseProphecy->getHeaderLine('Content-Length')->willReturn('11');

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/', function ($request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $app->run($requestProphecy->reveal());

        $this->expectOutputString('Hello World');
    }

    public function testHandleReturnsEmptyResponseBodyWithHeadRequestMethod()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());
        $responseProphecy->withBody(Argument::any())->will(function ($args) use ($streamProphecy) {
            $streamProphecy->__toString()->willReturn('');
            $clone = clone $this;
            $clone->getBody()->willReturn($args[0]);
            return $clone;
        });

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $called = 0;
        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/', function ($request, ResponseInterface $response) use (&$called) {
            $called++;
            $response->getBody()->write('Hello World');
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('HEAD');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertEquals(1, $called);
        $this->assertEmpty((string) $response->getBody());
    }

    public function testCanBeReExecutedRecursivelyDuringDispatch()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
        });

        $responseHeaders = [];
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());
        $responseProphecy->getStatusCode()->willReturn(200);
        $responseProphecy->getHeader(Argument::type('string'))->will(function ($args) use (&$responseHeaders) {
            return $responseHeaders[$args[0]];
        });
        $responseProphecy->withAddedHeader(
            Argument::type('string'),
            Argument::type('string')
        )->will(function ($args) use (&$responseHeaders) {
            $key = $args[0];
            $value = $args[1];
            if (!isset($responseHeaders[$key])) {
                $responseHeaders[$key] = [];
            }
            $responseHeaders[$key][] = $value;
            return $this;
        });

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy
            ->createResponse(Argument::type('integer'))
            ->will(function ($args) use ($responseProphecy) {
                $clone = clone $responseProphecy;
                $clone->getStatusCode()->willReturn($args[0]);
                return $clone;
            });

        $app = new App($responseFactoryProphecy->reveal());

        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )->will(function ($args) use ($app, $responseFactoryProphecy) {
            /** @var ServerRequestInterface $request */
            $request = $args[0];

            if ($request->hasHeader('X-NESTED')) {
                return $responseFactoryProphecy
                    ->reveal()
                    ->createResponse(204)
                    ->withAddedHeader('X-TRACE', 'nested');
            }

            /** @var ResponseInterface $response */
            $response = $app->handle($request->withAddedHeader('X-NESTED', '1'));
            $response = $response->withAddedHeader('X-TRACE', 'outer');

            return $response;
        });

        $middlewareProphecy2 = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy2->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )->will(function ($args) use ($app) {
            /** @var ServerRequestInterface $request */
            $request = $args[0];

            /** @var RequestHandlerInterface $handler */
            $handler = $args[1];

            /** @var ResponseInterface $response */
            $response = $handler->handle($request);
            $response->getBody()->write('1');

            return $response;
        });

        $app
            ->add($middlewareProphecy->reveal())
            ->add($middlewareProphecy2->reveal());
        $app->get('/', function ($request, ResponseInterface $response) {
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $responseHeaders = [];
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->hasHeader(Argument::type('string'))->will(function ($args) use (&$responseHeaders) {
            return array_key_exists($args[0], $responseHeaders);
        });
        $requestProphecy->withAddedHeader(
            Argument::type('string'),
            Argument::type('string')
        )->will(function ($args) use (&$responseHeaders) {
            $key = $args[0];
            $value = $args[1];
            if (!isset($responseHeaders[$key])) {
                $responseHeaders[$key] = [];
            }
            $responseHeaders[$key][] = $value;
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame(['nested', 'outer'], $response->getHeader('X-TRACE'));
        $this->assertEquals('11', (string) $response->getBody());
    }

    // TODO: Re-add testUnsupportedMethodWithoutRoute

    // TODO: Re-add testUnsupportedMethodWithRoute

    public function testContainerSetToRoute()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('Hello World');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('handler')->willReturn(true);
        $containerProphecy->get('handler')->willReturn(function () use ($responseProphecy) {
            return $responseProphecy->reveal();
        });

        $app = new App($responseFactoryProphecy->reveal(), $containerProphecy->reveal());
        $router = $app->getRouter();
        $router->map(['GET'], '/', 'handler');

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertEquals('Hello World', (string) $response->getBody());
    }

    public function testAppIsARequestHandler()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());

        $this->assertInstanceOf(RequestHandlerInterface::class, $app);
    }

    public function testInvokeSequentialProccessToAPathWithOptionalArgsAndWithoutOptionalArgs()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/Hello[/{name}]', function ($request, ResponseInterface $response, $args) {
            $response->getBody()->write((string) count($args));
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal());
        $this->assertEquals('1', (string) $response->getBody());

        $uriProphecy2 = $this->prophesize(UriInterface::class);
        $uriProphecy2->getPath()->willReturn('/Hello');

        $requestProphecy2 = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy2->getMethod()->willReturn('GET');
        $requestProphecy2->getUri()->willReturn($uriProphecy2->reveal());
        $requestProphecy2->getAttribute('routingResults')->willReturn(null);
        $requestProphecy2->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $streamProphecy->__toString()->willReturn('');
        $response = $app->handle($requestProphecy2->reveal());
        $this->assertEquals('0', (string) $response->getBody());
    }

    public function testInvokeSequentialProccessToAPathWithOptionalArgsAndWithoutOptionalArgsAndKeepSetedArgs()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/Hello[/{name}]', function ($request, ResponseInterface $response, $args) {
            $response->getBody()->write((string) count($args));
            return $response;
        })->setArgument('extra', 'value');

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal());
        $this->assertEquals('2', (string) $response->getBody());

        $uriProphecy2 = $this->prophesize(UriInterface::class);
        $uriProphecy2->getPath()->willReturn('/Hello');

        $requestProphecy2 = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy2->getMethod()->willReturn('GET');
        $requestProphecy2->getUri()->willReturn($uriProphecy2->reveal());
        $requestProphecy2->getAttribute('routingResults')->willReturn(null);
        $requestProphecy2->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $streamProphecy->__toString()->willReturn('');
        $response = $app->handle($requestProphecy2->reveal());
        $this->assertEquals('1', (string) $response->getBody());
    }

    public function testInvokeSequentialProccessAfterAddingAnotherRouteArgument()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $route = $app->get('/Hello[/{name}]', function ($request, ResponseInterface $response, $args) {
            $response->getBody()->write((string) count($args));
            return $response;
        })->setArgument('extra', 'value');

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute('route')->willReturn($route);
        $requestProphecy->getAttribute('routingResults')->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $clone = clone $this;
            $clone->getAttribute($args[0])->willReturn($args[1]);
            return $clone;
        });

        $response = $app->handle($requestProphecy->reveal());
        $this->assertEquals('2', (string) $response->getBody());

        $route->setArgument('extra2', 'value2');

        $streamProphecy->__toString()->willReturn('');
        $response = $app->handle($requestProphecy->reveal());
        $this->assertEquals('3', (string) $response->getBody());
    }
}
