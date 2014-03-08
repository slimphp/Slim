<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@joshlockhart.com>
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

//Mock custom view
class CustomView extends \Slim\View
{
    public function render($template, array $data = array()) { echo "Custom view"; }
}

//Mock middleware
class CustomMiddleware extends \Slim\Middleware
{
    public function call()
    {
        $env = $this->app['environment'];
        $res = $this->app['response'];
        $this->next->call();
        $res->getHeaders()->set('X-Slim-Test', 'Hello');
        $res->write('Hello');
    }
}

class AppTest extends PHPUnit_Framework_TestCase
{
    protected $app;

    protected function createApp(array $envSettings = array(), array $appSettings = array())
    {
        $envSettings = array_merge(array(
            'SCRIPT_NAME' => '/foo/index.php',
            'REQUEST_URI' => '/foo/bar?one=foo&two=bar',
            'QUERY_STRING' => 'one=foo&two=bar',
            'SERVER_NAME' => 'slimframework.com'
        ), $envSettings);

        $appSettings = array_merge(array(), $appSettings);

        $app = new \Slim\App($appSettings);

        $app['environment'] = function () use ($envSettings) {
            $env = new \Slim\Environment();
            $env->mock($envSettings);
            return $env;
        };

        return $app;
    }

    protected function initializeApp(array $envSettings = array(), array $appSettings = array())
    {
        $this->app = $this->createApp($envSettings, $appSettings);
    }

    public function setUp()
    {
        // Remove environment mode if set
        unset($_ENV['SLIM_MODE']);

        // Reset session
        $_SESSION = array();

        // Initialize app
        $this->initializeApp();
    }

    /************************************************
     * INSTANTIATION
     ************************************************/

    /**
     * Test version constant is string
     */
    public function testHasVersionConstant()
    {
        $this->assertTrue(is_string(\Slim\App::VERSION));
    }

    /**
     * Test default instance properties
     */
    public function testDefaultInstanceProperties()
    {
        $this->assertInstanceOf('\Slim\Interfaces\Http\RequestInterface', $this->app['request']);
        $this->assertInstanceOf('\Slim\Interfaces\Http\ResponseInterface', $this->app['response']);
        $this->assertInstanceOf('\Slim\Interfaces\RouterInterface', $this->app['router']);
        $this->assertInstanceOf('\Slim\Environment', $this->app['environment']);
    }

    /**
     * Test Slim autoloader ignores non-Slim classes
     *
     * Pre-conditions:
     * Instantiate a non-Slim class;
     *
     * Post-conditions:
     * Slim autoloader returns without requiring a class file;
     */
    public function testSlimAutoloaderIgnoresNonSlimClass()
    {
        $foo = new Foo();
    }

    /************************************************
     * SETTINGS
     ************************************************/

    /**
     * Test get setting that exists
     */
    public function testGetSettingThatExists()
    {
        $this->assertEquals('development', $this->app->config('mode'));
    }

    /**
     * Test get setting that does not exist
     */
    public function testGetSettingThatDoesNotExist()
    {
        $this->assertNull($this->app->config('foo'));
    }

    /**
     * Test set setting
     */
    public function testSetSetting()
    {
        $this->assertEquals('development', $this->app->config('mode'));
        $this->app->config('mode', 'staging');
        $this->assertEquals('staging', $this->app->config('mode'));
    }

    /**
     * Test batch set settings
     */
    public function testBatchSetSettings()
    {
        $this->assertEquals('development', $this->app->config('mode'));
        $this->assertNull($this->app->config('view'));
        $this->app->config(array(
            'mode' => 'staging',
            'view' => new \Slim\View(__DIR__ . '/templates')
        ));
        $this->assertEquals('staging', $this->app->config('mode'));
        $this->assertInstanceOf('\Slim\View', $this->app->config('view'));
    }

    /************************************************
     * MODES
     ************************************************/

    /**
     * Test default mode
     */
    public function testGetDefaultMode()
    {
        $this->assertEquals('development', $this->app['mode']);
    }

    /**
     * Test custom mode from environment
     */
    public function testGetModeFromEnvironment()
    {
        $_ENV['SLIM_MODE'] = 'production';
        $this->assertEquals('production', $this->app['mode']);
    }

    /**
     * Test custom mode from app settings
     */
    public function testGetModeFromSettings()
    {
        $this->initializeApp(array(), array('mode' => 'test'));
        $this->assertEquals('test', $this->app['mode']);
    }

