<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.0
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
namespace Slim;

/**
 * Configuration
 * Provides app configuration values stored as nested arrays, which can be accessed and stored using dot separated keys.
 *
 * @package    Slim
 * @author     John Porter
 * @since      3.0.0
 */
class Configuration implements \ArrayAccess, IteratorAggregate
{
    /**
     * Cache of previously parsed keys
     * @var array
     */
    protected $keys = array();

    /**
     * Storage array of values
     * @var array
     */
    protected $values = array();

    /**
     * Expected nested key separator
     * @var string
     */
    protected $separator = '.';

    /**
     * Default values
     * @var array
     */
    protected $defaults = array(
        // Application
        'mode' => 'development',
        'view' => null,
        // Cookies
        'cookies.encrypt' => false,
        'cookies.lifetime' => '20 minutes',
        'cookies.path' => '/',
        'cookies.domain' => null,
        'cookies.secure' => false,
        'cookies.httponly' => false,
        // Encryption
        'crypt.key' => 'A9s_lWeIn7cML8M]S6Xg4aR^GwovA&UN',
        'crypt.cipher' => MCRYPT_RIJNDAEL_256,
        'crypt.mode' => MCRYPT_MODE_CBC,
        // Session
        'session.options' => array(),
        'session.handler' => null,
        'session.flash_key' => 'slimflash',
        'session.encrypt' => false,
        // HTTP
        'http.version' => '1.1'
    );

    /**
     * Constructor
     * Merge provided values with the defaults to ensure all required values are set
     * @param array $values
     */
    public function __construct(array $values = array())
    {
        if (!empty($values)) {
            $this->values = $this->mergeArrays($this->values, $this->getDefaults(), $values);
        }
    }

    /**
     * Get the default settings
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Get a value from a nested array based on a separated key
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->getValue($key, $this->values);
    }

    /**
     * Set nested array values based on a separated key
     * @param  string  $key
     * @param  mixed   $value
     * @return array
     */
    public function offsetSet($key, $value)
    {
        $this->setValue($key, $value, $this->values);
    }

    /**
     * Check an array has a value based on a separated key
     * @param  string  $key
     * @return boolean
     */
    public function offsetExists($key)
    {
        return (bool)$this->getValue($key, $this->values);
    }

    /**
     * Remove nested array value based on a separated key
     * @param  string  $key
     */
    public function offsetUnset($key)
    {
        $this->setValue($key, null, $this->values);
    }

    /**
     * Parse a separated key and cache the result
     * @param  string $key
     * @return array
     */
    protected function parseKey($key)
    {
        if (!isset($this->keys[$key])) {
            $this->keys[$key] = explode($this->separator, $key);
        }

        return $this->keys[$key];
    }

    /**
     * Get a value from a nested array based on a separated key
     * @param  string $key
     * @param  array  $array
     * @return mixed
     */
    protected function getValue($key, array $array = array())
    {
        $keys = $this->parseKey($key);

        while (count($keys) > 0 and !is_null($array)) {
            $key = array_shift($keys);
            $array = isset($array[$key]) ? $array[$key] : null;
        }

        return $array;
    }

    /**
     * Set nested array values based on a separated key
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  array   $array
     * @return array
     */
    protected function setValue($key, $value, array &$array = array())
    {
        $keys = $this->parseKey($key, $separator);
        $pointer = &$array;

        while (count($keys) > 0) {
            $key = array_shift($keys);
            $pointer[$key] = (isset($pointer[$key]) ? $pointer[$key] : array());
            $pointer = &$pointer[$key];
        }

        $pointer = $value;
        return $array;
    }

    /**
     * Merge arrays with nested keys
     * Usage: $this->mergeArrays(array $array [, array $...])
     * @return array
     */
    protected function mergeArrays()
    {
        $arrays = func_get_args();
        $merged = array();

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                $merged = $this->setValue($key, $value, $merged)
            }
        }

        return $merged;
    }

    /**
     * Get an ArrayIterator for the stored items
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }
}
