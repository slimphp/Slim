<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.0.0
 * @package     Slim
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
namespace Slim\Http;

class Cookies
{
    /**
     * @var $request
     */
     protected $request;

     /**
      * @var $response
      */
      protected $response;

    /**
     * @var $settings
     */
     protected $settings;

    /**
     * Constructor
     *
     * @param \Slim\Request $request
     * @param \Slim\Response $response
     * @param array $settings
     */
     public function __construct($request, $response, $settings = array()) {
         // default cookies settings
         $defaultSettings = array(
             'cookies.lifetime' => '20 minutes',
             'cookies.path' => '/',
             'cookies.domain' => null,
             'cookies.secure' => false,
             'cookies.httponly' => false,
             'cookies.secret_key' => 'CHANGE_ME',
             'cookies.cipher' => MCRYPT_RIJNDAEL_256,
             'cookies.cipher_mode' => MCRYPT_MODE_CBC,
         );

         $this->request = $request;
         $this->response = $response;
         $this->settings = array_merge($defaultSettings, $settings);
     }

    /********************************************************************************
    * HTTP Cookies
    *******************************************************************************/

    /**
     * Set unencrypted HTTP cookie
     *
     * @param string     $name      The cookie name
     * @param string     $value     The cookie value
     * @param int|string $time      The duration of the cookie;
     *                                  If integer, should be UNIX timestamp;
     *                                  If string, converted to UNIX timestamp with `strtotime`;
     * @param string     $path      The path on the server in which the cookie will be available on
     * @param string     $domain    The domain that the cookie is available to
     * @param bool       $secure    Indicates that the cookie should only be transmitted over a secure
     *                              HTTPS connection to/from the client
     * @param bool       $httponly  When TRUE the cookie will be made accessible only through the HTTP protocol
     */
    public function setCookie($name, $value, $time = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        $this->response->setCookie($name, array(
            'value' => $value,
            'expires' => is_null($time) ? $this->settings['cookies.lifetime'] : $time,
            'path' => is_null($path) ? $this->settings['cookies.path'] : $path,
            'domain' => is_null($domain) ? $this->settings['cookies.domain'] : $domain,
            'secure' => is_null($secure) ? $this->settings['cookies.secure'] : $secure,
            'httponly' => is_null($httponly) ? $this->settings['cookies.httponly'] : $httponly
        ));
    }

    /**
     * Get value of unencrypted HTTP cookie
     *
     * Return the value of a cookie from the current HTTP request,
     * or return NULL if cookie does not exist. Cookies created during
     * the current request will not be available until the next request.
     *
     * @param  string      $name
     * @return string|null
     */
    public function getCookie($name)
    {
        return $this->request->cookies($name);
    }

    /**
     * Set encrypted HTTP cookie
     *
     * @param string    $name       The cookie name
     * @param mixed     $value      The cookie value
     * @param mixed     $expires    The duration of the cookie;
     *                                  If integer, should be UNIX timestamp;
     *                                  If string, converted to UNIX timestamp with `strtotime`;
     * @param string    $path       The path on the server in which the cookie will be available on
     * @param string    $domain     The domain that the cookie is available to
     * @param bool      $secure     Indicates that the cookie should only be transmitted over a secure
     *                              HTTPS connection from the client
     * @param  bool     $httponly   When TRUE the cookie will be made accessible only through the HTTP protocol
     */
    public function setEncryptedCookie($name, $value, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        $expires = is_null($expires) ? $this->settings['cookies.lifetime'] : $expires;
        if (is_string($expires)) {
            $expires = strtotime($expires);
        }
        $secureValue = \Slim\Http\Util::encodeSecureCookie(
            $value,
            $expires,
            $this->settings['cookies.secret_key'],
            $this->settings['cookies.cipher'],
            $this->settings['cookies.cipher_mode']
        );
        $this->setCookie($name, $secureValue, $expires, $path, $domain, $secure, $httponly);
    }

    /**
     * Get value of encrypted HTTP cookie
     *
     * Return the value of an encrypted cookie from the current HTTP request,
     * or return NULL if cookie does not exist. Encrypted cookies created during
     * the current request will not be available until the next request.
     *
     * @param  string       $name
     * @return string|false
     */
    public function getEncryptedCookie($name, $deleteIfInvalid = true)
    {
        $value = \Slim\Http\Util::decodeSecureCookie(
            $this->request->cookies($name),
            $this->settings['cookies.secret_key'],
            $this->settings['cookies.cipher'],
            $this->settings['cookies.cipher_mode']
        );
        if ($value === false && $deleteIfInvalid) {
            $this->deleteCookie($name);
        }

        return $value;
    }

    /**
     * Delete HTTP cookie (encrypted or unencrypted)
     *
     * Remove a Cookie from the client. This method will overwrite an existing Cookie
     * with a new, empty, auto-expiring Cookie. This method's arguments must match
     * the original Cookie's respective arguments for the original Cookie to be
     * removed. If any of this method's arguments are omitted or set to NULL, the
     * default Cookie setting values will be used instead.
     *
     * @param string    $name       The cookie name
     * @param string    $path       The path on the server in which the cookie will be available on
     * @param string    $domain     The domain that the cookie is available to
     * @param bool      $secure     Indicates that the cookie should only be transmitted over a secure
     *                              HTTPS connection from the client
     * @param  bool     $httponly   When TRUE the cookie will be made accessible only through the HTTP protocol
     */
    public function deleteCookie($name, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        $this->response->deleteCookie($name, array(
            'domain' => is_null($domain) ? $this->settings['cookies.domain'] : $domain,
            'path' => is_null($path) ? $this->settings['cookies.path'] : $path,
            'secure' => is_null($secure) ? $this->settings['cookies.secure'] : $secure,
            'httponly' => is_null($httponly) ? $this->settings['cookies.httponly'] : $httponly
        ));
    }
}
