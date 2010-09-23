<?php

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
	
	public function testViewIsConstructedWithDataArray() {
		$this->assertTrue(is_array($this->view->data()));
	}
	
	public function testViewIsConstructedWithEmptyDataArray() {
		$viewData = $this->view->data();
		$this->assertTrue(empty($viewData));
	}
	
	public function testViewReturnsDataWhenSet() {
		$testData = $this->generateTestData();
		$returnedData = $this->view->data($testData);
		$this->assertSame($testData, $returnedData);
	}
	
	public function testViewAcceptsArrayAsData() {
		$testData = $this->generateTestData();
		$this->view->data($testData);
		$this->assertEquals(count($this->view->data()), 3);
	}
	
	public function testViewDoesNotAcceptNonArrayAsData() {
		$returnedData = $this->view->data(1);
		$this->assertTrue(empty($returnedData));
	}
	
}

?>