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
 * Slim Response
 *
 * The Response object is responsible for preparing the HTTP response
 * before the response is sent to the client. In particular, this class sets the
 * HTTP response status, headers, cookies, and body.
 *
 * @author Josh Lockhart <info@joshlockhart.com>
 * @since Version 1.0
 */
class Response {
	
	/**
	 * @var int The HTTP status code
	 */
	private $status;
	
	/**
	 * @var array The HTTP response headers; [ name => value, ... ]
	 */
	private $headers;
	
	/**
	 * @var array The HTTP response cookies (not implemented yet)
	 */
	private $cookies;
	
	/**
	 * @var string The HTTP response body
	 */
	private $body;
	
	/**
	 * @var int The Content-Length of the HTTP response body
	 */
	private $length;
	
	/**
	 * @var array Available HTTP response codes with associated messages
	 */
	private static $messages = array(
		// [Informational 1xx]
		100 => '100 Continue',
		101 => '101 Switching Protocols',
		// [Successful 2xx]
		200 => '200 OK',
		201 => '201 Created',
		202 => '202 Accepted',
		203 => '203 Non-Authoritative Information',
		204 => '204 No Content',
		205 => '205 Reset Content',
		206 => '206 Partial Content',
		// [Redirection 3xx]
		300 => '300 Multiple Choices',
		301 => '301 Moved Permanently',
		302 => '302 Found',
		303 => '303 See Other',
		304 => '304 Not Modified',
		305 => '305 Use Proxy',
		306 => '306 (Unused)',
		307 => '307 Temporary Redirect',
		// [Client Error 4xx]
		400 => '400 Bad Request',
		401 => '401 Unauthorized',
		402 => '402 Payment Required',
		403 => '403 Forbidden',
		404 => '404 Not Found',
		405 => '405 Method Not Allowed',
		406 => '406 Not Acceptable',
		407 => '407 Proxy Authentication Required',
		408 => '408 Request Timeout',
		409 => '409 Conflict',
		410 => '410 Gone',
		411 => '411 Length Required',
		412 => '412 Precondition Failed',
		413 => '413 Request Entity Too Large',
		414 => '414 Request-URI Too Long',
		415 => '415 Unsupported Media Type',
		416 => '416 Requested Range Not Satisfiable',
		417 => '417 Expectation Failed',
		// [Server Error 5xx]
		500 => '500 Internal Server Error',
		501 => '501 Not Implemented',
		502 => '502 Bad Gateway',
		503 => '503 Service Unavailable',
		504 => '504 Gateway Timeout',
		505 => '505 HTTP Version Not Supported'
	);
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->status(200);
		$this->header('Content-Type', 'text/html');
		$this->cookies = array();
	}
	
	/***** ACCESSORS *****/
	
	/**
	 * Set and/or get the HTTP response status code
	 *
	 * @param int $status
	 * @return int
	 * @throws InvalidArgumentException If status parameter does not match a valid HTTP status code
	 */
	public function status( $status = null ) {
		if( !is_null($status) ) {
			if( !in_array(intval($status), array_keys(self::$messages))) {
				throw new InvalidArgumentException('Cannot set Response status. Provided status code "' . $status . '" is not a valid HTTP response code.');
			}
			$this->status = intval($status);
		}
		return $this->status;
	}
	
	/**
	 * Get HTTP response headers
	 *
	 * @return array
	 */
	public function headers() {
		return $this->headers;
	}
	
	/**
	 * Get and/or set an HTTP response header
	 *
	 * @param string $key The header name
	 * @param string $value The header value
	 * @return string The header value
	 */
	public function header( $key, $value = null ) {
		if( !is_null($value) ) {
			$this->headers[$key] = $value;
		}
		return $this->headers[$key];
	}
	
	/**
	 * Replace the HTTP response body
	 *
	 * @param string $body The new HTTP response body
	 * @return string The updated HTTP response body
	 */
	public function body( $body = null ) {
		if( !is_null($body) ) {
			$this->body = '';
			$this->length = 0;
			$this->write($body);
		}
		return $this->body;
	}
	
	/**
	 * Append the HTTP response body
	 *
	 * @param string $body Content to append to the current HTTP response body
	 * @return string The updated HTTP response body
	 */
	public function write( $body ) {
		$body = (string)$body;
		$this->length += strlen($body);
		$this->body .= $body;
		$this->header('Content-Length', $this->length);
		return $body;
	}
	
	/***** COOKIES *****/
	
	/**
	 * Add Cookie to Response
	 *
	 * @param Cookie $cookie
	 */
	public function addCookie( Cookie $cookie ) {
		$this->cookies[] = $cookie;
	}
	
	/**
	 * Get Cookies set in Response
	 *
	 * @return array
	 */
	public function getCookies() {
		return $this->cookies;
	}
	
	/***** FINALIZE BEFORE SENDING *****/
	
	/**
	 * Finalize response headers before response is sent
	 */
	public function finalize() {
		if( in_array($this->status, array(204, 304)) ) {
			unset($this->headers['Content-Type']);
		}
	}
	
	/***** HELPER METHODS *****/
	
	/**
	 * Get message for HTTP status code
	 *
	 * @return string
	 */
	public static function getMessageForCode($status) {
		return self::$messages[$status];
	}
	
	/**
	 * Can this HTTP response have a body?
	 *
	 * @return true|false
	 */
	public function canHaveBody() {
		return ( $this->status < 100 || $this->status >= 200 ) && $this->status != 204 && $this->status != 304;
	}
	
	/***** SEND RESPONSE *****/
	
	/**
	 * Send headers for HTTP response
	 */
	protected function sendHeaders() {
		
		//Finalize response
		$this->finalize();
		
		//Send HTTP message
		header('HTTP/1.1 ' . Response::getMessageForCode($this->status()));
		
		//Send headers
		foreach( $this->headers() as $name => $value ) {
			header("$name: $value");
		}
		
		//Send cookies
		foreach( $this->cookies as $cookie ) {
			if( empty($cookie->value) ) {
				setcookie($cookie->name, '', time() - 90000, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httponly);
			} else {
				setcookie($cookie->name, $cookie->value, $cookie->expires, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httponly);
			}
		}
		
		//Flush all output to client
		flush();
		
	}
	
	/**
	 * Send HTTP response
	 */
	public function send() {
		if( !headers_sent() ) {
			$this->sendHeaders();
		}
		if( $this->canHaveBody() ) {
			echo $this->body;
		}
	}

}

?>