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

use \Slim\Collection;
use \Slim\Interfaces\CryptInterface;
use \Slim\Interfaces\Http\CookiesInterface;
use \Slim\Interfaces\Http\HeadersInterface;

/**
 * Cookies
 *
 * This class manages a collection of HTTP cookies. Each
 * \Slim\Http\Request and \Slim\Http\Response instance will contain a
 * \Slim\Http\Cookies instance.
 *
 * This class has several helper methods used to parse
 * HTTP `Cookie` headers and to serialize cookie data into
 * HTTP headers.
 *
 * Like many other Slim application objects, \Slim\Http\Cookies extends
 * \Slim\Container so you have access to a simple and common interface
 * to manipulate HTTP cookies.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   2.3.0
 */
class Cookies extends Collection implements CookiesInterface
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
     * Constructor
     *
     * @param array $data
     */
    public function __construct(array $data = null, array $defaults = null)
    {
        if ($defaults) {
            $this->setDefaults($defaults);
        }

        if ($data) {
            $this->replace($data);
        }
    }

    /**
     * Set default cookie properties
     *
     * @param array
     */
    public function setDefaults(array $settings)
    {
        $this->defaults = array_merge($this->defaults, $settings);
    }

    /**
     * Get default values
     *
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
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
            $settings = array_replace($this->defaults, $value);
        } else {
            $settings = array_replace($this->defaults, array('value' => $value));
        }

        parent::set($key, $settings);
    }

    /**
     * Remove cookie
     *
     * Unlike \Slim\Collection, this will actually *set* a cookie with
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
     * Get cookie as header string
     *
     * @param  string $name The cookie name
     * @return string
     */
    public function getAsString($name)
    {
        $output = null;
        $cookie = $this->get($name);
        if ($cookie) {
            $value = (string)$cookie['value'];
            $parts = [];

            if (isset($cookie['domain']) && $cookie['domain']) {
                $parts[] = '; domain=' . $cookie['domain'];
            }

            if (isset($cookie['path']) && $cookie['path']) {
                $parts[] = '; path=' . $cookie['path'];
            }

            if (isset($cookie['expires'])) {
                if (is_string($cookie['expires'])) {
                    $timestamp = strtotime($cookie['expires']);
                } else {
                    $timestamp = (int)$cookie['expires'];
                }

                if ($timestamp !== 0) {
                    $parts[] = '; expires=' . gmdate('D, d-M-Y H:i:s e', $timestamp);
                }
            }

            if (isset($cookie['secure']) && $cookie['secure']) {
                $parts[] = '; secure';
            }

            if (isset($cookie['httponly']) && $cookie['httponly']) {
                $parts[] = '; HttpOnly';
            }

            $output = sprintf(
                '%s=%s',
                urlencode($name),
                urlencode($value) . implode('', $parts)
            );
        }

        return $output;
    }

    /**
     * Encrypt cookies
     *
     * This method iterates and encrypts data values.
     *
     * @param \Slim\Interfaces\CryptInterface $crypt
     * @api
     */
    public function encrypt(CryptInterface $crypt)
    {
        foreach ($this as $name => $settings) {
            $settings['value'] = $crypt->encrypt($settings['value']);
            $this->set($name, $settings);
        }
    }

    /**
     * Serialize this collection of cookies into a raw HTTP header
     *
     * @param \Slim\Interfaces\Http\HeadersInterface $headers
     * @api
     */
    public function setHeaders(HeadersInterface &$headers)
    {
        foreach ($this->data as $name => $settings) {
            $this->setHeader($headers, $name, $settings);
        }
    }

    /**
     * Parse cookie header
     *
     * This method will parse the HTTP request's `Cookie` header
     * and extract an associative array of cookie names and values.
     *
     * @param  string $header The `Cookie:` requet header's value
     * @return array
     * @api
     */
    public static function parseHeader($header)
    {
        if (is_array($header) === true) {
            $header = $header[0];
        }

        if (is_string($header) === false) {
            throw new \InvalidArgumentException('Cannot parse Cookie data. Header value must be a string.');
        }

        $header = rtrim($header, "\r\n");
        $pieces = preg_split('@\s*[;,]\s*@', $header);
        $cookies = array();

        foreach ($pieces as $cookie) {
            $cookie = explode('=', $cookie, 2);

            if (count($cookie) === 2) {
                $key = urldecode($cookie[0]);
                $value = urldecode($cookie[1]);

                if (!isset($cookies[$key])) {
                    $cookies[$key] = $value;
                }
            }
        }

        return $cookies;
    }
}
