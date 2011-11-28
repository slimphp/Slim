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

require_once 'Slim/Session/Flash.php';

class FlashTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $_SESSION['flash'] = array('info' => 'Info message');
    }

    /**
     * Test Flash session key
     *
     * Pre-conditions:
     * Case A: Use default key
     * Case B: Use custom string key
     * Case C: Use null key
     * Case D: Set null key directly
     *
     * Post-conditions:
     * Case A: Key === 'flash'
     * Case B: Key === 'foo'
     * Case C: Key === 'flash'
     * Case D: Catch RuntimeException
     */
    public function testFlashSessionKey() {
        //Case A
        $f1 = new Slim_Session_Flash();
        $this->assertEquals('flash', $f1->getSessionKey());
        //Case B
        $f2 = new Slim_Session_Flash('foo');
        $this->assertEquals('foo', $f2->getSessionKey());
        //Case C
        $f3 = new Slim_Session_Flash(null);
        $this->assertEquals('flash', $f3->getSessionKey());
        //Case D
        try {
            $f3->setSessionKey(null);
            $this->fail('Did not catch RuntimeException when setting null session key');
        } catch ( RuntimeException $e ) {}
    }

    /**
     * Test Flash loads messages from session
     *
     * Pre-conditions:
     * Case A: Assume default session key
     * Case B: Custom key that does not exist in $_SESSION
     *
     * Post-conditions:
     * Case A: Flash messages are same as in $_SESSION
     * Case B: Empty array
     */
    public function testFlashLoadsMessagesFromSession() {
        //Case A
        $f1 = new Slim_Session_Flash();
        $this->assertSame($_SESSION['flash'], $f1->getMessages());
        //Case B
        $f2 = new Slim_Session_Flash('foo');
        $this->assertSame(array(), $f2->getMessages());
    }

    /**
     * Test Flash sets messages for current request
     *
     * Pre-conditions:
     * Messages are loaded from $_SESSION;
     * Message set using now();
     * Message set using set();
     *
     * Post-conditions:
     * Flash messages for current request are equal to those
     * pulled from $_SESSION and the one set using now(). The
     * message set using set() is ignored until next request.
     */
    public function testFlashSetsMessagesForNow() {
        $f1 = new Slim_Session_Flash();
        $f1->now('error', 'Error message');
        $f1->set('later', 'Message for next request');
        $msgs = $f1->getMessages();
        $this->assertEquals(2, count($msgs));
        $this->assertArrayHasKey('info', $msgs); //From $_SESSION
        $this->assertArrayHasKey('error', $msgs); //From now()
    }

    /**
     * Test Flash sets and saves messages for next request
     *
     * Pre-conditions:
     * A new message is set for the next request;
     *
     * Post-conditions:
     * Only the new message set for the next request is
     * saved to $_SESSION. The messages from the previous
     * request are ignored.
     */
    public function testFlashSetsMessagesForNextRequest() {
        $f1 = new Slim_Session_Flash();
        $f1->set('info', 'New info message');
        $f1->save();
        $this->assertEquals(1, count($_SESSION['flash']));
        $this->assertArrayHasKey('info', $_SESSION['flash']);
        $this->assertEquals('New info message', $_SESSION['flash']['info']);
    }

    /**
     * Test Flash keeps messages from prev request for next request
     *
     * Pre-conditions:
     * Messages from past request are kept for next request;
     * New error message is set for next request;
     *
     * Post-conditions:
     * Messages from past request and new message are all
     * saved to $_SESSION.
     */
    public function testFlashKeepsMessages() {
        $f1 = new Slim_Session_Flash();
        $f1->keep();
        $f1->set('error', 'New error message');
        $f1->save();
        $this->assertEquals(2, count($_SESSION['flash']));
        $this->assertArrayHasKey('info', $_SESSION['flash']);
        $this->assertArrayHasKey('error', $_SESSION['flash']);
        $this->assertEquals('New error message', $_SESSION['flash']['error']);
    }

    /**
     * Tests flash can store array/object, not only strings.
     */
    public function testFlashKeepsObjects() {
        $c = new StdClass;
        $c->foo = 'bar';

        $f1 = new Slim_Session_Flash();
        $f1->set('object', $c);
        $f1->save();
        $f1->load();

        $this->assertObjectHasAttribute('foo', $f1['object']);
    }

    /**
     * Test flash information iteration
     */
    public function testFlashIteration() {
        $f = new Slim_Session_Flash();
        $data = array('one' => 'This is one', 'two' => 'This is two', 'three' => 'This is three');
        $f->now('info', $data);
        if ( isset($f['info']) ) {
            foreach ( $f['info'] as $key => $value ) {
                $this->assertArrayHasKey($key, $data);
                $this->assertEquals($data[$key], $value);
            }
            unset($f['info']);
            $this->assertNull($f['info']);
        }
        $f['test'] = $data;
        $this->assertEquals($data, $f['test']);
    }

}
