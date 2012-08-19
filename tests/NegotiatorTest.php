<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2012 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.6.4
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

require_once 'Slim/Exception/Stop.php';
require_once 'Slim/Route.php';
require_once 'Slim/Negotiator.php';

class NegotiatorTest extends PHPUnit_Framework_TestCase {

    public function setUp()
    {
        $this->params = array();
        $this->negotiator = new Slim_Negotiator();
        $this->request = new NegotiatorTestableRequest();
        $this->response = new NegotiatorTestableResponse();
    }

    public function respondTo()
    {
        return $this->negotiator->respondTo(
            $this->params,
            $this->request,
            $this->response,
            func_get_args()
        );
    }

    public function testDefaultFormat()
    {
        $format = $this->respondTo('txt');
        $this->assertEquals('txt', $format);
    }

    public function testSingleArgumentValue()
    {
        $this->params = array('format' => 'txt');
        $format = $this->respondTo('txt');
        $this->assertEquals('txt', $format);
    }

    public function testSingleArgumentArray()
    {
        $this->params = array('format' => 'txt');
        $format = $this->respondTo('html', 'txt');
        $this->assertEquals('txt', $format);
    }

    public function testSingleArgumentWithPeriod()
    {
        $this->params = array('format' => '.txt');
        $format = $this->respondTo('html', 'txt');
        $this->assertEquals('txt', $format);
    }

    public function testContentTypeHeader()
    {
        $this->params = array('format' => 'txt');
        $format = $this->respondTo('html', 'txt');
        $this->assertEquals('text/plain', $this->response->headers['content-type']);
    }

    public function testForcedFormatDoesNotSetVary()
    {
        $this->params = array('format' => 'txt');
        $format = $this->respondTo('html', 'txt');
        $this->assertFalse(array_key_exists('vary', $this->response->headers));
    }

    public function testUnknownForcedFormat()
    {
        try {
            $this->params = array('format' => 'foobar');
            $this->respondTo('html');
        } catch (Slim_Exception_Stop $e) {
            $exception = $e;
        }
        $this->assertInstanceOf('Slim_Exception_Stop', $exception);
        $this->assertEquals('404', $this->response->status);
        $this->assertEquals('Unsupported format', $this->response->body);
    }

    public function testForcedFormatWithNoMimeType()
    {
        $this->params = array('format' => 'foobarbaz');
        $format = $this->respondTo('html', 'foobarbaz');
        $this->assertEquals('foobarbaz', $format);
        $this->assertFalse(
            array_key_exists('content-type', $this->response->headers));
    }

    public function testAltenativeFormatKey()
    {
        $this->negotiator->setFormatKey('type');
        $this->params = array('type' => 'xml');
        $format = $this->respondTo('html', 'xml');
        $this->assertEquals('xml', $format);
    }

    public function testEqualQValuesDecidedByRespondToOrder()
    {
        $this->request->headers['accept'] = 'text/plain, text/html';
        $this->assertEquals('html', $this->respondTo('html', 'txt'));
        $this->assertEquals('txt', $this->respondTo('txt', 'html'));
    }

    public function testSubtypeWildcardHasLowerPriority()
    {
        $this->request->headers['accept'] = 'text/plain, text/*';
        $this->assertEquals('txt', $this->respondTo('html', 'txt'));
        $this->assertEquals('txt', $this->respondTo('txt', 'html'));
    }

    public function testFullWildcardHasLowestPriority()
    {
        $this->request->headers['accept'] = 'text/plain, text/*, */*';
        $this->assertEquals('txt', $this->respondTo('xml', 'txt'));
        $this->assertEquals('txt', $this->respondTo('txt', 'html'));
    }

    public function testHighestPriorityFormatFirst()
    {
        $this->request->headers['accept'] = '*/*';
        $this->assertEquals('html', $this->respondTo('html', 'txt'));
        $this->assertEquals('txt', $this->respondTo('txt', 'html'));
    }

    public function testQValueLast()
    {
        $this->request->headers['accept'] = 'text/plain, text/html; q=0.5';
        $this->assertEquals('txt', $this->respondTo('html', 'txt'));
    }

    public function testQValueFirst()
    {
        $this->request->headers['accept'] = 'text/plain; q=0.5, text/html';
        $this->assertEquals('html', $this->respondTo('txt', 'html'));
    }

    public function testMultipleQValues()
    {
        $this->request->headers['accept'] = 'text/plain;q=0.2, text/html;q=0.4, text/turtle;q=0.8,image/png;q=0.6';
        $this->assertEquals('ttl', $this->respondTo('html', 'ttl', 'txt'));
    }

    public function testInvalidAcceptHeader()
    {
        try {
            $this->request->headers['accept'] = 'foobar';
            $this->respondTo('txt', 'html');
        } catch (Slim_Exception_Stop $e) {
            $exception = $e;
        }
        $this->assertInstanceOf('Slim_Exception_Stop', $exception);
        $this->assertEquals('406', $this->response->status);
        $this->assertEquals('Not Acceptable', $this->response->body);
    }

    public function testNoMarchAcceptHeader()
    {
        try {
            $this->request->headers['accept'] = 'image/png,image/gif';
            $this->respondTo('txt');
        } catch (Slim_Exception_Stop $e) {
            $exception = $e;
        }
        $this->assertInstanceOf('Slim_Exception_Stop', $exception);
        $this->assertEquals('406', $this->response->status);
        $this->assertEquals('Not Acceptable', $this->response->body);
    }
}

class NegotiatorTestableRequest
{
    public $headers = array();

    public function headers($key)
    {
        $key = strtolower($key);
        if ( isset($this->headers[$key]) ) {
            return $this->headers[$key];
        }
    }
}

class NegotiatorTestableResponse
{
    public $status;
    public $body;
    public $headers = array();

    public function status($status) { $this->status = $status; }

    public function body($body) { $this->body = $body; }

    public function header($key, $value)
    {
        $this->headers[strtolower($key)] = $value;
    }
}
