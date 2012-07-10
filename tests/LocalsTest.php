<?php

set_include_path(dirname(__FILE__) . '/../' . PATH_SEPARATOR . get_include_path());

require_once 'Slim/Locals.php';

class LocalsTest extends PHPUnit_Framework_TestCase {
		
	public function setUp() {
		$this->locals = new Slim_Locals();
	}

	public function locals() {
		return $this->locals->parse(func_get_args());	
	}
	
	public function testLocalsPropertiesCannotBeAssignedLocally() {
		$this->locals->test = 123;
		$this->assertTrue( !isset($this->locals->test) );
	}
	
	public function testLocalsSetSimpleValue() {
		$this->locals('test', 123);
		$this->assertTrue( $this->locals('test') === 123 );
	}
	
	public function testLocalsSetSimpleGetReturnValue() {
		$rtn = $this->locals('test', 123);
		$this->assertTrue($rtn === 123);
	}
	
	public function testLocalsSetSimpleValueOverwrite() {
		$this->locals('test', 123);
		$this->locals('test', 124);
		$this->assertTrue( $this->locals('test') === 124 );
	}
	
	public function testLocalsSetSimpleValueMethod() {
		$this->locals('eq', function($a, $b) {
			return $a === $b;
		});
		$this->assertTrue( is_callable( $this->locals('eq' ) ) );		
	}
	
	public function testLocalsReturnMethodBodyByKey() {
		$this->locals('eq', function($a, $b) {
			return $a === $b;
		});
		$this->assertTrue( is_callable( $this->locals('eq') ) );
	}
	
	public function testLocalsSimpleValueMethod() {
		$this->locals('eq', function($a, $b) {
			return $a === $b;
		});
		$this->assertTrue( $this->locals('eq', 1, 1) );
		$this->assertFalse( $this->locals('eq', 1, 2));	
	}
	
	public function testLocalsOverwriteSimpleValueMethod() {
		$this->locals('eq', function($a, $b) {
			return $a === $b;
		});	
		$this->locals('eq', function($a, $b) {
			return $a !== $b;
		});
		$this->assertFalse( $this->locals('eq', 1, 1) );
		$this->assertTrue( $this->locals('eq', 1, 2));	
	}
	
	public function testLocalsEmptyArgumentsShouldReturnAllValues() {
		$this->locals('eq', function($a, $b) {
			return $a === $b;
		});
		$this->locals('test', 123);
		$this->locals('trolol', 12341);
		$this->assertTrue( $this->locals->count() === 3);
	}
	
	public function testOverwriteExistingValueWithMethod() {
		$this->locals('test', 123);
		$this->locals('test', function($a, $b) {
			return $a === $b;
		});
		$this->assertTrue( is_callable($this->locals('test')) );
	}
	
}
