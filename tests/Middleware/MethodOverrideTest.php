<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@joshlockhart.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.5.0
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

set_include_path(dirname(__FILE__) . '/../' . PATH_SEPARATOR . get_include_path());

require_once 'Slim/Http/Util.php';
require_once 'Slim/Http/Request.php';
require_once 'Slim/Middleware/MethodOverride.php';

class CustomApp {
    function call( &$env ) {
        return $env['REQUEST_METHOD'];
    }
}

class MethodOverrideTest extends PHPUnit_Extensions_OutputTestCase {
    /**
     * Test overrides method as POST
     */
    public function testOverrideMethodAsPost() {
        $env = array(
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONENT_LENGTH' => 11,
            'slim.url_scheme' => 'http',
            'slim.input' => '_METHOD=PUT',
            'slim.errors' => fopen('php://stderr', 'w')
        );
        $app = new CustomApp();
        $mw = new Slim_Middleware_MethodOverride($app);
        $result = $mw->call($env);
        $this->assertEquals('PUT', $result);
        $this->assertArrayHasKey('slim.method_override.original_method', $env);
        $this->assertEquals('POST', $env['slim.method_override.original_method']);
    }

    /**
     * Test does not override method if not POST
     */
    public function testDoesNotOverrideMethodIfNotPost() {
        $env = array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => '_METHOD=PUT',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w')
        );
        $app = new CustomApp();
        $mw = new Slim_Middleware_MethodOverride($app);
        $result = $mw->call($env);
        $this->assertEquals('GET', $result);
        $this->assertFalse(isset($env['slim.method_override.original_method']));
    }

    /**
     * Test does not override method if no method ovveride parameter
     */
    public function testDoesNotOverrideMethodAsPostWithoutParameter() {
        $env = array(
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'foo=bar',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'slim.url_scheme' => 'http',
            'slim.input' => '',
            'slim.errors' => fopen('php://stderr', 'w')
        );
        $app = new CustomApp();
        $mw = new Slim_Middleware_MethodOverride($app);
        $result = $mw->call($env);
        $this->assertEquals('POST', $result);
        $this->assertFalse(isset($env['slim.method_override.original_method']));
    }
}