<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

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
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use Slim\App;
use Slim\CallableResolver;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\Strategies\RequestResponseArgs;
use Slim\Handlers\Strategies\RequestResponseNamedArgs;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\MiddlewareDispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Middleware\ErrorMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\MiddlewareDispatcher;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;
use Slim\Tests\Mocks\MockAction;
use stdClass;

use function array_key_exists;
use function array_shift;
use function count;
use function ini_set;
use function json_encode;
use function strtolower;
use function sys_get_temp_dir;
use function tempnam;

class AppTest extends TestCase
{
    public static function setupBeforeClass(): void
    {
        ini_set('error_log', tempnam(sys_get_temp_dir(), 'slim'));
    }

    public function testDoesNotUseContainerAsServiceLocator(): void
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

    public function testGetContainer(): void
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $app = new App($responseFactoryProphecy->reveal(), $containerProphecy->reveal());

        $this->assertSame($containerProphecy->reveal(), $app->getContainer());
    }

    public function testGetCallableResolverReturnsInjectedInstance(): void
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $app = new App($responseFactoryProphecy->reveal(), null, $callableResolverProphecy->reveal());

        $this->assertSame($callableResolverProphecy->reveal(), $app->getCallableResolver());
    }

    public function testCreatesCallableResolverWhenNull(): void
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $callableResolver = new CallableResolver($containerProphecy->reveal());
        $app = new App($responseFactoryProphecy->reveal(), $containerProphecy->reveal(), null);

        $this->assertEquals($callableResolver, $app->getCallableResolver());
    }

    public function testGetRouteCollectorReturnsInjectedInstance(): void
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routeParserProphecy = $this->prophesize(RouteParserInterface::class);

        $routeCollectorProphecy->getRouteParser()->willReturn($routeParserProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal(), null, null, $routeCollectorProphecy->reveal());

        $this->assertSame($routeCollectorProphecy->reveal(), $app->getRouteCollector());
    }

    public function testCreatesRouteCollectorWhenNullWithInjectedContainer(): void
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $routeCollector = new RouteCollector(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            $containerProphecy->reveal()
        );
        $app = new App(
            $responseFactoryProphecy->reveal(),
            $containerProphecy->reveal(),
            $callableResolverProphecy->reveal()
        );

        $this->assertEquals($routeCollector, $app->getRouteCollector());
    }

    public function testGetMiddlewareDispatcherGetsSeededAndReturnsInjectedInstance(): void
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);

        $middlewareDispatcherProphecy = $this->prophesize(MiddlewareDispatcherInterface::class);
        $middlewareDispatcherProphecy
            ->seedMiddlewareStack(Argument::any())
            ->shouldBeCalledOnce();

        $app = new App(
            $responseFactoryProphecy->reveal(),
            null,
            null,
            null,
            null,
            $middlewareDispatcherProphecy->reveal()
        );

        $this->assertSame($middlewareDispatcherProphecy->reveal(), $app->getMiddlewareDispatcher());
    }

    public static function lowerCaseRequestMethodsProvider(): array
    {
        return [
            ['get'],
            ['post'],
            ['put'],
            ['patch'],
            ['delete'],
            ['options'],
        ];
    }

    /**
     * @param string $method
     * @dataProvider upperCaseRequestMethodsProvider()
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('upperCaseRequestMethodsProvider')]
    public function testGetPostPutPatchDeleteOptionsMethods(string $method): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('Hello World');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn($method);
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $methodName = strtolower($method);
        $app = new App($responseFactoryProphecy->reveal());
        $app->$methodName('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });
        $response = $app->handle($requestProphecy->reveal());

        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testAnyRoute(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('Hello World');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->any('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });

        foreach ($this->upperCaseRequestMethodsProvider() as $methods) {
            $method = $methods[0];
            $uriProphecy = $this->prophesize(UriInterface::class);
            $uriProphecy->getPath()->willReturn('/');

            $requestProphecy = $this->prophesize(ServerRequestInterface::class);
            $requestProphecy->getMethod()->willReturn($method);
            $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
            $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
            $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
                $this->getAttribute($args[0])->willReturn($args[1]);
                return $this;
            });

            $response = $app->handle($requestProphecy->reveal());

            $this->assertSame('Hello World', (string) $response->getBody());
        }
    }

    /********************************************************************************
     * Route collector proxy methods
     *******************************************************************************/

    public static function upperCaseRequestMethodsProvider(): array
    {
        return [
            ['GET'],
            ['POST'],
            ['PUT'],
            ['PATCH'],
            ['DELETE'],
            ['OPTIONS'],
        ];
    }

    /**
     * @param string $method
     * @dataProvider lowerCaseRequestMethodsProvider
     * @dataProvider upperCaseRequestMethodsProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('lowerCaseRequestMethodsProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('upperCaseRequestMethodsProvider')]
    public function testMapRoute(string $method): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('Hello World');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn($method);
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $app = new App($responseFactoryProphecy->reveal());
        $app->map([$method], '/', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });
        $response = $app->handle($requestProphecy->reveal());

        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testRedirectRoute(): void
    {
        $from = '/from';
        $to = '/to';

        $routeCreatedResponse = $this->prophesize(ResponseInterface::class);

        $handlerCreatedResponse = $this->prophesize(ResponseInterface::class);
        $handlerCreatedResponse->getStatusCode()->willReturn(301);
        $handlerCreatedResponse->getHeaderLine('Location')->willReturn($to);
        $handlerCreatedResponse->withHeader(
            Argument::type('string'),
            Argument::type('string')
        )->will(function ($args) {
            $this->getHeader($args[0])->willReturn($args[1]);
            return $this;
        });

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($routeCreatedResponse->reveal());
        $responseFactoryProphecy->createResponse(301)->willReturn($handlerCreatedResponse->reveal());

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn($from);
        $uriProphecy->__toString()->willReturn($to);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $app = new App($responseFactoryProphecy->reveal());
        $app->redirect($from, $to, 301);
        $response = $app->handle($requestProphecy->reveal());

        $responseFactoryProphecy->createResponse(301)->shouldHaveBeenCalled();
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame($to, $response->getHeaderLine('Location'));
    }

    public function testRouteWithInternationalCharacters(): void
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
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });
        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    /********************************************************************************
     * Route Patterns
     *******************************************************************************/

    public static function routePatternsProvider(): array
    {
        return [
            [''], // Empty Route
            ['/'], // Single Slash Route
            ['foo'], // Route That Does Not Start With A Slash
            ['/foo'], // Route That Does Not End In A Slash
            ['/foo/'], // Route That Ends In A Slash
        ];
    }

    /**
     * @param string $pattern
     * @dataProvider routePatternsProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('routePatternsProvider')]
    public function testRoutePatterns(string $pattern): void
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);

        $app = new App($responseFactoryProphecy->reveal());
        $app->get($pattern, function () {
        });

        $routeCollector = $app->getRouteCollector();
        $route = $routeCollector->lookupRoute('route0');

        $this->assertSame($pattern, $route->getPattern());
    }

    /********************************************************************************
     * Route Groups
     *******************************************************************************/

    public static function routeGroupsDataProvider(): array
    {
        return [
            'empty group with empty route' => [
                ['', ''], ''
            ],
            'empty group with single slash route' => [
                ['', '/'], '/'
            ],
            'empty group with segment route that does not end in aSlash' => [
                ['', '/foo'], '/foo'
            ],
            'empty group with segment route that ends in aSlash' => [
                ['', '/foo/'], '/foo/'
            ],
            'group single slash with empty route' => [
                ['/', ''], '/'
            ],
            'group single slash with single slash route' => [
                ['/', '/'], '//'
            ],
            'group single slash with segment route that does not end in aSlash' => [
                ['/', '/foo'], '//foo'
            ],
            'group single slash with segment route that ends in aSlash' => [
                ['/', '/foo/'], '//foo/'
            ],
            'group segment with empty route' => [
                ['/foo', ''], '/foo'
            ],
            'group segment with single slash route' => [
                ['/foo', '/'], '/foo/'
            ],
            'group segment with segment route that does not end in aSlash' => [
                ['/foo', '/bar'], '/foo/bar'
            ],
            'group segment with segment route that ends in aSlash' => [
                ['/foo', '/bar/'], '/foo/bar/'
            ],
            'empty group with nested group segment with an empty route' => [
                ['', '/foo', ''], '/foo'
            ],
            'empty group with nested group segment with single slash route' => [
                ['', '/foo', '/'], '/foo/'
            ],
            'group single slash with empty nested group and segment route without leading slash' => [
                ['/', '', 'foo'], '/foo'
            ],
            'group single slash with empty nested group and segment route' => [
                ['/', '', '/foo'], '//foo'
            ],
            'group single slash with single slash group and segment route without leading slash' => [
                ['/', '/', 'foo'], '//foo'
            ],
            'group single slash with single slash nested group and segment route' => [
                ['/', '/', '/foo'], '///foo'
            ],
            'group single slash with nested group segment with an empty route' => [
                ['/', '/foo', ''], '//foo'
            ],
            'group single slash with nested group segment with single slash route' => [
                ['/', '/foo', '/'], '//foo/'
            ],
            'group single slash with nested group segment with segment route' => [
                ['/', '/foo', '/bar'], '//foo/bar'
            ],
            'group single slash with nested group segment with segment route that has aTrailing slash' => [
                ['/', '/foo', '/bar/'], '//foo/bar/'
            ],
            'empty group with empty nested group and segment route without leading slash' => [
                ['', '', 'foo'], 'foo'
            ],
            'empty group with empty nested group and segment route' => [
                ['', '', '/foo'], '/foo'
            ],
            'empty group with single slash group and segment route without leading slash' => [
                ['', '/', 'foo'], '/foo'
            ],
            'empty group with single slash nested group and segment route' => [
                ['', '/', '/foo'], '//foo'
            ],
            'empty group with nested group segment with segment route' => [
                ['', '/foo', '/bar'], '/foo/bar'
            ],
            'empty group with nested group segment with segment route that has aTrailing slash' => [
                ['', '/foo', '/bar/'], '/foo/bar/'
            ],
            'group segment with empty nested group and segment route without leading slash' => [
                ['/foo', '', 'bar'], '/foobar'
            ],
            'group segment with empty nested group and segment route' => [
                ['/foo', '', '/bar'], '/foo/bar'
            ],
            'group segment with single slash nested group and segment route' => [
                ['/foo', '/', 'bar'], '/foo/bar'
            ],
            'group segment with single slash nested group and slash segment route' => [
                ['/foo', '/', '/bar'], '/foo//bar'
            ],
            'two group segments with empty route' => [
                ['/foo', '/bar', ''], '/foo/bar'
            ],
            'two group segments with single slash route' => [
                ['/foo', '/bar', '/'], '/foo/bar/'
            ],
            'two group segments with segment route' => [
                ['/foo', '/bar', '/baz'], '/foo/bar/baz'
            ],
            'two group segments with segment route that has aTrailing slash' => [
                ['/foo', '/bar', '/baz/'], '/foo/bar/baz/'
            ],
        ];
    }

    public function testGroupClosureIsBoundToThisClass(): void
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());

        $testCase = $this;
        $app->group('/foo', function () use ($testCase) {
            $testCase->assertSame($testCase, $this);
        });
    }

    /**
     * @dataProvider routeGroupsDataProvider
     * @param array  $sequence
     * @param string $expectedPath
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('routeGroupsDataProvider')]
    public function testRouteGroupCombinations(array $sequence, string $expectedPath): void
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());

        $processSequence = function (RouteCollectorProxy $app, array $sequence, $processSequence) {
            $path = array_shift($sequence);

            /**
             * If sequence isn't on last element we use $app->group()
             * The very tail of the sequence uses the $app->get() method
             */
            if (count($sequence)) {
                $app->group($path, function (RouteCollectorProxy $group) use (&$sequence, $processSequence) {
                    $processSequence($group, $sequence, $processSequence);
                });
            } else {
                $app->get($path, function () {
                });
            }
        };

        $processSequence($app, $sequence, $processSequence);

        $routeCollector = $app->getRouteCollector();
        $route = $routeCollector->lookupRoute('route0');

        $this->assertSame($expectedPath, $route->getPattern());
    }

    public function testRouteGroupPattern(): void
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);

        /** @var ResponseFactoryInterface $responseFactoryInterface */
        $responseFactoryInterface = $responseFactoryProphecy->reveal();
        $app = new App($responseFactoryInterface);
        $group = $app->group('/foo', function () {
        });

        $this->assertSame('/foo', $group->getPattern());
    }

    /********************************************************************************
     * Middleware
     *******************************************************************************/

    public function testAddMiddleware(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('Hello World');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());

        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(Argument::cetera())->will(function () use ($responseProphecy) {
            return $responseProphecy->reveal();
        });

        $middlewareProphecy2 = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy2->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )->will(function ($args) {
            /** @var ServerRequestInterface $request */
            $request = $args[0];

            /** @var RequestHandlerInterface $handler */
            $handler = $args[1];

            return $handler->handle($request);
        });

        $app->add($middlewareProphecy->reveal());
        $app->addMiddleware($middlewareProphecy2->reveal());
        $app->get('/', function (ServerRequestInterface $request, $response) {
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });


        $response = $app->handle($requestProphecy->reveal());
        $middlewareProphecy->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )->shouldHaveBeenCalled();
        $middlewareProphecy2->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )->shouldHaveBeenCalled();

        $this->assertSame($responseProphecy->reveal(), $response);
    }

    public function testAddMiddlewareUsingDeferredResolution(): void
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
        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
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

    public function testAddRoutingMiddleware(): void
    {
        /** @var ResponseFactoryInterface $responseFactory */
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class)->reveal();

        // Create the app.
        $app = new App($responseFactory);

        // Add the routing middleware.
        $routingMiddleware = $app->addRoutingMiddleware();

        // Check that the routing middleware really has been added to the tip of the app middleware stack.
        $middlewareDispatcherProperty = new ReflectionProperty(App::class, 'middlewareDispatcher');
        $this->setAccessible($middlewareDispatcherProperty);
        /** @var MiddlewareDispatcher $middlewareDispatcher */
        $middlewareDispatcher = $middlewareDispatcherProperty->getValue($app);

        $tipProperty = new ReflectionProperty(MiddlewareDispatcher::class, 'tip');
        $this->setAccessible($tipProperty);
        /** @var RequestHandlerInterface $tip */
        $tip = $tipProperty->getValue($middlewareDispatcher);

        $reflection = new ReflectionClass($tip);
        $middlewareProperty = $reflection->getProperty('middleware');
        $this->setAccessible($middlewareProperty);

        $this->assertSame($routingMiddleware, $middlewareProperty->getValue($tip));
        $this->assertInstanceOf(RoutingMiddleware::class, $routingMiddleware);
    }

    public function testAddErrorMiddleware(): void
    {
        /** @var ResponseFactoryInterface $responseFactory */
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class)->reveal();

        /** @var LoggerInterface $logger */
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        // Create the app.
        $app = new App($responseFactory);

        // Add the error middleware.
        $errorMiddleware = $app->addErrorMiddleware(true, true, true, $logger);

        // Check that the error middleware really has been added to the tip of the app middleware stack.
        $middlewareDispatcherProperty = new ReflectionProperty(App::class, 'middlewareDispatcher');
        $this->setAccessible($middlewareDispatcherProperty);
        /** @var MiddlewareDispatcher $middlewareDispatcher */
        $middlewareDispatcher = $middlewareDispatcherProperty->getValue($app);

        $tipProperty = new ReflectionProperty(MiddlewareDispatcher::class, 'tip');
        $this->setAccessible($tipProperty);
        /** @var RequestHandlerInterface $tip */
        $tip = $tipProperty->getValue($middlewareDispatcher);

        $reflection = new ReflectionClass($tip);
        $middlewareProperty = $reflection->getProperty('middleware');
        $this->setAccessible($middlewareProperty);

        $this->assertSame($errorMiddleware, $middlewareProperty->getValue($tip));
        $this->assertInstanceOf(ErrorMiddleware::class, $errorMiddleware);
    }

    public function testAddBodyParsingMiddleware(): void
    {
        /** @var ResponseFactoryInterface $responseFactory */
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class)->reveal();

        // Create the app.
        $app = new App($responseFactory);

        // Add the error middleware.
        $bodyParsingMiddleware = $app->addBodyParsingMiddleware();

        // Check that the body parsing middleware really has been added to the tip of the app middleware stack.
        $middlewareDispatcherProperty = new ReflectionProperty(App::class, 'middlewareDispatcher');
        $this->setAccessible($middlewareDispatcherProperty);
        /** @var MiddlewareDispatcher $middlewareDispatcher */
        $middlewareDispatcher = $middlewareDispatcherProperty->getValue($app);

        $tipProperty = new ReflectionProperty(MiddlewareDispatcher::class, 'tip');
        $this->setAccessible($tipProperty);
        /** @var RequestHandlerInterface $tip */
        $tip = $tipProperty->getValue($middlewareDispatcher);

        $reflection = new ReflectionClass($tip);
        $middlewareProperty = $reflection->getProperty('middleware');
        $this->setAccessible($middlewareProperty);

        $this->assertSame($bodyParsingMiddleware, $middlewareProperty->getValue($tip));
        $this->assertInstanceOf(BodyParsingMiddleware::class, $bodyParsingMiddleware);
    }

    public function testAddMiddlewareOnRoute(): void
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
        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) use (&$output) {
            $output .= 'Center';
            return $response;
        })
            ->add($middlewareProphecy->reveal())
            ->addMiddleware($middlewareProphecy2->reveal());

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $app->handle($requestProphecy->reveal());

        $this->assertSame('In2In1CenterOut1Out2', $output);
    }

    public function testAddMiddlewareOnRouteGroup(): void
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
        $app->group('/foo', function (RouteCollectorProxy $proxy) use (&$output) {
            $proxy->get('/bar', function (ServerRequestInterface $request, ResponseInterface $response) use (&$output) {
                $output .= 'Center';
                return $response;
            });
        })
            ->add($middlewareProphecy->reveal())
            ->addMiddleware($middlewareProphecy2->reveal());

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/foo/bar');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $app->handle($requestProphecy->reveal());

        $this->assertSame('In2In1CenterOut1Out2', $output);
    }

    public function testAddMiddlewareOnTwoRouteGroup(): void
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
        $app->group('/foo', function (RouteCollectorProxyInterface $group) use (
            $middlewareProphecy2,
            $middlewareProphecy3,
            &$output
        ) {
            // ensure that more than one nested group at the same level doesn't break middleware
            $group->group('/fizz', function (RouteCollectorProxyInterface $group) {
                $group->get('/buzz', function (ServerRequestInterface $request, ResponseInterface $response) {
                    return $response;
                });
            });

            $group->group('/bar', function (RouteCollectorProxyInterface $group) use (
                $middlewareProphecy3,
                &$output
            ) {
                $group->get('/baz', function (
                    ServerRequestInterface $request,
                    ResponseInterface $response
                ) use (&$output) {
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
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $app->handle($requestProphecy->reveal());

        $this->assertSame('In1In2In3CenterOut3Out2Out1', $output);
    }

    public function testAddMiddlewareAsStringNotImplementingInterfaceThrowsException(): void
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

    public function testInvokeReturnMethodNotAllowed(): void
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
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $app->handle($requestProphecy->reveal());
    }

    public function testInvokeWithMatchingRoute(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('Hello World');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/', function (ServerRequestInterface $request, $response) {
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testInvokeWithMatchingRouteWithSetArgument(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write("Hello {$args['name']}");
            return $response;
        })->setArgument('name', 'World');

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testInvokeWithMatchingRouteWithSetArguments(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write("{$args['greeting']} {$args['name']}");
            return $response;
        })->setArguments(['greeting' => 'Hello', 'name' => 'World']);

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterRequestResponseStrategy(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/Hello/{name}', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write("Hello {$args['name']}");
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterRequestResponseArgStrategy(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->getRouteCollector()->setDefaultInvocationStrategy(new RequestResponseArgs());
        $app->get('/Hello/{name}', function (ServerRequestInterface $request, ResponseInterface $response, $name) {
            $response->getBody()->write("Hello {$name}");
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterRequestResponseNamedArgsStrategy(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Named arguments are not supported in PHP versions prior to 8.0');
        }

        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->getRouteCollector()->setDefaultInvocationStrategy(new RequestResponseNamedArgs());
        $app->get(
            '/{greeting}/{name}',
            function (ServerRequestInterface $request, ResponseInterface $response, $name, $greeting) {
                $response->getBody()->write("{$greeting} {$name}");
                return $response;
            }
        );

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterOverwritesSetArgument(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/Hello/{name}', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write("Hello {$args['name']}");
            return $response;
        })->setArgument('name', 'World!');

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testInvokeWithoutMatchingRoute(): void
    {
        $this->expectException(HttpNotFoundException::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $app->handle($requestProphecy->reveal());
    }

    public function testInvokeWithCallableRegisteredInContainer(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('Hello World');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $handler = new class
        {
            public function foo(ServerRequestInterface $request, ResponseInterface $response)
            {
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
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testInvokeWithNonExistentMethodOnCallableRegisteredInContainer(): void
    {
        $this->expectException(RuntimeException::class);

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $handler = new class
        {
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
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())
            ->will(function ($args) {
                $this->getAttribute($args[0])->willReturn($args[1]);
                return $this;
            });

        $app->handle($requestProphecy->reveal());
    }

    public function testInvokeWithCallableInContainerViaCallMagicMethod(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
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
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal());

        $expectedPayload = json_encode(['name' => 'foo', 'arguments' => []]);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame($expectedPayload, (string) $response->getBody());
    }

    public function testInvokeFunctionName(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        // @codingStandardsIgnoreStart
        function handle($request, ResponseInterface $response)
        {
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
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testCurrentRequestAttributesAreNotLostWhenAddingRouteArguments(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/Hello/{name}', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write($request->getAttribute('greeting') . ' ' . $args['name']);
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal()->withAttribute('greeting', 'Hello'));

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testCurrentRequestAttributesAreNotLostWhenAddingRouteArgumentsRequestResponseArg(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->getRouteCollector()->setDefaultInvocationStrategy(new RequestResponseArgs());
        $app->get('/Hello/{name}', function (ServerRequestInterface $request, ResponseInterface $response, $name) {
            $response->getBody()->write($request->getAttribute('greeting') . ' ' . $name);
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal()->withAttribute('greeting', 'Hello'));

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testRun(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
        });
        $streamProphecy->read(1)->willReturn('_');
        $streamProphecy->read('11')->will(function () {
            $this->eof()->willReturn(true);
            return $this->reveal()->__toString();
        });
        $streamProphecy->eof()->willReturn(false);
        $streamProphecy->isSeekable()->willReturn(true);
        $streamProphecy->rewind()->shouldBeCalled();

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
        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $app->run($requestProphecy->reveal());

        $this->expectOutputString('Hello World');
    }

    public function testRunWithoutPassingInServerRequest(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
        });
        $streamProphecy->read(1)->willReturn('_');
        $streamProphecy->read(11)->will(function () {
            $this->eof()->willReturn(true);
            return $this->reveal()->__toString();
        });
        $streamProphecy->eof()->willReturn(false);
        $streamProphecy->isSeekable()->willReturn(true);
        $streamProphecy->rewind()->shouldBeCalled();

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
        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');
            return $response;
        });

        $app->run();

        $this->expectOutputString('Hello World');
    }

    public function testHandleReturnsEmptyResponseBodyWithHeadRequestMethod(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());
        $responseProphecy
            ->withBody(Argument::type(StreamInterface::class))
            ->will(function ($args) {
                $this->getBody()->willReturn($args[0]);
                return $this;
            });

        $emptyStreamProphecy = $this->prophesize(StreamInterface::class);
        $emptyStreamProphecy->__toString()->willReturn('');
        $emptyResponseProphecy = $this->prophesize(ResponseInterface::class);
        $emptyResponseProphecy->getBody()->willReturn($emptyStreamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn(
            $responseProphecy->reveal(),
            $emptyResponseProphecy->reveal()
        );

        $called = 0;
        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) use (&$called) {
            $called++;
            $response->getBody()->write('Hello World');
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('HEAD');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertSame(1, $called);
        $this->assertEmpty((string) $response->getBody());
    }

    public function testCanBeReExecutedRecursivelyDuringDispatch(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
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
                $responseProphecy->getStatusCode()->willReturn($args[0]);
                return $responseProphecy->reveal();
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
            return $response->withAddedHeader('X-TRACE', 'outer');
        });

        $middlewareProphecy2 = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy2->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )->will(function ($args) {
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
        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
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
        $this->assertSame('11', (string) $response->getBody());
    }

    // TODO: Re-add testUnsupportedMethodWithoutRoute

    // TODO: Re-add testUnsupportedMethodWithRoute

    public function testContainerSetToRoute(): void
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
        $routeCollector = $app->getRouteCollector();
        $routeCollector->map(['GET'], '/', 'handler');

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal());

        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testAppIsARequestHandler(): void
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $app = new App($responseFactoryProphecy->reveal());

        $this->assertInstanceOf(RequestHandlerInterface::class, $app);
    }

    public function testInvokeSequentialProcessToAPathWithOptionalArgsAndWithoutOptionalArgs(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/Hello[/{name}]', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write((string) count($args));
            return $response;
        });

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal());
        $this->assertSame('1', (string) $response->getBody());

        $uriProphecy2 = $this->prophesize(UriInterface::class);
        $uriProphecy2->getPath()->willReturn('/Hello');

        $requestProphecy2 = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy2->getMethod()->willReturn('GET');
        $requestProphecy2->getUri()->willReturn($uriProphecy2->reveal());
        $requestProphecy2->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy2->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $streamProphecy->__toString()->willReturn('');
        $response = $app->handle($requestProphecy2->reveal());
        $this->assertSame('0', (string) $response->getBody());
    }

    public function testInvokeSequentialProcessToAPathWithOptionalArgsAndWithoutOptionalArgsAndKeepSetedArgs(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $app->get('/Hello[/{name}]', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write((string) count($args));
            return $response;
        })->setArgument('extra', 'value');

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal());
        $this->assertSame('2', (string) $response->getBody());

        $uriProphecy2 = $this->prophesize(UriInterface::class);
        $uriProphecy2->getPath()->willReturn('/Hello');

        $requestProphecy2 = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy2->getMethod()->willReturn('GET');
        $requestProphecy2->getUri()->willReturn($uriProphecy2->reveal());
        $requestProphecy2->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy2->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $streamProphecy->__toString()->willReturn('');
        $response = $app->handle($requestProphecy2->reveal());
        $this->assertSame('1', (string) $response->getBody());
    }

    public function testInvokeSequentialProcessAfterAddingAnotherRouteArgument(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->__toString()->willReturn('');
        $streamProphecy->write(Argument::type('string'))->will(function ($args) {
            $body = $this->reveal()->__toString();
            $body .= $args[0];
            $this->__toString()->willReturn($body);
            return 0;
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $app = new App($responseFactoryProphecy->reveal());
        $route = $app->get('/Hello[/{name}]', function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            $args
        ) {
            $response->getBody()->write((string) count($args));
            return $response;
        })->setArgument('extra', 'value');

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/Hello/World');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getAttribute(RouteContext::ROUTE)->willReturn($route);
        $requestProphecy->getAttribute(RouteContext::ROUTING_RESULTS)->willReturn(null);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) {
            $this->getAttribute($args[0])->willReturn($args[1]);
            return $this;
        });

        $response = $app->handle($requestProphecy->reveal());
        $this->assertSame('2', (string) $response->getBody());

        $route->setArgument('extra2', 'value2');

        $streamProphecy->__toString()->willReturn('');
        $response = $app->handle($requestProphecy->reveal());
        $this->assertSame('3', (string) $response->getBody());
    }
}
