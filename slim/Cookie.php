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

/**
 * Cookie
 *
 * This class acts as a wrapper for PHP's native `setcookie` method.
 *
 * @package	Slim
 * @author	Josh Lockhart <info@joshlockhart.com>
 * @since	Version 1.0
 */
class Cookie {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var mixed
	 */
	protected $value;

	/**
	 * @var int UNIX timestamp
	 */
	protected $expires;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var string
	 */
	protected $domain;

	/**
	 * @var bool
	 */
	protected $secure;

	/**
	 * @var bool
	 */
	protected $httponly;

	public function __construct( $name, $value = null, $expires = 0, $path = null, $domain = null, $secure = false, $httponly = false ) {
		$this->name = $name;
		$this->value = $value;
		$this->expires = $expires;
		$this->path = $path;
		$this->domain = $domain;
		$this->secure = $secure;
		$this->httponly = $httponly;
	}
	
	/**
	 * Get cookie name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Set cookie name
	 *
	 * @param string $name
	 * @return void;
	 */
	public function setName( $name ) {
		$this->name = (string)$name;
	}
	
	/**
	 * Get cookie value
	 *
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * Set cookie value
	 *
	 * @param mixed $value
	 * @return void
	 */
	public function setValue($value) {
		$this->value = (string)$value;
	}
	
	/**
	 * Get cookie expiration time
	 *
	 * @return int UNIX timestamp
	 */
	public function getExpires() {
		return $this->expires;
	}
	
	/**
	 * Set cookie expiration time
	 *
	 * @param string|int Cookie expiration time as string w/ strtotime(), or UNIX timestamp
	 * @return void
	 */
	public function setExpires($time) {
		$this->expires = is_string($time) ? strtotime($time) : (int)$time;
	}

	/**
	 * Get cookie path
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}
	
	/**
	 * Set cookie path
	 *
	 * @param string $path
	 * @return void
	 */
	public function setPath($path) {
		$this->path = (string)$path;
	}
	
	/**
	 * Get cookie domain
	 *
	 * @return string
	 */
	public function getDomain() {
		return $this->domain;
	}
	
	/**
	 * Set cookie domain
	 *
	 * @param string $domain
	 * @return void
	 */
	public function setDomain($domain) {
		$this->domain = (string)$domain;
	}
	
	/**
	 * Is cookie sent over SSL/HTTPS only?
	 *
	 * @return bool
	 */
	public function getSecure() {
		return $this->secure;
	}
	
	/**
	 * Set whether cookie is sent over SSL/HTTPS only
	 *
	 * @param bool $secure
	 * @return void
	 */
	public function setSecure($secure) {
		$this->secure = (bool)$secure;
	}
	
	/**
	 * Is cookie sent over HTTP protocol only?
	 *
	 * @return bool
	 */
	public function getHttpOnly() {
		return $this->httpOnly;
	}
	
	/**
	 * Set whether cookie is sent over HTTP protocol only
	 *
	 * @param bool $httponly
	 * @return void
	 */
	public function setHttpOnly($httpOnly) {
		$this->httponly = (bool)$httponly;
	}

}

?>