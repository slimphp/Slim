<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.5
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

/**
 * Cookies
 *
 * This class manages a collection of HTTP response cookies. Each
 * \Slim\Http\Response instance will contain a \Slim\Http\Cookies instance.
 *
 * This class also has several static helper methods used to parse
 * HTTP request `Cookie` headers and to serialize cookie data into
 * HTTP response headers.
 *
 * Like many other Slim application objects, \Slim\Http\Cookies extends
 * \Slim\Container so you have access to a simple and common interface
 * to manipulate HTTP response cookies.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   2.3.0
 */
class Cookies extends \Slim\Collection implements \Slim\Interfaces\Http\CookiesInterface
{
    /**
     * Default cookie settings
     * @var array
     */
    protected $defaults = array(
        'value' => '',
        'domain' => null,
        'path' => null,
        'expires' => null,
        'secure' => false,
        'httponly' => false
    );

    /**
     * Extract cookies from HTTP headers
     * @param  \Slim\Http\Headers $headers
     * @return array
     */
    public static function extractFromHeaders(\Slim\Http\Headers $headers)
    {
        return static::parseCookieHeader($headers->get('Cookie', ''));
    }

    /**
     * Set cookie
     *
     * The second argument may be a single scalar value, in which case
     * it will be merged with the default settings and considered the `value`
     * of the merged result.
     *
     * The second argument may also be an array containing any or all of
     * the keys shown in the default settings above. This array will be
     * merged with the defaults shown above.
     *
     * @param string $key   Cookie name
     * @param mixed  $value Cookie settings
     * @api
     */
    public function set($key, $value)
    {
        if (is_array($value)) {
            $cookieSettings = array_replace($this->defaults, $value);
        } else {
            $cookieSettings = array_replace($this->defaults, array('value' => $value));
        }
        parent::set($key, $cookieSettings);
    }

    /**
     * Remove cookie
     *
     * Unlike \Slim\Container, this will actually *set* a cookie with
     * an expiration date in the past. This expiration date will force
     * the client-side cache to remove its cookie with the given name
     * and settings.
     *
     * @param string $key      Cookie name
     * @param array  $settings Optional cookie settings
     * @api
     */
    public function remove($key, $settings = array())
    {
        $settings['value'] = '';
        $settings['expires'] = time() - 86400;
        $this->set($key, array_replace($this->defaults, $settings));
    }

    /**
     * Encrypt cookies
     *
     * This method iterates and encrypts data values.
     *
     * @param \Slim\Crypt $crypt
     * @api
     */
    public function encrypt(\Slim\Interfaces\CryptInterface $crypt)
    {
        foreach ($this as $name => $settings) {
            $settings['value'] = $crypt->encrypt($settings['value']);
            $this->set($name, $settings);
        }
    }

    /********************************************************************************
     * Static Methods
     *******************************************************************************/

    /**
     * Serialize cookies into HTTP response header
     * @param  \Slim\Http\Headers $headers The Response headers
     * @param  \Slim\Http\Cookies $cookies The Response cookies
     * @param  array              $config  The Slim app settings
     */
    public static function serializeCookies(&$headers, \Slim\Http\Cookies $cookies)
    {
        foreach ($cookies as $name => $settings) {
            if (is_string($settings['expires'])) {
                $expires = strtotime($settings['expires']);
            } else {
                $expires = (int)$settings['expires'];
            }
            static::setCookieHeader($headers, $name, $settings);
        }
    }

