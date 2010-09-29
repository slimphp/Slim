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
 * Slim Exception
 *
 * This specific Exception is used to prepare and intercept Slim-specific 
 * response messages; for example, a SlimException may be thrown
 * with code 404 to send a NotFound response to the client.
 *
 * We use a sub-classed exception to easily differentiate a Slim
 * exception, used to trigger a specific HTTP response, from a 
 * RuntimeException that represents an application runtime error.
 *
 * @author Josh Lockhart <info@joshlockhart.com>
 * @since Version 1.0
 */
class SlimException extends Exception {

	/**
	 * Construct
	 *
	 * SlimException represents an HTTP status code and message.
	 * Therefore, the exception code must be a valid HTTP status code.
	 * If it is not, a RuntimeException is thrown which will be caught
	 * and spawn a 500 error.
	 *
	 * @param string $message The body message for the HTTP response
	 * @param int $code The HTTP status code
	 */
	public function __construct( $message, $code = 500 ) {
		//TODO: Do we ensure $code is a valid HTTP status code here? Or upstream?
		parent::__construct($message, $code);
	}
	
}

?>