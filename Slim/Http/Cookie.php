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
 * Cookie
 *
 * Object-oriented representation of a Cookie to be sent in an HTTP response
 *
 * @package Slim
 * @author  Josh Lockhart <info@joshlockhart.com>
 * @since   Version 1.0
 */
class Slim_Http_Cookie {

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
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

    /**
     * Constructor
     * @param   string  $name       The cookie name
     * @param   string  $value      The cookie value
     * @param   mixed   $time       The duration of the cookie;
     *                              If integer, should be a UNIX timestamp;
     *                              If string, converted to UNIX timestamp with `strtotime`;
     * @param   string  $path       The path on the server in which the cookie will be available on
     * @param   string  $domain     The domain that the cookie is available to
     * @param   bool    $secure     Indicates that the cookie should only be transmitted over a secure
     *                              HTTPS connection from the client
     * @param   bool    $httponly   When TRUE the cookie will be made accessible only through the HTTP protocol
     * @return  void
     */
    public function __construct( $name, $value = null, $expires = 0, $path = null, $domain = null, $secure = false, $httponly = false ) {
        $this->setName($name);
        $this->setValue($value);
        $this->setExpires($expires);
        $this->setPath($path);
        $this->setDomain($domain);
        $this->setSecure($secure);
        $this->setHttpOnly($httponly);
    }

    /**
     * Get cookie name
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set cookie name
     * @param   string $name
     * @return  void
     */
    public function setName( $name ) {
        $this->name = (string)$name;
    }

    /**
     * Get cookie value
     * @return string
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * Set cookie value
     * @param   string $value
     * @return  void
     */
    public function setValue( $value ) {
        $this->value = (string)$value;
    }

    /**
     * Get cookie expiration time
     * @return int UNIX timestamp
     */
    public function getExpires() {
        return $this->expires;
    }

    /**
     * Set cookie expiration time
     * @param   string|int Cookie expiration time
     * @return  void
     */
    public function setExpires( $time ) {
        $this->expires = is_string($time) ? strtotime($time) : (int)$time;
    }

    /**
     * Get cookie path
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Set cookie path
     * @param   string $path
     * @return  void
     */
    public function setPath( $path ) {
        $this->path = (string)$path;
    }

    /**
     * Get cookie domain
     * @return string
     */
    public function getDomain() {
        return $this->domain;
    }

    /**
     * Set cookie domain
     * @param   string $domain
     * @return  void
     */
    public function setDomain( $domain ) {
        $this->domain = (string)$domain;
    }

    /**
     * Is cookie sent only if SSL/HTTPS is used?
     * @return bool
     */
    public function getSecure() {
        return $this->secure;
    }

    /**
     * Set whether cookie is sent only if SSL/HTTPS is used
     * @param   bool $secure
     * @return  void
     */
    public function setSecure( $secure ) {
        $this->secure = (bool)$secure;
    }

    /**
     * Is cookie sent with HTTP protocol only?
     * @return bool
     */
    public function getHttpOnly() {
        return $this->httponly;
    }

    /**
     * Set whether cookie is sent with HTTP protocol only
     * @param   bool $httponly
     * @return  void
     */
    public function setHttpOnly( $httponly ) {
        $this->httponly = (bool)$httponly;
    }

}