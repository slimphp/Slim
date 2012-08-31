<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.6.7
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

class SettingsTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test default settings
     */
    public function testDefaultSettings()
    {
        $s = new \Slim\Settings(array());
        $this->assertSame('development', $s['mode']);
    }

    /**
     * Test set settings
     */
    public function testSetSettings()
    {
        $s = new \Slim\Settings(array());
        $s->set('mode', 'production');
        $this->assertSame('production', $s['mode']);
        $s['view'] = 'foo';
        $this->assertSame('foo', $s->get('view'));
    }

    /**
     * Set settings using an array
     */
    public function testSetSettingsArray()
    {
        $s = new \Slim\Settings(array('mode' => 'production', 'view' => 'foo'));
        $this->assertSame('production', $s['mode']);
        $this->assertSame('foo', $s['view']);
        $s->set(array('mode' => 'development', 'view' => 'bar'));
        $this->assertSame('development', $s['mode']);
        $this->assertSame('bar', $s['view']);
    }

    /**
     * Set an invalid argument
     */
    public function testSettingsWithInvalidArgument()
    {
        $this->setExpectedException('InvalidArgumentException');
        $s = new \Slim\Settings(array());
        $s['foo'] = array('bar');
    }
}
