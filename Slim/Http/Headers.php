<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.3
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
 * Headers
 *
 * This class manages a collection of HTTP headers. Each \Slim\Http\Request
 * and \Slim\Http\Response instance will contain a \Slim\Http\Cookies instance.
 *
 * Because HTTP headers may be upper, lower, or mixed case, this class
 * normalizes the user-requested header name into a canonical internal format
 * so that it can adapt to and successfully handle any header name format.
 *
 * Otherwise, this class extends \Slim\Container and has access to a simple
 * and common interface to manipulate HTTP header data.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.6.0
 */
class Headers extends \Slim\Collection implements \Slim\Interfaces\Http\HeadersInterface
{
    /********************************************************************************
    * Static interface
    *******************************************************************************/

    /**
     * Special-case HTTP headers that are otherwise unidentifiable as HTTP headers.
     * Typically, HTTP headers in the $_SERVER array will be prefixed with
     * `HTTP_` or `X_`. These are not so we list them here for later reference.
     *
     * @var array
     */
    protected static $special = array(
        'CONTENT_TYPE',
        'CONTENT_LENGTH',
        'PHP_AUTH_USER',
        'PHP_AUTH_PW',
        'PHP_AUTH_DIGEST',
        'AUTH_TYPE'
    );

    /**
     * Extract HTTP headers from an array of data (e.g. $_SERVER)
     * @param  array $data
     * @return array
     */
    public static function find($data)
    {
        $results = array();
        foreach ($data as $key => $value) {
            $key = strtoupper($key);
            if (strpos($key, 'HTTP_') === 0 || in_array($key, static::$special)) {
                if ($key === 'HTTP_CONTENT_TYPE' || $key === 'HTTP_CONTENT_LENGTH') {
                    continue;
                }
                $results[$key] = $value;
            }
        }

        return $results;
    }

    /********************************************************************************
    * Instance interface
    *******************************************************************************/

    /**
     * Set data key to value
     * @param string $key   The data key
     * @param mixed  $value The data value
     * @api
     */
    public function set($key, $value)
    {
        parent::set(static::normalizeKey($key), $value);
    }

    /**
     * Get data value with key
     * @param  string $key     The data key
     * @param  mixed  $default The value to return if data key does not exist
     * @return mixed           The data value, or the default value
     * @api
     */
    public function get($key, $default = null)
    {
        return parent::get(static::normalizeKey($key), $default);
    }

    /**
     * Does this set contain a key?
     * @param  string  $key The data key
     * @return boolean
     * @api
     */
    public function has($key)
    {
        return parent::has(static::normalizeKey($key));
    }

    /**
     * Remove value with key from this set
     * @param  string $key The data key
     * @api
     */
    public function remove($key)
    {
        parent::remove(static::normalizeKey($key));
    }

    /**
     * Transform header name into canonical form
     * @param  string $key
     * @return string
     */
    public static function normalizeKey($key)
    {
        $key = strtolower($key);
        $key = str_replace(array('-', '_'), ' ', $key);
        $key = preg_replace('#^http #', '', $key);
        $key = ucwords($key);
        $key = str_replace(' ', '-', $key);

        return $key;
    }
}
