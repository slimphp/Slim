<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author		Josh Lockhart
 * @link		http://www.slimframework.com
 * @copyright	2011 Josh Lockhart
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
require_once 'PHPUnit/Extensions/OutputTestCase.php';
 
class ViewTest extends PHPUnit_Extensions_OutputTestCase {

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
		$this->assertEquals($this->view->getData(), array());
	}

	/**
	 * Test View sets and gets data
	 *
	 * Pre-conditions:
	 * View is instantiated
	 * Case A: Set view data key/value
	 * Case B: Set view data as array
	 * Case C: Set view data with one argument that is not an array
	 *
	 * Post-conditions:
	 * Case A: Data key/value are set
	 * Case B: Data is set to array
	 * Case C: An InvalidArgumentException is thrown
	 */
	public function testViewSetAndGetData() {
		//Case A
		$this->view->setData('one', 1);
		$this->assertEquals($this->view->getData('one'), 1);
		
		//Case B
		$data = array('foo' => 'bar', 'a' => 'A');
		$this->view->setData($data);
		$this->assertSame($this->view->getData(), $data);
		
		//Case C
		try {
			$this->view->setData('foo');
			$this->fail('Setting View data with non-array single argument did not throw exception');
		} catch ( InvalidArgumentException $e ) {}
	}
	
	/**
	 * Test View appends data
	 *
	 * Pre-conditions:
	 * View is instantiated
	 * Append data to View several times
	 *
	 * Post-conditions:
	 * The View data contains all appended data
	 */
	public function testViewAppendsData(){
		$this->view->appendData(array('a' => 'A'));
		$this->view->appendData(array('b' => 'B'));
		$this->assertEquals($this->view->getData(), array('a' => 'A', 'b' => 'B'));
	}

	/**
	 * Test View templates directory
	 *
	 * Pre-conditions:
	 * View is instantiated
	 * View templates directory is set to an existing directory
	 *
	 * Post-conditions:
	 * The templates directory is set correctly.
	 */
	public function testSetsTemplatesDirectory() {
		$templatesDirectory = '../templates';
		$this->view->setTemplatesDirectory($templatesDirectory);
		$this->assertEquals($templatesDirectory, $this->view->getTemplatesDirectory());
	}

	/**
	 * Test View templates directory may have a trailing slash when set
	 *
	 * Pre-conditions:
	 * View is instantiated
	 * View templates directory is set to an existing directory with a trailing slash
	 *
	 * Post-conditions:
	 * The View templates directory is set correctly without a trailing slash
	 */
	public function testTemplatesDirectoryWithTrailingSlash() {
		$this->view->setTemplatesDirectory('../templates/');
		$this->assertEquals('../templates', $this->view->getTemplatesDirectory());
	}

	/**
	 * Test View throws Exception if templates directory does not exist
	 *
	 * Pre-conditions:
	 * View is instantiated
	 * View templates directory is set to a non-existent directory
	 *
	 * Post-conditions:
	 * A RuntimeException is thrown
	 */
	public function testExceptionForInvalidTemplatesDirectory() {
		$this->setExpectedException('RuntimeException');
		$this->view->setTemplatesDirectory('./foo');
	}

	/**
	 * Test View renders template
	 *
	 * Pre-conditions:
	 * View is instantiated
	 * View templates directory is set to an existing directory.
	 * View data is set without errors
	 * Case A: View renders an existing template
	 * Case B: View renders a non-existing template
	 *
	 * Post-conditions:
	 * Case A: The rendered template is returned as a string
	 * Case B: A RuntimeException is thrown
	 */
	public function testRendersTemplateWithData() {
		$this->view->setTemplatesDirectory('./templates');
		$this->view->setData(array('foo' => 'bar'));
		
		//Case A
		$output = $this->view->render('test.php');
		$this->assertEquals($output, 'test output bar');
		
		//Case B
		try {
			$output = $this->view->render('foo.php');
			$this->fail('Rendering non-existent template did not throw exception');
		} catch ( RuntimeException $e ) {}
	}
	
	/**
	 * Test View displays template
	 *
	 * Pre-conditions:
	 * View is instantiated
	 * View templates directory is set to an existing directory.
	 * View data is set without errors
	 * View is displayed
	 *
	 * Post-conditions:
	 * The output buffer contains the rendered template
	 */
	public function testDisplaysTemplateWithData() {
		$this->expectOutputString('test output bar');
		$this->view->setTemplatesDirectory('./templates');
		$this->view->setData(array('foo' => 'bar'));
		$this->view->display('test.php');
	}

}
?>