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
	 * A newly constructed View class' data will be an array
	 */
	public function testViewIsConstructedWithDataArray() {
		$this->assertTrue(is_array($this->view->data()));
	}
	
	/**
	 * A newly constructed View class' data will be an empty array
	 */
	public function testViewIsConstructedWithEmptyDataArray() {
		$viewData = $this->view->data();
		$this->assertTrue(empty($viewData));
	}
	
	/**
	 * The associative array of data you provide will be used as the View's data
	 */
	public function testViewReturnsDataWhenSet() {
		$testData = $this->generateTestData();
		$returnedData = $this->view->data($testData);
		$this->assertSame($testData, $returnedData);
	}
	
	/**
	 * Test View appends data rather than overwriting data
	 *
	 * Pre-conditions:
	 * You have instantiated a default View and call the
	 * data method multiple times to append array data.
	 *
	 * Post-conditions:
	 * The resultant data array should contain all merged
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
	 * You can pass an associatve array to populate a View's data
	 */
	public function testViewAcceptsArrayAsData() {
		$testData = $this->generateTestData();
		$this->view->data($testData);
		$this->assertEquals(count($this->view->data()), 3);
	}
	
	/**
	 * You cannot pass a non-array to populate a View's data
	 */
	public function testViewDoesNotAcceptNonArrayAsData() {
		$returnedData = $this->view->data(1);
		$this->assertTrue(empty($returnedData));
	}
	
	/**
	 * You can set the templates directory for a View
	 */
	public function testSetsTemplatesDirectory() {
		$templatesDirectory = rtrim(realpath('../templates/'), '/') . '/';
		$this->view->templatesDirectory($templatesDirectory);
		$this->assertEquals($templatesDirectory, $this->view->templatesDirectory());
	}
	
	/**
	 * When you set the templates directory for a view, your absolute
	 * or relative path may or may not have a trailing slash. Either way
	 * should work just fine.
	 */
	public function testTemplatesDirectoryHasTrailingSlash() {
		$templatesDirectory = rtrim(realpath('../templates/'), '/');
		$this->view->templatesDirectory($templatesDirectory);
		$this->assertEquals($templatesDirectory . '/', $this->view->templatesDirectory());
	}
	
	/**
	 * When you set the templates directory or a view, if the specified
	 * templates directory does not exist or is not a directory, an
	 * Exception will be thrown.
	 */
	public function testExceptionForInvalidTemplatesDirectory() {
		$this->setExpectedException('RuntimeException');
		$this->view->templatesDirectory('./foo');
	}
	
	/**
	 * The default View class should successfully echo a rendered template
	 * using the provided data.
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