<?php

class Slim_Locals {

	/**
	 * @var array
	 */
	private $properties;

 	/**
     * Constructor
     *
     * 
     */
    public function __construct() {
    	$this->properties = array();
    }
	
	/**
	 * Get value of a local property
	 * 
	 * @param string $name Name of property
	 * @return mixed|null Value of property, otherwise null.
	 */
	protected function get($name) {
		return isset($this->properties[$name]) ? $this->properties[$name] : null;
	}
		
	/**
	 * Set value of a local property
	 * 
	 * @param string $name Name of property
	 * @param mixed $value Value of property
	 * @return mixed Actual value of assigned property
	 */
	protected function set($name, $value) {
		$this->properties[$name] = $value;
		return $value;
	}
	
	/**
	 * Prevent properties being added to class object
	 * 
	 * @param string $name 
	 * @param mixed $value
	 */
	public function __set($name, $value) {}
	
	/**
	 * Determines if the value of a local property is a callable
	 * method
	 * 
	 * @param string $name Name of property
	 * @return bool True if callable method, otherwise false.
	 */
	protected function isCallable($name) {
	 	$fn = $this->get($name);
		return $fn !== null && is_callable($fn);
	}
	
	/**
	 * Tries to locate a given property. Contents of the given property value
	 * can either by any value type or a closure/lambda method.
	 * 
	 * If value is a method the remaining arguments will be passed to that method,
	 * otherwise the actual value will be returned.
	 * 
	 * Please note that existing properties will be overwritten.
	 * 
	 * @param array $args Array of arguments
	 * @return mixed|null
	 */			
	protected function parse($args) {
			
		// number of arguments available
		$numArgs = count($args);
	
		// return all properties available
		if( $numArgs === 0 ) {
			return (object) $this->properties;
		}
	
		// name of property
		$name = $args[0];
		
		// does the property exist?
		$exists = isset($this->properties[$name]);
		
		// return the value of existing property
		if ( $numArgs === 1 ) {
			return $this->get($name);
		}
	
		// is property callable?
		$existingIsCallable = $this->isCallable($name);
		$currentIsCallable = is_callable($args[1]);
					
		// set new property if it does not exist
		if (!$exists && $numArgs === 2) {
			return $this->set($name, $args[1]);
		}
		
		// overwrite existing property
		if( $exists && !$existingIsCallable) {
			return $this->set($name, $args[1]);
		}
		
		// overwrite existing method
		if( $exists && $currentIsCallable) {
			return $this->set($name, $args[1]);
		}	
				
		// call existing method with x number of arguments
		if ( $exists && $existingIsCallable ) {
			$fn = $this->get($name);
		 	return call_user_func_array( $fn, array_slice( $args, 1 ));
		} 
				
		return null;
 
	}
	
	/**
	 * Number of properties available.
	 * 
	 * @return integer Number of properties available. 
	 */
	public function count() {
		return count ( $this->properties ); 
	}
	
	/**
	 * Allows access of properties to retrieved by local array index
	 * and by calling `locals` as a derrived method.
	 * 
	 * @param string $name Name of method
	 * @param array $args Array of arguments to pass to calling method
	 * @return Exception|mixed
	 */
	public function __call($name, $args) {
		if(is_callable(array($this, $name))) {
            return call_user_func_array(array($this, $name), $args);
        }
	}
	
}