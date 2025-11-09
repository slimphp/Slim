<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use FastRoute\BadRouteException;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use Slim\Routing\FastRouteDispatcher;
use Slim\Tests\TestCase;

use function FastRoute\simpleDispatcher;

class FastRouteDispatcherTest extends TestCase
{
    /**
     * @dataProvider provideFoundDispatchCases
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideFoundDispatchCases')]
    public function testFoundDispatches($method, $uri, $callback, $handler, $argDict)
    {
        /** @var FastRouteDispatcher $dispatcher */
        $dispatcher = simpleDispatcher($callback, $this->generateDispatcherOptions());

        $results = $dispatcher->dispatch($method, $uri);

        $this->assertSame($dispatcher::FOUND, $results[0]);
        $this->assertSame($handler, $results[1]);
        $this->assertSame($argDict, $results[2]);
    }

    /**
     * Set appropriate options for the specific Dispatcher class we're testing
     */
    private function generateDispatcherOptions()
    {
        return [
            'dataGenerator' => $this->getDataGeneratorClass(),
            'dispatcher' => $this->getDispatcherClass()
        ];
    }

    protected function getDataGeneratorClass()
    {
        return GroupCountBased::class;
    }

    protected function getDispatcherClass()
    {
        return FastRouteDispatcher::class;
    }

    /**
     * @dataProvider provideNotFoundDispatchCases
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideNotFoundDispatchCases')]
    public function testNotFoundDispatches($method, $uri, $callback)
    {
        /** @var FastRouteDispatcher $dispatcher */
        $dispatcher = simpleDispatcher($callback, $this->generateDispatcherOptions());

        $results = $dispatcher->dispatch($method, $uri);

        $this->assertSame($dispatcher::NOT_FOUND, $results[0]);
    }

    /**
     * @dataProvider provideMethodNotAllowedDispatchCases
     * @param $method
     * @param $uri
     * @param $callback
     * @param $allowedMethods
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideMethodNotAllowedDispatchCases')]
    public function testMethodNotAllowedDispatches($method, $uri, $callback, $allowedMethods)
    {
        /** @var FastRouteDispatcher $dispatcher */
        $dispatcher = simpleDispatcher($callback, $this->generateDispatcherOptions());

        $results = $dispatcher->dispatch($method, $uri);

        $this->assertSame($dispatcher::METHOD_NOT_ALLOWED, $results[0]);
    }

    /**
     * @dataProvider provideMethodNotAllowedDispatchCases
     * @param $method
     * @param $uri
     * @param $callback
     * @param $allowedMethods
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideMethodNotAllowedDispatchCases')]
    public function testGetAllowedMethods($method, $uri, $callback, $allowedMethods)
    {
        /** @var FastRouteDispatcher $dispatcher */
        $dispatcher = simpleDispatcher($callback, $this->generateDispatcherOptions());

        $results = $dispatcher->getAllowedMethods($uri);

        $this->assertSame($results, $allowedMethods);
    }

    public function testDuplicateVariableNameError()
    {
        $this->expectException(BadRouteException::class);
        $this->expectExceptionMessage('Cannot use the same placeholder "test" twice');

        simpleDispatcher(function (RouteCollector $r) {
            $r->addRoute('GET', '/foo/{test}/{test:\d+}', 'handler0');
        }, $this->generateDispatcherOptions());
    }

    public function testDuplicateVariableRoute()
    {
        $this->expectException(BadRouteException::class);
        $this->expectExceptionMessage('Cannot register two routes matching "/user/([^/]+)" for method "GET"');

        simpleDispatcher(function (RouteCollector $r) {
            $r->addRoute('GET', '/user/{id}', 'handler0'); // oops, forgot \d+ restriction ;)
            $r->addRoute('GET', '/user/{name}', 'handler1');
        }, $this->generateDispatcherOptions());
    }

    public function testDuplicateStaticRoute()
    {
        $this->expectException(BadRouteException::class);
        $this->expectExceptionMessage('Cannot register two routes matching "/user" for method "GET"');

        simpleDispatcher(function (RouteCollector $r) {
            $r->addRoute('GET', '/user', 'handler0');
            $r->addRoute('GET', '/user', 'handler1');
        }, $this->generateDispatcherOptions());
    }

    /**
     * @codingStandardsIgnoreStart
     * @codingStandardsIgnoreEnd
     */
    public function testShadowedStaticRoute()
    {
        $this->expectException(BadRouteException::class);
        $this->expectExceptionMessage('Static route "/user/nikic" is shadowed by previously defined variable route' .
                                      ' "/user/([^/]+)" for method "GET"');

        simpleDispatcher(function (RouteCollector $r) {
            $r->addRoute('GET', '/user/{name}', 'handler0');
            $r->addRoute('GET', '/user/nikic', 'handler1');
        }, $this->generateDispatcherOptions());
    }

    public function testCapturing()
    {
        $this->expectException(BadRouteException::class);
        $this->expectExceptionMessage('Regex "(en|de)" for parameter "lang" contains a capturing group');

        simpleDispatcher(function (RouteCollector $r) {
            $r->addRoute('GET', '/{lang:(en|de)}', 'handler0');
        }, $this->generateDispatcherOptions());
    }

    public static function provideFoundDispatchCases()
    {
        $cases = [];

        // 0 -------------------------------------------------------------------------------------->

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/resource/123/456', 'handler0');
        };

        $method = 'GET';
        $uri = '/resource/123/456';
        $handler = 'handler0';
        $argDict = [];

        $cases[] = [$method, $uri, $callback, $handler, $argDict];

        // 1 -------------------------------------------------------------------------------------->

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/handler0', 'handler0');
            $r->addRoute('GET', '/handler1', 'handler1');
            $r->addRoute('GET', '/handler2', 'handler2');
        };

        $method = 'GET';
        $uri = '/handler2';
        $handler = 'handler2';
        $argDict = [];

        $cases[] = [$method, $uri, $callback, $handler, $argDict];

        // 2 -------------------------------------------------------------------------------------->

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}', 'handler0');
            $r->addRoute('GET', '/user/{id:[0-9]+}', 'handler1');
            $r->addRoute('GET', '/user/{name}', 'handler2');
        };

        $method = 'GET';
        $uri = '/user/rdlowrey';
        $handler = 'handler2';
        $argDict = ['name' => 'rdlowrey'];

        $cases[] = [$method, $uri, $callback, $handler, $argDict];

        // 3 -------------------------------------------------------------------------------------->

        // reuse $callback from #2

        $method = 'GET';
        $uri = '/user/12345';
        $handler = 'handler1';
        $argDict = ['id' => '12345'];

        $cases[] = [$method, $uri, $callback, $handler, $argDict];

        // 4 -------------------------------------------------------------------------------------->

        // reuse $callback from #3

        $method = 'GET';
        $uri = '/user/NaN';
        $handler = 'handler2';
        $argDict = ['name' => 'NaN'];

        $cases[] = [$method, $uri, $callback, $handler, $argDict];

        // 5 -------------------------------------------------------------------------------------->

        // reuse $callback from #4

        $method = 'GET';
        $uri = '/user/rdlowrey/12345';
        $handler = 'handler0';
        $argDict = ['name' => 'rdlowrey', 'id' => '12345'];

        $cases[] = [$method, $uri, $callback, $handler, $argDict];

        // 6 -------------------------------------------------------------------------------------->

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/user/{id:[0-9]+}', 'handler0');
            $r->addRoute('GET', '/user/12345/extension', 'handler1');
            $r->addRoute('GET', '/user/{id:[0-9]+}.{extension}', 'handler2');
        };

        $method = 'GET';
        $uri = '/user/12345.svg';
        $handler = 'handler2';
        $argDict = ['id' => '12345', 'extension' => 'svg'];

        $cases[] = [$method, $uri, $callback, $handler, $argDict];

        // 7 ----- Test GET method fallback on HEAD route miss ------------------------------------>

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/user/{name}', 'handler0');
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}', 'handler1');
            $r->addRoute('GET', '/static0', 'handler2');
            $r->addRoute('GET', '/static1', 'handler3');
            $r->addRoute('HEAD', '/static1', 'handler4');
        };

        $method = 'HEAD';
        $uri = '/user/rdlowrey';
        $handler = 'handler0';
        $argDict = ['name' => 'rdlowrey'];

        $cases[] = [$method, $uri, $callback, $handler, $argDict];

        // 8 ----- Test GET method fallback on HEAD route miss ------------------------------------>

        // reuse $callback from #7

        $method = 'HEAD';
        $uri = '/user/rdlowrey/1234';
        $handler = 'handler1';
        $argDict = ['name' => 'rdlowrey', 'id' => '1234'];

        $cases[] = [$method, $uri, $callback, $handler, $argDict];

        // 9 ----- Test GET method fallback on HEAD route miss ------------------------------------>

        // reuse $callback from #8

        $method = 'HEAD';
        $uri = '/static0';
        $handler = 'handler2';
        $argDict = [];

        $cases[] = [$method, $uri, $callback, $handler, $argDict];

        // 10 ---- Test existing HEAD route used if available (no fallback) ----------------------->

        // reuse $callback from #9

        $method = 'HEAD';
        $uri = '/static1';
        $handler = 'handler4';
        $argDict = [];

        $cases[] = [$method, $uri, $callback, $handler, $argDict];

        // 11 ---- More specified routes are not shadowed by less specific of another method ------>

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/user/{name}', 'handler0');
            $r->addRoute('POST', '/user/{name:[a-z]+}', 'handler1');
        };

        $method = 'POST';
        $uri = '/user/rdlowrey';
        $handler = 'handler1';
        $argDict = ['name' => 'rdlowrey'];

        $cases[] = [$method, $uri, $callback, $handler, $argDict];

        // 12 ---- Handler of more specific routes is used, if it occurs first -------------------->

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/user/{name}', 'handler0');
            $r->addRoute('POST', '/user/{name:[a-z]+}', 'handler1');
            $r->addRoute('POST', '/user/{name}', 'handler2');
        };

        $method = 'POST';
        $uri = '/user/rdlowrey';
        $handler = 'handler1';
        $argDict = ['name' => 'rdlowrey'];

        $cases[] = [$method, $uri, $callback, $handler, $argDict];

        // 13 ---- Route with constant suffix ----------------------------------------------------->

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/user/{name}', 'handler0');
            $r->addRoute('GET', '/user/{name}/edit', 'handler1');
        };

        $method = 'GET';
        $uri = '/user/rdlowrey/edit';
        $handler = 'handler1';
        $argDict = ['name' => 'rdlowrey'];

        $cases[] = [$method, $uri, $callback, $handler, $argDict];

        // 14 ---- Handle multiple methods with the same handler ---------------------------------->

        $callback = function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '/user', 'handlerGetPost');
            $r->addRoute(['DELETE'], '/user', 'handlerDelete');
            $r->addRoute([], '/user', 'handlerNone');
        };

        $argDict = [];
        $cases[] = ['GET', '/user', $callback, 'handlerGetPost', $argDict];
        $cases[] = ['POST', '/user', $callback, 'handlerGetPost', $argDict];
        $cases[] = ['DELETE', '/user', $callback, 'handlerDelete', $argDict];

        // 17 ----

        $callback = function (RouteCollector $r) {
            $r->addRoute('POST', '/user.json', 'handler0');
            $r->addRoute('GET', '/{entity}.json', 'handler1');
        };

        $cases[] = ['GET', '/user.json', $callback, 'handler1', ['entity' => 'user']];

        // 18 ----

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '', 'handler0');
        };

        $cases[] = ['GET', '', $callback, 'handler0', []];

        // 19 ----

        $callback = function (RouteCollector $r) {
            $r->addRoute('HEAD', '/a/{foo}', 'handler0');
            $r->addRoute('GET', '/b/{foo}', 'handler1');
        };

        $cases[] = ['HEAD', '/b/bar', $callback, 'handler1', ['foo' => 'bar']];

        // 20 ----

        $callback = function (RouteCollector $r) {
            $r->addRoute('HEAD', '/a', 'handler0');
            $r->addRoute('GET', '/b', 'handler1');
        };

        $cases[] = ['HEAD', '/b', $callback, 'handler1', []];

        // 21 ----

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/foo', 'handler0');
            $r->addRoute('HEAD', '/{bar}', 'handler1');
        };

        $cases[] = ['HEAD', '/foo', $callback, 'handler1', ['bar' => 'foo']];

        // 22 ----

        $callback = function (RouteCollector $r) {
            $r->addRoute('*', '/user', 'handler0');
            $r->addRoute('*', '/{user}', 'handler1');
            $r->addRoute('GET', '/user', 'handler2');
        };

        $cases[] = ['GET', '/user', $callback, 'handler2', []];

        // 23 ----

        $callback = function (RouteCollector $r) {
            $r->addRoute('*', '/user', 'handler0');
            $r->addRoute('GET', '/user', 'handler1');
        };

        $cases[] = ['POST', '/user', $callback, 'handler0', []];

        // 24 ----

        $cases[] = ['HEAD', '/user', $callback, 'handler1', []];

        // 25 ----

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/{bar}', 'handler0');
            $r->addRoute('*', '/foo', 'handler1');
        };

        $cases[] = ['GET', '/foo', $callback, 'handler0', ['bar' => 'foo']];

        // 26 ----

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/user', 'handler0');
            $r->addRoute('*', '/{foo:.*}', 'handler1');
        };

        $cases[] = ['POST', '/bar', $callback, 'handler1', ['foo' => 'bar']];

        // 27 --- International characters

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/новости/{name}', 'handler0');
        };

        $cases[] = ['GET', '/новости/rdlowrey', $callback, 'handler0', ['name' => 'rdlowrey']];

        // x -------------------------------------------------------------------------------------->

        return $cases;
    }

    public static function provideNotFoundDispatchCases()
    {
        $cases = [];

        // 0 -------------------------------------------------------------------------------------->

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/resource/123/456', 'handler0');
        };

        $method = 'GET';
        $uri = '/not-found';

        $cases[] = [$method, $uri, $callback];

        // 1 -------------------------------------------------------------------------------------->

        // reuse callback from #0
        $method = 'POST';
        $uri = '/not-found';

        $cases[] = [$method, $uri, $callback];

        // 2 -------------------------------------------------------------------------------------->

        // reuse callback from #1
        $method = 'PUT';
        $uri = '/not-found';

        $cases[] = [$method, $uri, $callback];

        // 3 -------------------------------------------------------------------------------------->

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/handler0', 'handler0');
            $r->addRoute('GET', '/handler1', 'handler1');
            $r->addRoute('GET', '/handler2', 'handler2');
        };

        $method = 'GET';
        $uri = '/not-found';

        $cases[] = [$method, $uri, $callback];

        // 4 -------------------------------------------------------------------------------------->

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}', 'handler0');
            $r->addRoute('GET', '/user/{id:[0-9]+}', 'handler1');
            $r->addRoute('GET', '/user/{name}', 'handler2');
        };

        $method = 'GET';
        $uri = '/not-found';

        $cases[] = [$method, $uri, $callback];

        // 5 -------------------------------------------------------------------------------------->

        // reuse callback from #4
        $method = 'GET';
        $uri = '/user/rdlowrey/12345/not-found';

        $cases[] = [$method, $uri, $callback];

        // 6 -------------------------------------------------------------------------------------->

        // reuse callback from #5
        $method = 'HEAD';

        $cases[] = [$method, $uri, $callback];

        // x -------------------------------------------------------------------------------------->

        return $cases;
    }

    public static function provideMethodNotAllowedDispatchCases()
    {
        $cases = [];

        // 0 -------------------------------------------------------------------------------------->

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/resource/123/456', 'handler0');
        };

        $method = 'POST';
        $uri = '/resource/123/456';
        $allowedMethods = ['GET'];

        $cases[] = [$method, $uri, $callback, $allowedMethods];

        // 1 -------------------------------------------------------------------------------------->

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/resource/123/456', 'handler0');
            $r->addRoute('POST', '/resource/123/456', 'handler1');
            $r->addRoute('PUT', '/resource/123/456', 'handler2');
            $r->addRoute('*', '/', 'handler3');
        };

        $method = 'DELETE';
        $uri = '/resource/123/456';
        $allowedMethods = ['GET', 'POST', 'PUT'];

        $cases[] = [$method, $uri, $callback, $allowedMethods];

        // 2 -------------------------------------------------------------------------------------->

        $callback = function (RouteCollector $r) {
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}', 'handler0');
            $r->addRoute('POST', '/user/{name}/{id:[0-9]+}', 'handler1');
            $r->addRoute('PUT', '/user/{name}/{id:[0-9]+}', 'handler2');
            $r->addRoute('PATCH', '/user/{name}/{id:[0-9]+}', 'handler3');
        };

        $method = 'DELETE';
        $uri = '/user/rdlowrey/42';
        $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH'];

        $cases[] = [$method, $uri, $callback, $allowedMethods];

        // 3 -------------------------------------------------------------------------------------->

        $callback = function (RouteCollector $r) {
            $r->addRoute('POST', '/user/{name}', 'handler1');
            $r->addRoute('PUT', '/user/{name:[a-z]+}', 'handler2');
            $r->addRoute('PATCH', '/user/{name:[a-z]+}', 'handler3');
        };

        $method = 'GET';
        $uri = '/user/rdlowrey';
        $allowedMethods = ['POST', 'PUT', 'PATCH'];

        $cases[] = [$method, $uri, $callback, $allowedMethods];

        // 4 -------------------------------------------------------------------------------------->

        $callback = function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '/user', 'handlerGetPost');
            $r->addRoute(['DELETE'], '/user', 'handlerDelete');
            $r->addRoute([], '/user', 'handlerNone');
        };

        $cases[] = ['PUT', '/user', $callback, ['GET', 'POST', 'DELETE']];

        // 5

        $callback = function (RouteCollector $r) {
            $r->addRoute('POST', '/user.json', 'handler0');
            $r->addRoute('GET', '/{entity}.json', 'handler1');
        };

        $cases[] = ['PUT', '/user.json', $callback, ['POST', 'GET']];

        // x -------------------------------------------------------------------------------------->

        return $cases;
    }
}
