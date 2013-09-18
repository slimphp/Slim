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

use \ArrayAccess;
use \IteratorAggregate;
use \Slim\NestedArrayHandler;

/**
 * Configuration
 *
 * A simple class, extending an array helper to allow setting and getting of
 * properties based on a separated key, default is . separated. Uses ArrayAccess and
 * IteratorAggregate like the Slim\Container class, as it can't directly extend that
 * class.
 *
 * @package    Slim
 * @author     John Porter
 * @since      3.0.0
 */
class Configuration extends NestedArrayHandler implements ArrayAccess, IteratorAggregate
{
    /**
     * Array hierarchy of Configuration items.
     *
     * @var array
     */
    protected $items = array();

    /**
     * Allow the class to be instantiated with an array of configuration values
     *
     * @param array $configuration
     */
    public function __construct(array $configuration = array() $mergeDefaults = false)
    {
        if (!empty($array)) {
            $this->set($configuration);
        }
    }

    /**
     * Get a Configuration value from a nested array based on a separated key
     *
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        return parent::getValue($key, $this->items);
    }

    /**
     * Set Configuration values based on separated key
     *
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public function set($key, $value = null)
    {
        if (func_num_args() === 1 && is_array($key)) {
            //var_dump($key);die;
            foreach ($key as $keyy => $valuee) {
                $this->set($keyy, $valuee);
            }

            return $this->items;
        }

        return parent::setValue($key, $value, $this->items);
    }

    /**
     * Check Configuration has a value based on a separated key
     *
     * @param  string  $key
     * @return boolean
     */
    public function has($key)
    {
        return parent::hasKey($key, $this->items);
    }

    /**
     * Remove Configuration value based on a separated key
     *
     * @param  string $key
     * @return boolean
     */
    public function remove($key)
    {
        return (bool)parent::removeValue($key, $this->items);
    }

    /**
     * Flatten Configuration array to a single array with separated keys
     *
     * @param  array  $array
     * @param  string $prepend
     * @return array
     */
    public function flatten()
    {
        return parent::flattenArray($this->items);
    }

    /**
     * Get all of the Configuration items.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->items;
    }

    /**
     * Get a Configuration value from a nested array based on a separated key
     * Uses ArrayAccess
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set Configuration values based on separated key
     * Uses ArrayAccess
     *
     * @param  string  $key
     * @param  string  $value
     * @return array
     */
    public function offsetSet($key, $value)
    {
        return $this->set($key, $value);
    }

    /**
     * Check Configuration has a value based on a separated key
     * Uses ArrayAccess
     *
     * @param  string  $key
     * @return boolean
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Remove Configuration value based on a separated key
     * Uses ArrayAccess
     *
     * @param  string  $key
     * @return boolean
     */
    public function offsetUnset($key)
    {
        return $this->remove($key);
    }

    /**
     * Get a Configuration value from a nested array based on a separated key
     * Uses Magic Method Access
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Set Configuration values based on separated key
     * Uses Megic Method Access
     *
     * @param  string  $key
     * @param  string  $value
     * @return array
     */
    public function __set($key, $value)
    {
        return $this->set($key, $value);
    }

    /**
     * Check Configuration has a value based on a separated key
     * Uses Magic Method Access
     *
     * @param  string  $key
     * @return boolean
     */
    public function __isset($key)
    {
        return $this->has($key);
    }

    /**
     * Remove Configuration value based on a separated key
     * Uses Magic Method Access
     *
     * @param  string  $key
     * @return boolean
     */
    public function __unset($key)
    {
        $this->remove($key);
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
