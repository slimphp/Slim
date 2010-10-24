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

require_once '../slim/View.php';
require_once '../views/MustacheView.php';
require_once 'PHPUnit/Framework.php';
 
class MustacheViewTest extends PHPUnit_Framework_TestCase {

	/***** SETUP *****/

	public function setUp() {
		$this->view = new MustacheView();
	}

	/***** TESTS *****/

    /**
	 * Test MustacheView class renders template using Mustache
	 *
	 * Pre-conditions:
	 * You instantiate a View object, sets its templates directory to
	 * an existing directory. You pass data into the View, and render
	 * an existing Mustache template. No errors or exceptions are thrown.
	 *
	 * Post-conditions:
	 * The contents of the output buffer match the rendered Mustache template.
	 */
	public function testRendersTemplateWithData() {
		$this->view->templatesDirectory(realpath('./templates'));
		ob_start();
        $this->view->mustacheDirectory= dirname(__FILE__)."/../lib/";
		$this->view->data(array('foo' => 'bar'));
		$this->view->render('test.mustache');
		$output = ob_get_clean();
		$this->assertEquals($output, 'test mustache output bar');
	}
}

?>