    /**
     * Test mode configuration
     */
    public function testModeConfiguration()
    {
        $flag = 0;
        $configureTest = function () use (&$flag) {
            $flag = 'test';
        };
        $configureProduction = function () use (&$flag) {
            $flag = 'production';
        };

        $this->initializeApp(array(), array('mode' => 'test'));
        $this->app->configureMode('test', $configureTest);
        $this->app->configureMode('production', $configureProduction);
        $this->assertEquals('test', $flag);
    }

    /**
     * Test mode configuration when mode does not match
     */
    public function testModeConfigurationWhenModeDoesNotMatch()
    {
        $flag = 0;
        $configureTest = function () use (&$flag) {
            $flag = 'test';
        };
        $this->initializeApp(array(), array('mode' => 'production'));
        $this->app->configureMode('test', $configureTest);
        $this->assertEquals(0, $flag);
    }

    /**
     * Test mode configuration when not callable
     */
    public function testModeConfigurationWhenNotCallable()
    {
        $flag = 0;
        $this->initializeApp(array(), array('mode' => 'production'));
        $this->app->configureMode('production', 'foo');
        $this->assertEquals(0, $flag);
    }

    /**
     * Test custom mode from getenv()
     */
    public function testGetModeFromGetEnv()
    {
        putenv('SLIM_MODE=production');
        $this->assertEquals('production', $this->app['mode']);
    }

    /************************************************
     * ROUTING
     ************************************************/

    /**
     * Test GENERIC route
     */
    public function testGenericRoute()
    {
        $callable = function () { echo "foo"; };
        $route = $this->app->map('/bar', $callable);
        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertEmpty($route->getHttpMethods());
    }

    /**
     * Test GET routes also get mapped as a HEAD route
     */
    public function testGetRouteIsAlsoMappedAsHead()
    {
        $route = $this->app->get('/foo', function () {});
        $this->assertTrue($route->supportsHttpMethod(\Slim\Http\Request::METHOD_GET));
        $this->assertTrue($route->supportsHttpMethod(\Slim\Http\Request::METHOD_HEAD));
    }

