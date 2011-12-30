<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.6.0
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
  * Slim Middleware Interface
  * @package    Slim
  * @author     Josh Lockhart
  * @since      1.6.0
  */
interface Slim_Middleware_Interface {
    /**
     * Constructor
     *
     * The constructor will always accept two arguments. The first
     * argument is a reference to an instance of class `Slim` or
     * an instance of a subclass of `Slim_Middleware_Base`.
     * The second argument is an associative array
     * of settings specific to this middleware instance.
     *
     * You are free to override this constructor implementation
     * in your subclass; just make sure the subclass constructor
     * signature remains the same.
     *
     * @param   Slim|Slim_Middleware_Base $app
     * @param   array $settings
     * @return  void
     */
    public function __construct( $app, $settings = array() );

    /**
     * Call
     *
     * This method will be invoked when the Slim application is run. This method
     * MUST return a numeric array containing these three elements:
     *
     * 1) (int) The HTTP status
     * 2) (Slim_Http_Headers|array) The HTTP headers
     * 3) (string) The HTTP body
     *
     * This method accepts one required argument: a reference to the environment
     * settings; this is an associative array. Changes made to the environment
     * settings will be propagated immediately throughout the entire application.
     *
     * @param   array $env Reference to environment settings
     * @return  array[status, header, body]
     */
    public function call( &$env );
}