<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
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

class ResponseTest extends PHPUnit_Framework_TestCase
{
    public function testConstructWithoutArgs()
    {
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $res = new \Slim\Http\Response($headers, $cookies);

        $this->assertAttributeEquals(200, 'status', $res);
        $this->assertAttributeEquals('', 'body', $res);
    }

    public function testConstructWithArgs()
    {
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $res = new \Slim\Http\Response($headers, $cookies, 'Foo', 201);

        $this->assertAttributeEquals(201, 'status', $res);
        $this->assertAttributeEquals('Foo', 'body', $res);
    }

    public function testGetStatus()
    {
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $res = new \Slim\Http\Response($headers, $cookies);

        $this->assertEquals(200, $res->getStatus());
    }

    public function testSetStatus()
    {
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $res = new \Slim\Http\Response($headers, $cookies);
        $res->setStatus(301);

        $this->assertAttributeEquals(301, 'status', $res);
    }

    public function testGetBody()
    {
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $res = new \Slim\Http\Response($headers, $cookies);
        $property = new \ReflectionProperty($res, 'body');
        $property->setAccessible(true);
        $property->setValue($res, 'foo');

        $this->assertEquals('foo', $res->getBody());
    }

    public function testSetBody()
    {
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $res = new \Slim\Http\Response($headers, $cookies, 'bar');
        $res->setBody('foo'); // <-- Should replace body

        $this->assertAttributeEquals('foo', 'body', $res);
    }

    public function testWrite()
    {
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $res = new \Slim\Http\Response($headers, $cookies);
        $property = new \ReflectionProperty($res, 'body');
        $property->setAccessible(true);
        $property->setValue($res, 'foo');
        $res->write('bar'); // <-- Should append to body

        $this->assertAttributeEquals('foobar', 'body', $res);
    }

    public function testLength()
    {
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $res = new \Slim\Http\Response($headers, $cookies, 'foo'); // <-- Sets body and length

        $this->assertEquals(3, $res->getLength());
    }

    public function testFinalize()
    {
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $res = new \Slim\Http\Response($headers, $cookies);
        $resFinal = $res->finalize();

        $this->assertTrue(is_array($resFinal));
        $this->assertEquals(3, count($resFinal));
        $this->assertEquals(200, $resFinal[0]);
        $this->assertInstanceOf('\Slim\Http\Headers', $resFinal[1]);
        $this->assertEquals('', $resFinal[2]);
    }

    public function testFinalizeWithEmptyBody()
    {
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $res = new \Slim\Http\Response($headers, $cookies, 'Foo', 304);
        $resFinal = $res->finalize();

        $this->assertEquals('', $resFinal[2]);
    }

    public function testRedirect()
    {
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $res = new \Slim\Http\Response($headers, $cookies);
        $res->redirect('/foo');

        $pStatus = new \ReflectionProperty($res, 'status');
        $pStatus->setAccessible(true);

        $this->assertEquals(302, $pStatus->getValue($res));
        $this->assertEquals('/foo', $res->getHeaders()->get('Location'));
    }

    public function testIsEmpty()
    {
        $headers = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $r1 = new \Slim\Http\Response($headers, $cookies);
        $r2 = new \Slim\Http\Response($headers, $cookies);
        $r1->setStatus(404);
        $r2->setStatus(201);
        $this->assertFalse($r1->isEmpty());
        $this->assertTrue($r2->isEmpty());
    }

    public function testIsClientError()
    {
        $headers1 = new \Slim\Http\Headers();
        $headers2 = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $r1 = new \Slim\Http\Response($headers1, $cookies);
        $r2 = new \Slim\Http\Response($headers2, $cookies);
        $r1->setStatus(404);
        $r2->setStatus(500);
        $this->assertTrue($r1->isClientError());
        $this->assertFalse($r2->isClientError());
    }