    /**
     * Test GET route
     */
    public function testGetRoute()
    {
        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "xyz"; };
        $route = $this->app->get('/bar', $mw1, $mw2, $callable);
        $this->app->call();
        $this->assertEquals('foobarxyz', $this->app['response']->getBody());
        $this->assertEquals('/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test POST route
     */
    public function testPostRoute()
    {
        $this->initializeApp(array(
            'REQUEST_METHOD' => 'POST',
        ));

        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "xyz"; };
        $route = $this->app->post('/bar', $mw1, $mw2, $callable);
        $this->app->call();
        $this->assertEquals('foobarxyz', $this->app['response']->getBody());
        $this->assertEquals('/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test PUT route
     */
    public function testPutRoute()
    {
        $this->initializeApp(array(
            'REQUEST_METHOD' => 'PUT'
        ));

        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "xyz"; };
        $route = $this->app->put('/bar', $mw1, $mw2, $callable);
        $this->app->call();
        $this->assertEquals('foobarxyz', $this->app['response']->getBody());
        $this->assertEquals('/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test PATCH route
     */
    public function testPatchRoute()
    {
        $this->initializeApp(array(
            'REQUEST_METHOD' => 'PATCH'
        ));

        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "xyz"; };
        $route = $this->app->patch('/bar', $mw1, $mw2, $callable);
        $this->app->call();
        $this->assertEquals('foobarxyz', $this->app['response']->getBody());
        $this->assertEquals('/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test DELETE route
     */
    public function testDeleteRoute()
    {
        $this->initializeApp(array(
            'REQUEST_METHOD' => 'DELETE'
        ));

        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "xyz"; };
        $route = $this->app->delete('/bar', $mw1, $mw2, $callable);
        $this->app->call();
        $this->assertEquals('foobarxyz', $this->app['response']->getBody());
        $this->assertEquals('/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
     * Test OPTIONS route
     */
    public function testOptionsRoute()
    {
        $this->initializeApp(array(
            'REQUEST_METHOD' => 'OPTIONS'
        ));

        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "xyz"; };
        $route = $this->app->options('/bar', $mw1, $mw2, $callable);
        $this->app->call();
        $this->assertEquals('foobarxyz', $this->app['response']->getBody());
        $this->assertEquals('/bar', $route->getPattern());
        $this->assertSame($callable, $route->getCallable());
    }

    /**
    * Test route groups
    */
    public function testRouteGroups()
    {
        // Prepare local-scope app
        $app = $this->createApp(array(
            'SCRIPT_NAME' => '/foo/index.php',
            'REQUEST_URI' => '/foo/bar/baz'
        ));

        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "xyz"; };
        $app->group('/bar', $mw1, function () use ($app, $mw2, $callable) {
            $app->get('/baz', $mw2, $callable);
        });
        $app->call();
        $this->assertEquals('foobarxyz', $app['response']->getBody());
    }

    /*
     * Test ANY route
     */
    public function testAnyRoute()
    {
        $mw1 = function () { echo "foo"; };
        $mw2 = function () { echo "bar"; };
        $callable = function () { echo "xyz"; };
        $methods = array('GET', 'POST', 'PUT', 'DELETE', 'OPTIONS');

        foreach ($methods as $method) {
            $this->initializeApp(array(
                'REQUEST_METHOD' => $method
            ));
            $route = $this->app->any('/bar', $mw1, $mw2, $callable);
            $this->app->call();
            $this->assertEquals('foobarxyz', $this->app['response']->getBody());
            $this->assertEquals('/bar', $route->getPattern());
            $this->assertSame($callable, $route->getCallable());
        }
    }

    /**
     * Test if route does NOT expect trailing slash and URL has one
     */
    public function testRouteWithoutSlashAndUrlWithOne()
    {
        $this->initializeApp(array(
            'REQUEST_URI' => '/foo/bar/' // <-- Trailing slash
        ));

        $this->app->get('/bar', function () { echo "xyz"; });
        $this->app->call();
        $this->assertEquals(404, $this->app['response']->getStatus());
    }

    /**
      * Tests if route will match in case-insensitive manner if configured to do so
      */
     public function testRouteMatchesInCaseInsensitiveMannerIfConfigured()
     {
        $this->initializeApp(
            array(
                'REQUEST_URI' => '/foo/BaR' // Does not match route case
            ),
            array(
                'routes.case_sensitive' => false
            )
        );

         $route = $this->app->get('/bar', function () { echo "xyz"; });
         $this->app->call();
         $this->assertEquals(200, $this->app['response']->getStatus());
         $this->assertEquals('xyz', $this->app['response']->getBody());
         $this->assertEquals('/bar', $route->getPattern());
     }

    /**
     * Test if route contains URL encoded characters
     */
    public function testRouteWithUrlEncodedCharacters()
    {
        $this->initializeApp(array(
            'REQUEST_URI' => '/foo/bar/jo%20hn/smi%20th'
        ));

        $this->app->get('/bar/:one/:two', function ($one, $two) { echo $one . $two; });
        $this->app->call();
        $this->assertEquals('jo hnsmi th', $this->app['response']->getBody());
    }

    /************************************************
     * File Streaming
     ************************************************/

    public function testStreamingAFile()
    {
        $this->expectOutputString(file_get_contents(dirname(__DIR__) . "/composer.json"));

        $app = $this->createApp();
        $app->get('/bar', function() use ($app) {
            $app->sendFile(dirname(__DIR__) . "/composer.json");
        });
        $app->run();
    }

    public function testStreamingAFileWithContentType()
    {
        $this->expectOutputString(file_get_contents(dirname(__DIR__) . "/composer.json"));

        $app = $this->createApp();
        $header = $app['response']->getHeaders();
        $app->get('/bar', function() use ($app) {
            $app->sendFile(dirname(__DIR__) . "/composer.json", 'application/json');
        });
        $app->run();
        $this->assertEquals('application/json', $header->get('Content-Type'));
    }

    public function testStreamingAProc()
    {
        $this->expectOutputString("FooBar\n");

        $app = $this->createApp();
        $app->get('/bar', function() use ($app) {
            $app->sendProcess("echo 'FooBar'");
        });
        $app->run();
    }

    /************************************************
     * VIEW
     ************************************************/

    /**
     * Test set view with object instance
     */
    public function testSetSlimViewFromInstance()
    {
        $this->initializeApp(array(), array(
            'view' => new CustomView(dirname(__FILE__) . '/templates')
        ));

        $this->assertInstanceOf('CustomView', $this->app['view']);
    }

    /************************************************
     * RENDERING
     ************************************************/

    /**
     * Test render with template and data
     */
    public function testRenderTemplateWithData()
    {
        $app = $this->createApp(array(), array(
            'view' => new \Slim\View(dirname(__FILE__) . '/templates')
        ));
        $app->get('/bar', function () use ($app) {
            $app->render('test.php', array('foo' => 'bar', 'abc' => '123'));
        });
        $app->call();
        list($status, $header, $body) = $app['response']->finalize();

        $this->assertEquals(200, $status);
        $this->assertEquals('test output bar 123', $body);
    }

    /**
     * Test render with template and data and status
     */
    public function testRenderTemplateWithDataAndStatus()
    {
        $app = $this->createApp(array(), array(
            'view' => new \Slim\View(dirname(__FILE__) . '/templates')
        ));
        $app->get('/bar', function () use ($app) {
            $app->render('test.php', array('foo' => 'bar', 'abc' => '123'), 500);
        });
        $app->call();
        list($status, $header, $body) = $app['response']->finalize();

        $this->assertEquals(500, $status);
        $this->assertEquals('test output bar 123', $body);
    }

    /************************************************
     * HTTP CACHING
     ************************************************/

    /**
     * Test Last-Modified match
     */
    public function testLastModifiedMatch()
    {
        $app = $this->createApp(array(
            'HTTP_IF_MODIFIED_SINCE' => 'Sun, 03 Oct 2010 21:00:52 GMT'
        ));
        $app->get('/bar', function () use ($app) {
            $app->lastModified(1286139652);
        });
        $app->call();

        $this->assertEquals(304, $app['response']->getStatus());
    }

    /**
     * Test Last-Modified match
     */
    public function testLastModifiedDoesNotMatch()
    {
        $app = $this->createApp(array(
            'HTTP_IF_MODIFIED_SINCE' => 'Sun, 03 Oct 2010 21:00:52 GMT'
        ));
        $app->get('/bar', function () use ($app) {
            $app->lastModified(1286139250);
        });
        $app->call();

        $this->assertEquals(200, $app['response']->getStatus());
    }

    /**
     * Test Last-Modified only accepts integers
     */
    public function testLastModifiedOnlyAcceptsIntegers()
    {
        $this->setExpectedException('\InvalidArgumentException');

        $app = $this->createApp();
        $app->get('/bar', function () use ($app) {
            $app->lastModified('Test');
        });
        $app->call();
    }

    /**
     * Test Last Modified header format
     */
    public function testLastModifiedHeaderFormat()
    {
        $app = $this->createApp();
        $app->get('/bar', function () use ($app) {
            $app->lastModified(1286139652);
        });
        $app->call();
        list($status, $header, $body) = $app['response']->finalize();

        $this->assertFalse(is_null($header->get('Last-Modified')));
        $this->assertEquals('Sun, 03 Oct 2010 21:00:52 GMT', $header->get('Last-Modified'));
    }

    /**
     * Test ETag matches
     */
    public function testEtagMatches()
    {
        $app = $this->createApp(array(
            'HTTP_IF_NONE_MATCH' => '"abc123"'
        ));
        $app->get('/bar', function () use ($app) {
            $app->etag('abc123');
        });
        $app->call();

        $this->assertEquals(304, $app['response']->getStatus());
    }

    /**
     * Test ETag does not match
     */
    public function testEtagDoesNotMatch()
    {
        $app = $this->createApp(array(
            'HTTP_IF_NONE_MATCH' => '"abc1234"'
        ));
        $app->get('/bar', function () use ($app) {
            $app->etag('abc123');
        });
        $app->call();

        $this->assertEquals(200, $app['response']->getStatus());
    }

    /**
     * Test ETag with invalid type
     */
    public function testETagWithInvalidType()
    {
        $this->setExpectedException('\InvalidArgumentException');

        $app = $this->createApp(array(
            'HTTP_IF_NONE_MATCH' => '"abc1234"'
        ));
        $app->get('/bar', function () use ($app) {
            $app->etag('123','foo');
        });
        $app->call();
    }

    /**
     * Test Expires
     */
    public function testExpiresAsString()
    {
        $now = strtotime('5 days');
        $expectedDate = gmdate('D, d M Y H:i:s T', $now);

        $app = $this->createApp();
        $app->get('/bar', function () use ($app, $now) {
            $app->expires($now);
        });
        $app->call();
        list($status, $header, $body) = $app['response']->finalize();

        $this->assertFalse(is_null($header->get('Expires')));
        $this->assertEquals($header->get('Expires'), $expectedDate);
    }

    /**
     * Test Expires
     */
    public function testExpiresAsInteger()
    {
        $fiveDaysFromNow = time() + (60 * 60 * 24 * 5);
        $expectedDate = gmdate('D, d M Y H:i:s T', $fiveDaysFromNow);

        $app = $this->createApp();
        $app->get('/bar', function () use ($app, $fiveDaysFromNow) {
            $app->expires($fiveDaysFromNow);
        });
        $app->call();
        list($status, $header, $body) = $app['response']->finalize();

        $this->assertFalse(is_null($header->get('Expires')));
        $this->assertEquals($header->get('Expires'), $expectedDate);
    }

    /************************************************
     * COOKIES
     ************************************************/

    /**
     * Set cookie
     *
     * This tests that the Slim application instance sets
     * a cookie in the HTTP response header. This does NOT
     * test the implementation of setting the cookie; that is
     * tested in a separate file.
     */
    public function testSetCookie()
    {
        $app = $this->createApp();
        $app->get('/bar', function () use ($app) {
            $app->setCookie('foo', 'bar', '2 days');
            $app->setCookie('foo1', 'bar1', '2 days');
        });
        $app->call();
        $cookie1 = $app['response']->getCookies()->get('foo');
        $cookie2 = $app['response']->getCookies()->get('foo1');

        $this->assertEquals(2, count($app['response']->getCookies()));
        $this->assertEquals('bar', $cookie1['value']);
        $this->assertEquals('bar1', $cookie2['value']);
    }

    /**
     * Test get cookie
     *
     * This method ensures that the `Cookie:` HTTP request
     * header is parsed if present, and made accessible via the
     * Request object.
     */
    public function testGetCookie()
    {
        $this->initializeApp(array(
            'HTTP_COOKIE' => 'foo=bar; foo2=bar2'
        ));

        $this->assertEquals('bar', $this->app->getCookie('foo'));
        $this->assertEquals('bar2', $this->app->getCookie('foo2'));
        $this->assertNull($this->app->getCookie('foo3'));
    }

    /**
     * Test delete cookie
     *
     * This method ensures that the `Set-Cookie:` HTTP response
     * header is set. The implementation of setting the response
     * cookie is tested separately in another file.
     */
    public function testDeleteCookie()
    {
        $app = $this->createApp(array(
            'HTTP_COOKIE' => 'foo=bar; foo2=bar2'
        ));
        $app->get('/bar', function () use ($app) {
            $app->deleteCookie('foo');
        });
        $app->call();
        $cookie = $app['response']->getCookies()->get('foo');

        $this->assertEquals(1, count($app['response']->getCookies()));
        $this->assertEquals('', $cookie['value']);
        $this->assertLessThan(time(), $cookie['expires']);
    }

    /************************************************
     * HELPERS
     ************************************************/

    /**
     * Test get root directory
     */
    public function testGetRoot()
    {
        $app = $this->createApp(array('SCRIPT_FILENAME' => '/var/www/index.php'));
        $this->assertEquals('/var/www', $app->root());
    }

    /**
     * Test get root directory when server variable is not present
     */
    public function testGetRootWithoutServerVariable()
    {
        $this->setExpectedException('\RuntimeException');

        $this->app['environment']->remove('SCRIPT_FILENAME');
        $this->app->root();
    }

    /**
     * Test stop
     */
    public function testStop()
    {
        $this->setExpectedException('\Slim\Exception\Stop');

        $this->app->stop();
    }

    /**
     * Test stop with subsequent output
     */
    public function testStopWithSubsequentOutput()
    {
        $app = $this->createApp();
        $app->get('/bar', function () use ($app) {
            echo "Foo"; //<-- Should be in response body
            $app->stop();
            echo "Bar"; //<-- Should not be in response body
        });
        $app->call();

        $this->assertEquals('Foo', $app['response']->getBody());
    }

    /**
     * Test stop with output buffer on and pre content
     */
    public function testStopOutputWithOutputBufferingOnAndPreContent()
    {
        $this->expectOutputString('1.2.Foo.3'); //<-- PHP unit uses OB here
        echo "1.";

        $app = $this->createApp();
        $app->get('/bar', function () use ($app) {
            echo "Foo";
            $app->stop();
        });
        echo "2.";
        $app->run(); // <-- Needs to be run to actually echo body
        echo ".3";
    }

    /**
     * Test stop does not leave output buffers open
     */
    public function testStopDoesNotLeaveOutputBuffersOpen()
    {
        $level_start = ob_get_level();

        $app = $this->createApp();
        $app->get('/bar', function () use ($app) {
            $app->stop();
        });
        $app->run();

        $this->assertEquals($level_start, ob_get_level());
    }

    /**
     * Test halt
     */
    public function testHalt()
    {
        $app = $this->createApp();
        $app->get('/bar', function () use ($app) {
            echo "Foo!"; //<-- Should not be in response body!
            $app->halt(500, 'Something broke');
        });
        $app->call();
        list($status, $header, $body) = $app['response']->finalize();

        $this->assertEquals(500, $status);
        $this->assertEquals('Something broke', $body);
    }

    /**
     * Test halt with output buffering and pre content
     */
    public function testHaltOutputWithOutputBufferingOnAndPreContent()
    {
        $this->expectOutputString('1.2.Something broke.3'); // <-- PHP unit uses OB here

        echo "1.";
        $app = $this->createApp();
        $app->get('/bar', function () use ($app) {
            echo "Foo!"; // <-- Should not be in response body!
            $app->halt(500, 'Something broke');
        });
        echo "2.";
        $app->run();
        echo ".3";
    }

    /**
     * Test halt does not leave output buffers open
     */
    public function testHaltDoesNotLeaveOutputBuffersOpen()
    {
        $level_start = ob_get_level();

        $app = $this->createApp();
        $app->get('/bar', function () use ($app) {
            $app->halt(500, '');
        });
        $app->run();

        $this->assertEquals($level_start, ob_get_level());
    }

    /**
     * Test pass cleans buffer and throws exception
     */
    public function testPass()
    {
        ob_start();
        echo "Foo";
        try {
            $this->app->pass();
            $this->fail('Did not catch Slim_Exception_Pass');
        } catch (\Slim\Exception\Pass $e) {}
        $output = ob_get_clean();
        $this->assertEquals('', $output);
    }

    /**
     * Test pass when there is a subsequent fallback route
     */
    public function testPassWithSubsequentRoute()
    {
        $app = $this->createApp(array(
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/name/Frank'
        ));
        $app->get('/name/Frank', function () use ($app) {
            echo "Fail"; // <-- Should not be in response body!
            $app->pass();
        });
        $app->get('/name/:name', function ($name) {
            echo $name; // <-- Should be in response body!
        });
        $app->call();

        $this->assertEquals('Frank', $app['response']->getBody());
    }

    /**
     * Test pass when there is not a subsequent fallback route
     */
    public function testPassWithoutSubsequentRoute()
    {
        $app = $this->createApp(array(
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/name/Frank'
        ));
        $app->get('/name/Frank', function () use ($app) {
            echo "Fail"; // <-- Should not be in response body!
            $app->pass();
        });
        $app->call();

        $this->assertEquals(404, $app['response']->getStatus());
    }

    /**
     * Test content type
     */
    public function testContentType()
    {
        $this->app->contentType('application/json');
        $this->assertEquals('application/json', $this->app['response']->getHeaders()->get('Content-Type'));
    }

    /**
     * Test status
     */
    public function testStatus()
    {
        $this->app->status(403);
        $this->assertEquals(403, $this->app['response']->getStatus());
    }

    /**
     * Test URL for
     */
    public function testUrlFor()
    {
        $this->app->get('/hello/:name', function () {})->name('hello');
        $this->assertEquals('/foo/hello/Josh', $this->app->urlFor('hello', array('name' => 'Josh'))); // <-- Prepends physical path!
    }

    /**
     * Test redirect sets status and header
     */
    public function testRedirect()
    {
        $this->setExpectedException('\Slim\Exception\Stop'); // <-- Thrown by redirect() method
        $this->app->redirect('/somewhere/else', 303);
        $this->assertEquals(303, $this->app['response']->getStatus());
        $this->assertEquals('/somewhere/else', $this->app['response']->getHeaders()->get('Location'));
        $this->assertEquals('', $this->app['response']->getBody());
    }

    /************************************************
     * RUNNER
     ************************************************/

    /**
     * Test that runner sends headers and body
     */
    public function testRun()
    {
        $this->expectOutputString('Foo');

        $this->app->get('/bar', function () {
            echo "Foo";
        });
        $this->app->run();
    }

    /**
     * Test runner output with output buffering on and pre content
     */
    public function testRunOutputWithOutputBufferingOnAndPreContent()
    {
        $this->expectOutputString('1.2.Foo.3');  // <-- PHP unit uses OB here

        echo "1.";
        $this->app->get('/bar', function () {
            echo "Foo";
        });
        echo "2.";
        $this->app->run();
        echo ".3";
    }

    /**
     * Test that runner does not leave output buffers open
     */
    public function testRunDoesNotLeaveAnyOutputBuffersOpen()
    {
        $level_start = ob_get_level();
        $this->app->get('/bar', function () {});
        $this->app->run();

        $this->assertEquals($level_start, ob_get_level());
    }

    /************************************************
     * MIDDLEWARE
     ************************************************/

    /**
     * Test add middleware
     *
     * This asserts that middleware are queued and called
     * in sequence. This also asserts that the environment
     * variables are passed by reference.
     */
    public function testAddMiddleware()
    {
        $this->expectOutputString('FooHello');

        $this->app->add(new CustomMiddleware()); //<-- See top of this file for class definition
        $this->app->get('/bar', function () {
            echo 'Foo';
        });
        $this->app->run();

        $this->assertEquals('Hello', $this->app['response']->getHeaders()->get('X-Slim-Test'));
    }

    /**
     * Test exception when adding circular middleware queues
     *
     * This asserts that the same middleware can NOT be queued twice (usually by accident).
     *
     * Circular middleware stack causes a troublesome to debug PHP Fatal error,
     * mostly due to a quite opaque error message:
     *
     * > Fatal error: Maximum function nesting level of '100' reached. aborting!
     */
    public function testFailureWhenAddingCircularMiddleware()
    {
        $this->setExpectedException('\RuntimeException');
        $middleware = new CustomMiddleware();
        $this->app->add($middleware);
        $this->app->add($middleware);
        $this->app->run();
    }

    /************************************************
     * NOT FOUND HANDLING
     ************************************************/

    /**
     * Test custom Not Found handler
     */
    public function testNotFound()
    {
        $this->app->notFound(function () {
            echo "Not Found";
        });
        $this->app->get('/foo', function () {});
        $this->app->call();
        list($status, $header, $body) = $this->app['response']->finalize();

        $this->assertEquals(404, $status);
        $this->assertEquals('Not Found', $body);
    }

    /************************************************
     * ERROR HANDLING
     ************************************************/

    /**
     * Test default and custom error handlers
     *
     * Pre-conditions:
     * Invoked app route calls default error handler;
     *
     * Post-conditions:
     * Response status code is 500;
     */
    public function testSlimError()
    {
        $app = $this->createApp();
        $app->get('/bar', function () use ($app) {
            $app->error();
        });
        $app->call();

        $this->assertEquals(500, $app['response']->getStatus());
    }

    /**
     * Test triggered errors are converted to ErrorExceptions
     *
     * Pre-conditions:
     * Custom error handler defined;
     * Invoked app route triggers error;
     *
     * Post-conditions:
     * Response status is 500;
     * Response body is equal to triggered error message;
     * Error handler's argument is ErrorException instance;
     */
    public function testTriggeredErrorsAreConvertedToErrorExceptions()
    {
        $this->expectOutputString('Foo I say!');

        $this->initializeApp(array(), array('debug' => false));
        $this->app->error(function ($e) {
            if ($e instanceof \ErrorException) {
                echo $e->getMessage();
            }
        });
        $this->app->get('/bar', function () {
            trigger_error('Foo I say!');
        });
        $this->app->run();
        list($status, $header, $body) = $this->app['response']->finalize();

        $this->assertEquals(500, $status);
        $this->assertEquals('Foo I say!', $body);
    }

    /**
     * Test custom error handler uses existing Response object
     */
    public function testErrorHandlerUsesCurrentResponseObject()
    {
        $this->expectOutputString('Foo');

        $app = $this->createApp(array(), array('debug' => false));
        $app->error(function(\Exception $e) use ($app) {
            $r = $app['response'];
            $r->setStatus(503);
            $r->write('Foo');
            $r->getHeaders()->set('X-Powered-By', 'Slim');
        });
        $app->get('/bar', function () {
            throw new \Exception('Foo');
        });
        $app->run();
        list($status, $header, $body) = $app['response']->finalize();

        $this->assertEquals(503, $status);
        $this->assertEquals('Slim', $header->get('X-Powered-By'));
    }

    /**
     * Test custom global error handler
     */
    public function testHandleErrors()
    {
        $defaultErrorReporting = error_reporting();

        // Test 1
        error_reporting(E_ALL ^ E_NOTICE); // <-- Report all errors EXCEPT notices
        try {
            \Slim\App::handleErrors(E_NOTICE, 'test error', 'Slim.php', 119);
        } catch (\ErrorException $e) {
            $this->fail('Slim::handleErrors reported a disabled error level.');
        }

        // Test 2
        error_reporting(E_ALL | E_STRICT); // <-- Report all errors, including E_STRICT
        try {
            \Slim\App::handleErrors(E_STRICT, 'test error', 'Slim.php', 119);
            $this->fail('Slim::handleErrors didn\'t report a enabled error level');
        } catch (\ErrorException $e) {}

        error_reporting($defaultErrorReporting);
    }

    /**
     * Slim should keep reference to a callable error callback
     */
    public function testErrorHandler() {
        $errCallback = function () { echo "404"; };
        $this->app->error($errCallback);
        $this->assertSame($errCallback, $this->app['error']);
    }

    /**
     * Slim should throw a Slim_Exception_Stop if error callback is not callable
     */
    public function testErrorHandlerIfNotCallable() {
        $this->setExpectedException('\Slim\Exception\Stop');
        $this->app->error('foo');
    }

    /**
     * Slim should keep reference to a callable NotFound callback
     */
    public function testNotFoundHandler() {
        $notFoundCallback = function () { echo "404"; };
        $this->app->notFound($notFoundCallback);
        $this->assertSame($notFoundCallback, $this->app['notFound']);
    }

    /**
     * Slim should throw a Slim_Exception_Stop if NotFound callback is not callable
     */
    public function testNotFoundHandlerIfNotCallable() {
        $this->setExpectedException('\Slim\Exception\Stop');
        $this->app->notFound('foo');
    }

    /************************************************
     * HOOKS
     ************************************************/

    /**
     * Test hook listener
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Hook name does not exist;
     * Listeners are callable objects;
     *
     * Post-conditions:
     * Callables are invoked in expected order;
     */
    public function testRegistersAndCallsHooksByPriority()
    {
        $this->expectOutputString('barfoo');

        $callable1 = function () { echo "foo"; };
        $callable2 = function () { echo "bar"; };
        $this->app->hook('test.hook.one', $callable1); // default is 10
        $this->app->hook('test.hook.one', $callable2, 8);
        $hooks = $this->app->getHooks();
        $this->assertEquals(7, count($hooks)); //6 default, 1 custom
        $this->app->applyHook('test.hook.one');
    }

    /**
     * Test hook listener if listener is not callable
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Hook name does not exist;
     * Listener is NOT a callable object;
     *
     * Post-conditions:
     * Hook is created;
     * Callable is NOT assigned to hook;
     */
    public function testHookInvalidCallable()
    {
        $this->app->hook('test.hook.one', 'test'); // NOT callable
        $this->assertEquals(array(array()), $this->app->getHooks('test.hook.one'));
    }

    /**
     * Test hook invocation if hook does not exist
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Hook name does not exist;
     *
     * Post-conditions:
     * Hook is created;
     * Hook initialized with empty array;
     */
    public function testHookInvocationIfNotExists()
    {
        $this->app->applyHook('test.hook.one');
        $this->assertEquals(array(array()), $this->app->getHooks('test.hook.one'));
    }

    /**
     * Test clear hooks
     *
     * Pre-conditions:
     * Slim app instantiated;
     * Two hooks exist, each with one listener;
     *
     * Post-conditions:
     * Case A: Listeners for 'test.hook.one' are cleared;
     * Case B: Listeners for all hooks are cleared;
     */
    public function testHookClear()
    {
        $this->app->hook('test.hook.one', function () {});
        $this->app->hook('test.hook.two', function () {});
        $this->app->clearHooks('test.hook.two');
        $this->assertEquals(array(array()), $this->app->getHooks('test.hook.two'));
        $hookOne = $this->app->getHooks('test.hook.one');
        $this->assertTrue(count($hookOne[10]) === 1);
        $this->app->clearHooks();
        $this->assertEquals(array(array()), $this->app->getHooks('test.hook.one'));
    }
}
