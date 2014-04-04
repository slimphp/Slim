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
use \Slim\Interfaces\EnvironmentInterface;
use \Slim\Interfaces\Http\HeadersInterface;

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
class Headers extends Collection implements HeadersInterface
{
    /**
     * Special header keys to treat like HTTP_ headers
     * @var array
     */
    protected $special = array(
        'CONTENT_TYPE',
        'CONTENT_LENGTH',
        'PHP_AUTH_USER',
        'PHP_AUTH_PW',
        'PHP_AUTH_DIGEST',
        'AUTH_TYPE'
    );

    /**
     * Constructor, will parse an environment for headers if present
     *
     * @param \Slim\Interfaces\EnvironmentInterface $environment
     * @api
     */
    public function __construct(EnvironmentInterface $environment = null)
    {
        if (!is_null($environment)) {
            $this->parseHeaders($environment);
        }
    }

    /**
     * Parse provided headers into this collection
     *
     * @param  \Slim\Interfaces\EnvironmentInterface $environment
     * @return void
     * @api
     */
    public function parseHeaders(EnvironmentInterface $environment)
    {
        foreach ($environment as $key => $value) {
            $key = strtoupper($key);

            if (strpos($key, 'HTTP_') === 0 || in_array($key, $this->special)) {
                if ($key === 'HTTP_CONTENT_TYPE' || $key === 'HTTP_CONTENT_LENGTH') {
                    continue;
                }

                parent::set($this->normalizeKey($key), array($value));
            }
        }
    }

    /**
     * Set data key to value
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     * @api
     */
    public function set($key, $value)
    {
        parent::set($this->normalizeKey($key), array($value));
    }

    /**
     * Get data value with key
     *
     * @param  string $key     The data key
     * @param  mixed  $default The value to return if data key does not exist
     * @return mixed           The data value, or the default value
     * @api
     */
    public function get($key, $asArray = false)
    {
        if ($asArray) {
            return parent::get($this->normalizeKey($key), array());
        } else {
            return implode(', ', parent::get($this->normalizeKey($key), array()));
        }
    }

    /**
     * Add data to key
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     * @api
     */
    public function add($key, $value)
    {
        $header = $this->get($key, true);
        if (is_array($value)) {
            $header = array_merge($header, $value);
        } else {
            $header[] = $value;
        }
        parent::set($this->normalizeKey($key), $header);
    }

    /**
     * Does this set contain a key?
     *
     * @param  string  $key The data key
     * @return boolean
     * @api
     */
    public function has($key)
    {
        return parent::has($this->normalizeKey($key));
    }

    /**
     * Remove value with key from this set
     *
     * @param string $key The data key
     * @api
     */
    public function remove($key)
    {
        parent::remove($this->normalizeKey($key));
    }

    /**
     * Transform header name into canonical form
     *
     * @param  string $key
     * @return string
     */
    public function normalizeKey($key)
    {
        $key = strtolower($key);
        $key = str_replace(array('-', '_'), ' ', $key);
        $key = preg_replace('#^http #', '', $key);
        $key = ucwords($key);
        $key = str_replace(' ', '-', $key);

        return $key;
    }
}
