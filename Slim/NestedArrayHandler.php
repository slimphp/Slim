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
 * NestedArrayHandler
 *
 * A simple class helper to allow setting and getting of properties based on a
 * separated key, default is . separated.
 *
 * @package    Slim
 * @author     John Porter
 * @since      3.0.0
 */
class NestedArrayHandler
{
    /**
     * Cache of previously parsed keys
     * @var array
     */
    protected $keys = array();

    /**
     * Parse a separated key and cache the result
     *
     * @param  string $key
     * @param  string $separator
     * @return array
     */
    protected function parseKey($key, $separator = '.')
    {
        if (!isset($this->keys[$key])) {
            $this->keys[$key] = explode($separator, $key);
        }

        return $this->keys[$key];
    }

    /**
     * Get a value from a nested array based on a separated key
     *
     * @param  string $key
     * @param  array  $array
     * @param  string $separator
     * @return mixed
     */
    protected function getValue($key, array $array = array(), $separator = '.')
    {
        $keys = $this->parseKey($key, $separator);

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
     * @param  array   $input
     * @param  string  $separator
     * @return array
     */
    protected function setValue($key, $value, array &$array = array(), $separator = '.')
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
     * Check an array has a value based on a separated key
     *
     * @param  string  $key
     * @param  array   $array
     * @param  string  $separator
     * @return boolean
     */
    protected function hasKey($key, array $array = array(), $separator = '.')
    {
        return (bool)$this->get($key, $array);
    }

    /**
     * Remove nested array value based on a separated key
     *
     * @param  string  $key
     * @param  array   $array
     * @param  string  $separator
     * @return boolean
     */
    protected function removeValue($key, array &$array, $separator = '.')
    {
        return $this->set($key, null, $array, $separator);
    }

    /**
     * Flatten a nested array to a separated key
     *
     * @param  array   $array
     * @param  string  $separator
     * @return array
     */
    protected function flattenArray(array $array, $separator = '.')
    {
        $flattened = array();

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $flattened = array_merge($flattened, $this->flatten($value, $separator.$key));
            } else {
                $flattened[$separator.$key] = $value;
            }
        }

        return $flattened;
    }
}
