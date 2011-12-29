<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.5.2
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

require_once 'Slim/Middleware/Interface.php';
require_once 'Slim/Middleware/ContentTypes.php';

class CustomApp {
    function call( &$env ) {
        return $env['slim.input'];
    }
}

class ContentTypesTest extends PHPUnit_Framework_TestCase {
    /**
     * Test parses JSON
     */
    public function testParsesJson() {
        $env = array(
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'CONTENT_TYPE' => 'application/json',
            'CONENT_LENGTH' => 13,
            'slim.url_scheme' => 'http',
            'slim.input' => '{"foo":"bar"}',
            'slim.errors' => fopen('php://stderr', 'w')
        );
        $app = new CustomApp();
        $mw = new Slim_Middleware_ContentTypes($app);
        $result = $mw->call($env);
        $this->assertTrue(is_array($result));
        $this->assertEquals('bar', $result['foo']);
    }

    /**
     * Test ignores JSON with errors
     */
    public function testParsesJsonWithError() {
        $env = array(
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'CONTENT_TYPE' => 'application/json',
            'CONENT_LENGTH' => 12,
            'slim.url_scheme' => 'http',
            'slim.input' => '{"foo":"bar"',
            'slim.errors' => fopen('php://stderr', 'w')
        );
        $app = new CustomApp();
        $mw = new Slim_Middleware_ContentTypes($app);
        $result = $mw->call($env);
        $this->assertTrue(is_string($result));
        $this->assertEquals('{"foo":"bar"', $result);
    }

    /**
     * Test parses XML
     */
    public function testParsesXml() {
        $env = array(
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'CONTENT_TYPE' => 'application/xml',
            'CONENT_LENGTH' => 68,
            'slim.url_scheme' => 'http',
            'slim.input' => '<books><book><id>1</id><author>Clive Cussler</author></book></books>',
            'slim.errors' => fopen('php://stderr', 'w')
        );
        $app = new CustomApp();
        $mw = new Slim_Middleware_ContentTypes($app);
        $result = $mw->call($env);
        $this->assertInstanceOf('SimpleXMLElement', $result);
        $this->assertEquals('Clive Cussler', (string)$result->book->author);
    }

    /**
     * Test ignores XML with errors
     */
    public function testParsesXmlWithError() {
        $env = array(
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'CONTENT_TYPE' => 'application/xml',
            'CONENT_LENGTH' => 68,
            'slim.url_scheme' => 'http',
            'slim.input' => '<books><book><id>1</id><author>Clive Cussler</book></books>',
            'slim.errors' => fopen('php://stderr', 'w')
        );
        $app = new CustomApp();
        $mw = new Slim_Middleware_ContentTypes($app);
        $result = $mw->call($env);
        $this->assertTrue(is_string($result));
        $this->assertEquals('<books><book><id>1</id><author>Clive Cussler</book></books>', $result);
    }

    /**
     * Test parses CSV
     */
    public function testParsesCsv() {
        $env = array(
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/foo/index.php', //<-- Physical
            'PATH_INFO' => '/bar', //<-- Virtual
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'SERVER_NAME' => 'slim',
            'SERVER_PORT' => 80,
            'CONTENT_TYPE' => 'text/csv',
            'CONENT_LENGTH' => 44,
            'slim.url_scheme' => 'http',
            'slim.input' => "John,Doe,000-111-2222\nJane,Doe,111-222-3333",
            'slim.errors' => fopen('php://stderr', 'w')
        );
        $app = new CustomApp();
        $mw = new Slim_Middleware_ContentTypes($app);
        $result = $mw->call($env);
        $this->assertTrue(is_array($result));
        $this->assertEquals(2, count($result));
        $this->assertEquals('000-111-2222', $result[0][2]);
        $this->assertEquals('Doe', $result[1][1]);
    }
}