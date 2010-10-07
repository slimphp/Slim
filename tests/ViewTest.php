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
require_once 'PHPUnit/Framework.php';
 
class ViewTest extends PHPUnit_Framework_TestCase {

	/***** SETUP *****/

	public function setUp() {
		$this->view = new View();
	}

	/***** DATA FACTORY *****/

	public function generateTestData() {
		return array('a' => 1, 'b' => 2, 'c' => 3);
	}

	/***** TESTS *****/

	/**
	 * Test initial View data is an empty array
	 *
	 * Pre-conditions:
	 * You instantiate a new View object
	 *
	 * Post-conditions:
	 * The View object's data attribute is an empty array
	 */
	public function testViewIsConstructedWithDataArray() {
		$this->assertEquals($this->view->data(), array());
	}

	/**
	 * Test View data is returned when set
	 *
	 * Pre-conditions:
	 * You instantiate a View object and set its data
	 *
	 * Post-conditions: 
	 * The latest View data is returned by the View::data method
	 */
	public function testViewReturnsDataWhenSet() {
		$returnedData = $this->view->data($this->generateTestData());
		$this->assertEquals($this->generateTestData(), $returnedData);
	}

	/**
	 * Test View appends data rather than overwriting data
	 *
	 * Pre-conditions:
	 * You instantiate a View object and call its data method 
	 * multiple times to append multiple sets of data
	 *
	 * Post-conditions:
	 * The resultant data array should contain the merged
	 * data from the multiple View::data calls.
	 */
	public function testViewMergesData(){
		$dataOne = array('a' => 'A');
		$dataTwo = array('b' => 'B');
		$this->view->data($dataOne);
		$this->view->data($dataTwo);
		$this->assertEquals($this->view->data(), array('a' => 'A', 'b' => 'B'));
	}

	/**
	 * Test View does not accept non-Array values
	 *
	 * Pre-conditions:
	 * You instantiate a View object and pass a non-Array value
	 * into its data method.
	 *
	 * Post-conditions:
	 * The View ignores the invalid data and the View's
	 * existing data attribute remains unchanged.
	 */
	public function testViewDoesNotAcceptNonArrayAsData() {
		$this->assertEquals($this->view->data(1), array());
	}

	/**
	 * Test View sets templates directory
	 *
	 * Pre-conditions:
	 * You instantiate a View object and set its templates directory
	 * to an existing directory.
	 *
	 * Post-conditions:
	 * The templates directory is set correctly.
	 */
	public function testSetsTemplatesDirectory() {
		$templatesDirectory = rtrim(realpath('../templates/'), '/') . '/';
		$this->view->templatesDirectory($templatesDirectory);
		$this->assertEquals($templatesDirectory, $this->view->templatesDirectory());
	}

	/**
	 * Test View templates directory path may have a trailing slash when set
	 *
	 * Pre-conditions:
	 * You instantiate a View object and set its template directory to an
	 * existing directory path with a trailing slash.
	 *
	 * Post-conditions:
	 * The View templates directory path contains a trailing slash.
	 */
	public function testTemplatesDirectoryWithTrailingSlash() {
		$templatesDirectory = realpath('../templates/');
		$this->view->templatesDirectory($templatesDirectory);
		$this->assertEquals($templatesDirectory . '/', $this->view->templatesDirectory());
	}

	/**
	 * Test View templates directory path may not have a trailing slash when set
	 *
	 * Pre-conditions:
	 * You instantiate a View object and set its template directory to an
	 * existing directory path without a trailing slash.
	 *
	 * Post-conditions:
	 * The View templates directory path contains a trailing slash.
	 */
	public function testTemplatesDirectoryWithoutTrailingSlash() {
		$templatesDirectory = rtrim(realpath('../templates/'), '/');
		$this->view->templatesDirectory($templatesDirectory);
		$this->assertEquals($templatesDirectory . '/', $this->view->templatesDirectory());
	}

	/**
	 * Test View throws Exception if templates directory does not exist
	 *
	 * Pre-conditions:
	 * You instantiate a View object and set its template directory
	 * to a non-existent directory.
	 *
	 * Post-conditions:
	 * A RuntimeException is thrown
	 */
	public function testExceptionForInvalidTemplatesDirectory() {
		$this->setExpectedException('RuntimeException');
		$this->view->templatesDirectory('./foo');
	}

	/**
	 * Test View class renders template
	 *
	 * Pre-conditions:
	 * You instantiate a View object, sets its templates directory to
	 * an existing directory. You pass data into the View, and render
	 * an existing template. No errors or exceptions are thrown.
	 *
	 * Post-conditions:
	 * The contents of the output buffer match the template.
	 */
	public function testRendersTemplateWithData() {
		$this->view->templatesDirectory(realpath('./templates'));
		ob_start();
		$this->view->data(array('foo' => 'bar'));
		$this->view->render('test.php');
		$output = ob_get_clean();
		$this->assertEquals($output, 'test output bar');
	}

}

?>