    /**
     * Set HTTP cookie header
     *
     * This method will construct and set the HTTP `Set-Cookie` header. Slim
     * uses this method instead of PHP's native `setcookie` method. This allows
     * more control of the HTTP header irrespective of the native implementation's
     * dependency on PHP versions.
     *
     * This method accepts the Slim_Http_Headers object by reference as its
     * first argument; this method directly modifies this object instead of
     * returning a value.
     *
     * @param  \Slim\Http\Headers  $header
     * @param  string              $name
     * @param  string              $value
     * @return void
     */
    public static function setCookieHeader(&$header, $name, $value)
    {
        //Build cookie header
        if (is_array($value)) {
            $domain = '';
            $path = '';
            $expires = '';
            $secure = '';
            $httponly = '';
            if (isset($value['domain']) && $value['domain']) {
                $domain = '; domain=' . $value['domain'];
            }
            if (isset($value['path']) && $value['path']) {
                $path = '; path=' . $value['path'];
            }
            if (isset($value['expires'])) {
                if (is_string($value['expires'])) {
                    $timestamp = strtotime($value['expires']);
                } else {
                    $timestamp = (int) $value['expires'];
                }
                if ($timestamp !== 0) {
                    $expires = '; expires=' . gmdate('D, d-M-Y H:i:s e', $timestamp);
                }
            }
            if (isset($value['secure']) && $value['secure']) {
                $secure = '; secure';
            }
            if (isset($value['httponly']) && $value['httponly']) {
                $httponly = '; HttpOnly';
            }
            $cookie = sprintf('%s=%s%s', urlencode($name), urlencode((string) $value['value']), $domain . $path . $expires . $secure . $httponly);
        } else {
            $cookie = sprintf('%s=%s', urlencode($name), urlencode((string) $value));
        }

        //Set cookie header
        if (!isset($header['Set-Cookie']) || $header['Set-Cookie'] === '') {
            $header['Set-Cookie'] = $cookie;
        } else {
            $header['Set-Cookie'] = implode("\n", array($header['Set-Cookie'], $cookie));
        }
    }

    /**
     * Delete HTTP cookie header
     *
     * This method will construct and set the HTTP `Set-Cookie` header to invalidate
     * a client-side HTTP cookie. If a cookie with the same name (and, optionally, domain)
     * is already set in the HTTP response, it will also be removed. Slim uses this method
     * instead of PHP's native `setcookie` method. This allows more control of the HTTP header
     * irrespective of PHP's native implementation's dependency on PHP versions.
     *
     * This method accepts the \Slim\Http\Headers object by reference as its
     * first argument; this method directly modifies this object instead of
     * returning a value.
     *
     * @param  array  $header
     * @param  string $name
     * @param  array  $value
     */
    public static function deleteCookieHeader(&$header, $name, $value = array())
    {
        //Remove affected cookies from current response header
        $cookiesOld = array();
        $cookiesNew = array();
        if (isset($header['Set-Cookie'])) {
            $cookiesOld = explode("\n", $header['Set-Cookie']);
        }
        foreach ($cookiesOld as $c) {
            if (isset($value['domain']) && $value['domain']) {
                $regex = sprintf('@%s=.*domain=%s@', urlencode($name), preg_quote($value['domain']));
            } else {
                $regex = sprintf('@%s=@', urlencode($name));
            }
            if (preg_match($regex, $c) === 0) {
                $cookiesNew[] = $c;
            }
        }
        if ($cookiesNew) {
            $header['Set-Cookie'] = implode("\n", $cookiesNew);
        } else {
            unset($header['Set-Cookie']);
        }

        //Set invalidating cookie to clear client-side cookie
        static::setCookieHeader($header, $name, array_merge(array('value' => '', 'path' => null, 'domain' => null, 'expires' => time() - 100), $value));
    }

    /**
     * Parse cookie header
     *
     * This method will parse the HTTP request's `Cookie` header
     * and extract cookies into an associative array.
     *
     * @param  string
     * @return array
     */
    public static function parseCookieHeader($header)
    {
        $cookies = array();
        $header = rtrim($header, "\r\n");
        $headerPieces = preg_split('@\s*[;,]\s*@', $header);

        foreach ($headerPieces as $c) {
            $cParts = explode('=', $c);
            if (count($cParts) === 2) {
                $key = urldecode($cParts[0]);
                $value = urldecode($cParts[1]);
                if (!isset($cookies[$key])) {
                    $cookies[$key] = $value;
                }
            }
        }

        return $cookies;
    }
}