    public function testIsForbidden()
    {
        $headers1 = new \Slim\Http\Headers();
        $headers2 = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $r1 = new \Slim\Http\Response($headers1, $cookies);
        $r2 = new \Slim\Http\Response($headers2, $cookies);
        $r1->setStatus(403);
        $r2->setStatus(500);
        $this->assertTrue($r1->isForbidden());
        $this->assertFalse($r2->isForbidden());
    }

    public function testIsInformational()
    {
        $headers1 = new \Slim\Http\Headers();
        $headers2 = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $r1 = new \Slim\Http\Response($headers1, $cookies);
        $r2 = new \Slim\Http\Response($headers2, $cookies);
        $r1->setStatus(100);
        $r2->setStatus(200);
        $this->assertTrue($r1->isInformational());
        $this->assertFalse($r2->isInformational());
    }

    public function testIsNotFound()
    {
        $headers1 = new \Slim\Http\Headers();
        $headers2 = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $r1 = new \Slim\Http\Response($headers1, $cookies);
        $r2 = new \Slim\Http\Response($headers2, $cookies);
        $r1->setStatus(404);
        $r2->setStatus(200);
        $this->assertTrue($r1->isNotFound());
        $this->assertFalse($r2->isNotFound());
    }

    public function testIsOk()
    {
        $headers1 = new \Slim\Http\Headers();
        $headers2 = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $r1 = new \Slim\Http\Response($headers1, $cookies);
        $r2 = new \Slim\Http\Response($headers2, $cookies);
        $r1->setStatus(200);
        $r2->setStatus(201);
        $this->assertTrue($r1->isOk());
        $this->assertFalse($r2->isOk());
    }

    public function testIsSuccessful()
    {
        $headers1 = new \Slim\Http\Headers();
        $headers2 = new \Slim\Http\Headers();
        $headers3 = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $r1 = new \Slim\Http\Response($headers1, $cookies);
        $r2 = new \Slim\Http\Response($headers2, $cookies);
        $r3 = new \Slim\Http\Response($headers3, $cookies);
        $r1->setStatus(200);
        $r2->setStatus(201);
        $r3->setStatus(302);
        $this->assertTrue($r1->isSuccessful());
        $this->assertTrue($r2->isSuccessful());
        $this->assertFalse($r3->isSuccessful());
    }

    public function testIsRedirect()
    {
        $headers1 = new \Slim\Http\Headers();
        $headers2 = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $r1 = new \Slim\Http\Response($headers1, $cookies);
        $r2 = new \Slim\Http\Response($headers2, $cookies);
        $r1->setStatus(307);
        $r2->setStatus(304);
        $this->assertTrue($r1->isRedirect());
        $this->assertFalse($r2->isRedirect());
    }

    public function testIsRedirection()
    {
        $headers1 = new \Slim\Http\Headers();
        $headers2 = new \Slim\Http\Headers();
        $headers3 = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $r1 = new \Slim\Http\Response($headers1, $cookies);
        $r2 = new \Slim\Http\Response($headers2, $cookies);
        $r3 = new \Slim\Http\Response($headers3, $cookies);
        $r1->setStatus(307);
        $r2->setStatus(304);
        $r3->setStatus(200);
        $this->assertTrue($r1->isRedirection());
        $this->assertTrue($r2->isRedirection());
        $this->assertFalse($r3->isRedirection());
    }

    public function testIsServerError()
    {
        $headers1 = new \Slim\Http\Headers();
        $headers2 = new \Slim\Http\Headers();
        $cookies = new \Slim\Http\Cookies();
        $r1 = new \Slim\Http\Response($headers1, $cookies);
        $r2 = new \Slim\Http\Response($headers2, $cookies);
        $r1->setStatus(500);
        $r2->setStatus(400);
        $this->assertTrue($r1->isServerError());
        $this->assertFalse($r2->isServerError());
    }

    public function testMessageForCode()
    {
        $this->assertEquals('200 OK', \Slim\Http\Response::getMessageForCode(200));
    }

    public function testMessageForCodeWithInvalidCode()
    {
        $this->assertNull(\Slim\Http\Response::getMessageForCode(600));
    }
}
