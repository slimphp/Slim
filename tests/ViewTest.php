<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.2.0
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

class ViewTest extends PHPUnit_Framework_TestCase
{
    public function testViewIsConstructedWithDataArray()
    {
        $view = new \Slim\View();
        $this->assertAttributeEquals(array(), 'data', $view);
    }

    public function testGetDataAll()
    {
        $view = new \Slim\View();
        $property = new \ReflectionProperty($view, 'data');
        $property->setAccessible(true);
        $property->setValue($view, array('foo' => 'bar'));

        $this->assertSame(array('foo' => 'bar'), $view->getData());
    }

    public function testGetDataKeyExists()
    {
        $view = new \Slim\View();
        $property = new \ReflectionProperty($view, 'data');
        $property->setAccessible(true);
        $property->setValue($view, array('foo' => 'bar'));

        $this->assertEquals('bar', $view->getData('foo'));
    }

    public function testGetDataKeyNotExists()
    {
        $view = new \Slim\View();
        $property = new \ReflectionProperty($view, 'data');
        $property->setAccessible(true);
        $property->setValue($view, array('foo' => 'bar'));

        $this->assertNull($view->getData('abc'));
    }

    public function testSetDataKeyValue()
    {
        $view = new \Slim\View();
        $view->setData('foo', 'bar');

        $this->assertAttributeEquals(array('foo' => 'bar'), 'data', $view);
    }

    public function testSetDataArray()
    {
        $view = new \Slim\View();
        $view->setData(array('foo' => 'bar'));

        $this->assertAttributeEquals(array('foo' => 'bar'), 'data', $view);
    }

    public function testSetDataInvalidArgument()
    {
        $this->setExpectedException('InvalidArgumentException');

        $view = new \Slim\View();
        $view->setData('foo');
    }

    public function testAppendData()
    {
        $view = new \Slim\View();
        $view->appendData(array('foo' => 'bar'));

        $this->assertAttributeEquals(array('foo' => 'bar'), 'data', $view);
    }

    public function testAppendDataOverwrite()
    {
        $view = new \Slim\View();
        $property = new \ReflectionProperty($view, 'data');
        $property->setAccessible(true);
        $property->setValue($view, array('foo' => 'bar'));
        $view->appendData(array('foo' => '123'));

        $this->assertAttributeEquals(array('foo' => '123'), 'data', $view);
    }

    public function testAppendDataInvalidArgument()
    {
        $this->setExpectedException('InvalidArgumentException');

        $view = new \Slim\View();
        $view->appendData('foo');
    }

    public function testGetTemplatesDirectory()
    {
        $view = new \Slim\View();
        $property = new \ReflectionProperty($view, 'templatesDirectory');
        $property->setAccessible(true);
        $property->setValue($view, 'templates');

        $this->assertEquals('templates', $view->getTemplatesDirectory());
    }

    public function testSetTemplatesDirectory()
    {
        $view = new \Slim\View();
        $view->setTemplatesDirectory('templates/'); // <-- Should strip trailing slash

        $this->assertAttributeEquals('templates', 'templatesDirectory', $view);
    }

    public function testSetTemplateExists()
    {
        $view = new \Slim\View();
        $property = new \ReflectionProperty($view, 'templatesDirectory');
        $property->setAccessible(true);
        $property->setValue($view, dirname(__FILE__) . '/templates');
        $view->setTemplate('test.php');

        $this->assertAttributeEquals(dirname(__FILE__) . '/templates/test.php', 'templatePath', $view);
    }

    public function testSetTemplateNotExists()
    {
        $this->setExpectedException('RuntimeException');

        $view = new \Slim\View();
        $property = new \ReflectionProperty($view, 'templatesDirectory');
        $property->setAccessible(true);
        $property->setValue($view, dirname(__FILE__) . '/templates');
        $view->setTemplate('foo.php');
    }

    public function testDisplay()
    {
        $this->expectOutputString('test output bar');

        $view = new \Slim\View();

        $property1 = new \ReflectionProperty($view, 'data');
        $property1->setAccessible(true);
        $property1->setValue($view, array('foo' => 'bar'));

        $property2 = new \ReflectionProperty($view, 'templatesDirectory');
        $property2->setAccessible(true);
        $property2->setValue($view, dirname(__FILE__) . '/templates');

        $view->display('test.php');
    }
}
