<?php
/**
 * Slim
 *
 * A simple PHP framework for PHP 5 or newer
 *
 * @author		Josh Lockhart <info@joshlockhart.com>
 * @link		http://slim.joshlockhart.com
 * @copyright	2010 Josh Lockhart
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

require_once '../slim/Response.php';
require_once '../slim/Cookie.php';
require_once 'PHPUnit/Extensions/OutputTestCase.php';

class ResponseTest extends PHPUnit_Extensions_OutputTestCase	 {

	/**
	 * Test default response
	 *
	 * Pre-conditions:
	 * A response is instantiated
	 *
	 * Post-conditions:
	 * Response status is 200;
	 * Headers array has "text/html" Content-Type
	 * Cookies array is empty
	 */
	public function testNewResponse() {
		$r = new Response();
		$this->assertEquals($r->status(), 200);
		$this->assertEquals($r->getCookies(), array());
		$this->assertEquals($r->headers(), array('Content-Type' => 'text/html'));
	}

	/**
	 * Test status
	 *
	 * Pre-conditions:
	 * Case A: Status code is a valid HTTP status code
	 * Case B: Status code is not a valid HTTP status code
	 *
	 * Post-conditions:
	 * Case A: The response status code is set and returned
	 * Case B: An InvalidArgumentException is thrown
	 */
	public function testResponseStatus() {
		//Case A
		$r1 = new Response();
		$newStatus = $r1->status(201);
		$this->assertEquals($newStatus, 201);
		
		//Case B
		$r2 = new Response();
		try {
			$r2->status(700);
			$this->fail('Did not throw exception when status code invalid');
		} catch ( InvalidArgumentException $e ) {}
	}
	
	/**
	 * Test headers
	 *
	 * Pre-conditions:
	 * Case A: Set Content-Type to 'application/json'
	 * Case B: Get non-existent header
	 *
	 * Post-conditions:
	 * Case A: Header is set correctly
	 * Case B: Returned value is NULL
	 */
	public function testResponseHeaders() {
		//Case A
		$r1 = new Response();
		$r1->header('Content-Type', 'application/json');
		$this->assertEquals($r1->header('Content-Type'), 'application/json');
		$this->assertEquals($r1->headers(), array('Content-Type' => 'application/json'));
		
		//Case B
		$this->assertNull($r1->header('foo'));
	}
	
	/**
	 * Test body and write
	 *
	 * Pre-conditions:
	 * Case A: Response body set to "Foo bar"
	 * Case B: Same response body is changed to "abc123"
	 * Case C: Same response body is appended with "xyz"
	 *
	 * Post-conditions:
	 * Case A: Response body is "Foo bar", and Content-Length = 7
	 * Case B: Response body is "abc123" and Content-Length = 6
	 * Case C: Response body is "abc123xyz" and Content-Length = 9
	 */
	public function testBody() {
		//Case A
		$r1 = new Response();
		$r1->body('Foo bar');
		$this->assertEquals($r1->body(), 'Foo bar');
		$this->assertEquals($r1->header('Content-Length'), 7);
		
		//Case B
		$r1->body('abc123');
		$this->assertEquals($r1->body(), 'abc123');
		$this->assertEquals($r1->header('Content-Length'), 6);
		
		//Case C
		$r1->write('xyz');
		$this->assertEquals($r1->body(), 'abc123xyz');
		$this->assertEquals($r1->header('Content-Length'), 9);
	}
	
	/**
	 * Test response cookies
	 *
	 * Pre-conditions:
	 * A response is instantiated and assigned cookies
	 *
	 * Post-conditions:
	 * Case A: Array of cookies returned;
	 * Case B: Cookies with given names are returned;
	 * Case C: Returns NULL if cookie with given name does not exist;
	 */
	public function testCookies() {
		$r1 = new Response();
		$cookie1 = new Cookie('foo1', 'bar1');
		$cookie2 = new Cookie('foo2', 'bar2');
		$cookie3 = new Cookie('foo3', 'bar3');
		$r1->addCookie($cookie1);
		$r1->addCookie($cookie2);
		$r1->addCookie($cookie3);
		//Case A:
		$cookies = $r1->getCookies();
		$this->assertEquals(count($cookies), 3);
		//Case B:
		$this->assertSame($cookie1, $r1->getCookie('foo1'));
		$this->assertSame($cookie2, $r1->getCookie('foo2'));
		$this->assertSame($cookie3, $r1->getCookie('foo3'));
		//Case C:
		$this->assertNull($r1->getCookie('doesNotExist'));
	}
	
	/**
	 * Test finalize
	 *
	 * Pre-conditions:
	 * Case A: Response status is 200
	 * Case B: Response status is 204
	 * Case C: Response status is 304
	 *
	 * Post-conditions:
	 * Case A: Response has body and content-length
	 * Case B: Response does not have body and content-length
	 * Case C: Response does not have body and content-length
	 */
	public function testFinalize() {
		//Case A
		$r1 = new Response();
		$r1->body('body1');
		$r1->finalize();
		$this->assertEquals($r1->body(), 'body1');
		$this->assertEquals($r1->header('Content-Length'), 5);
		
		//Case B
		$r2 = new Response();
		$r2->body('body2');
		$r2->status(204);
		$r2->finalize();
		$this->assertEquals($r2->body(), '');
		$this->assertNull($r2->header('Content-Type'));
		
		//Case C
		$r3 = new Response();
		$r3->body('body3');
		$r3->status(304);
		$r3->finalize();
		$this->assertEquals($r3->body(), '');
		$this->assertNull($r3->header('Content-Type'));
	}
	
	/**
	 * Test get messages for code
	 *
	 * Pre-conditions:
	 * Case A: Status = 200
	 * Case B: Status = 304
	 * Case C: Status = 420 //Fake
	 *
	 * Post-conditions:
	 * Case A: Message = '200 OK'
	 * Case B: Message = '304 Not Modified'
	 * Case C: Message = NULL
	 */
	public function testGetMessageForCode() {
		//Case A
		$this->assertEquals(Response::getMessageForCode(200), '200 OK');
		
		//Case B
		$this->assertEquals(Response::getMessageForCode(304), '304 Not Modified');
		
		//Case C
		$this->assertNull(Response::getMessageForCode(420));
	}
	
	/**
	 * Test can have body
	 *
	 * Pre-conditions:
	 * Case A: Status code = 100
	 * Case B: Status code = 200
	 * Case C: Status code = 204
	 * Case D: Status code = 304
	 *
	 * Post-conditions:
	 * Case A: false
	 * Case B: true
	 * Case C: false
	 * Case D: false
	 */
	public function testCanHaveBody() {
		$r1 = new Response();
		
		//Case A
		$r1->status(100);
		$this->assertFalse($r1->canHaveBody());
		
		//Case B
		$r1->status(200);
		$this->assertTrue($r1->canHaveBody());
		
		//Case C
		$r1->status(204);
		$this->assertFalse($r1->canHaveBody());
		
		//Case D
		$r1->status(304);
		$this->assertFalse($r1->canHaveBody());
	}

	/**
	 * Test send response
	 *
	 * Pre-conditions:
	 * Response instantiated with body "foo bar"
	 *
	 * Post-conditions:
	 * Output buffer will equal "foo bar"
	 */
	function testSendResponse() {
		$this->expectOutputString('foo bar');
		$r1 = new Response();
		$r1->body('foo bar');
		$r1->send();
	}
}

?>