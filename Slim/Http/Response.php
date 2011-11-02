<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@joshlockhart.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.5.0
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
 * Response
 *
 * Object-oriented representation of an HTTP response that is
 * returned to the client. This class is responsible for:
 *
 * - HTTP response status
 * - HTTP response body
 * - HTTP response headers
 * - HTTP response cookies
 *
 * @package Slim
 * @author  Josh Lockhart <info@joshlockhart.com>
 * @since   Version 1.0
 */
class Slim_Http_Response implements ArrayAccess, Iterator, Countable {
    /**
     * @var string HTTP response body
     */
    protected $body;

    /**
     * @var array HTTP response headers
     */
    protected $header;

    /**
     * @var int Length of HTTP response body
     */
    protected $length;

    /**
     * @var int HTTP status code
     */
    protected $status;

    /**
     * Constructor
     */
    public function __construct( $body = '', $status = 200, $header = array() ) {
        $this->body = '';
        $this->header = array_merge(array('Content-Type' => 'text/html'), $header);
        $this->length = 0;
        $this->status = (int)$status;
        $this->write($body);
    }

    /**
     * Append the HTTP response body
     * @param   string $body    Content to append to the current HTTP response body
     * @return  string          The updated HTTP response body
     */
    public function write( $body ) {
        $this->body .= (string)$body;
        $this->length = strlen($this->body);
        $this->header('Content-Length', $this->length);
        return $this->body;
    }

    /**
     * Finalize
     */
    public function finalize() {
        if ( in_array($this->status, array(204, 304)) ) {
            unset($this->header['Content-Type'], $this->header['Content-Length']); //TODO: Abstract header array into own util class
            return array($this->status, $this->header, '');
        } else {
            return array($this->status, $this->header, $this->body);
        }
    }

    /**
     * Set cookie
     */
    public function setCookie( $name, $valueOrArray ) {
        //Slim_Http_Util::setCookieHeader($this->header, $name, $valueOrArray);
    }

    /**
     * Delete cookie
     */
    public function deleteCookie( $name ) {
        //Slim_Http_Util::deleteCookieHeader($this->header, $name);
    }

    /**
     * Helpers: Empty?
     */
    public function isEmpty() {
        return in_array($this->status, array(201, 204, 304));
    }

    /**
     * Helpers: Client error?
     */
    public function isClientError() {
        return $this->status >= 400 && $this->status < 500;
    }

    /**
     * Helpers: Forbidden?
     */
    public function isForbidden() {
        return $this->status === 403;
    }

    /**
     * Helpers: Informational?
     */
    public function isInformational() {
        return $this->status >= 100 && $this->status < 200;
    }

    /**
     * Helpers: Not Found?
     */
    public function isNotFound() {
        return $this->status === 404;
    }

    /**
     * Helpers: OK?
     */
    public function isOk() {
        return $this->status === 200;
    }

    /**
     * Helpers: Redirect?
     */
    public function isRedirect() {
        return in_array($this->status, array(301, 302, 303, 307));
    }

    /**
     * Helpers: Server Error?
     */
    public function isServerError() {
        return $this->status >= 500 && $this->status < 600;
    }

    /**
     * Helpers: Successful?
     */
    public function isSuccessful() {
        return $this->status >= 200 && $this->status < 300;
    }

    /**
     * Array Access: Offset Exists
     */
    public function offsetExists( $offset ) {
        return isset($this->header[(string)$offset]);
    }

    /**
     * Array Access: Offset Get
     */
    public function offsetGet( $offset ) {
        if ( isset($this->header[(string)$offset]) ) {
            return $this->header[(string)$offset];
        } else {
            return null;
        }
    }

    /**
     * Array Access: Offset Set
     */
    public function offsetSet( $offset, $value ) {
        $this->header[(string)$offset] = (string)$value;
    }

    /**
     * Array Access: Offset Unset
     */
    public function offsetUnset( $offset ) {
        unset($this->header[(string)$offset]);
    }

    /**
     * Iterable: Rewind
     */
    public function rewind() {
        reset($this->header);
    }

    /**
     * Iterable: Current
     */
    public function current() {
        return current($this->header);
    }

    /**
     * Iterable: Key
     */
    public function key() {
        return key($this->header);
    }

    /**
     * Iterable: Next
     */
    public function next() {
        return next($this->header);
    }

    /**
     * Iterable: Valid
     */
    public function valid() {
        return $this->current() !== false;
    }

    /**
     * Countable: Count
     */
    public function count() {
        return count($this->header);
    }
}
?>