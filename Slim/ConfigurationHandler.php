<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use Slim\Interfaces\ConfigurationHandlerInterface;

/**
 * ConfigurationHandler
 *
 * This is a default Configuration class which provides app configuration
 * values stored as nested arrays, which can be accessed and stored using
 * dot separated keys.
 */
class ConfigurationHandler implements ConfigurationHandlerInterface
{
    /**
     * Cache of previously parsed keys
     *
     * @var array
     */
    protected $keys = array();

    /**
     * Storage array of values
     *
     * @var array
     */
    protected $values = array();

    /**
     * Expected nested key separator
     *
     * @var string
     */
    protected $separator = '.';

    /**
     * Set an array of configuration options
     * Merge provided values with the defaults to ensure all required values are set
     *
     * @param array $values
     */
    public function setArray(array $values = array())
    {
        $this->values = $this->mergeArrays($this->values, $values);
    }

    /**
     * Get all values as nested array
     *
     * @return array
     */
    public function getAllNested()
    {
        return $this->values;
    }

    /**
     * Get all values as flattened key array
     *
     * @return array
     */
    public function getAllFlat()
    {
        return $this->flattenArray($this->values);
    }

    /**
     * Get all flattened array keys
     *
     * @return array
     */
    public function getKeys()
    {
        $flattened = $this->flattenArray($this->values);
        return array_keys($flattened);
    }

    /**
     * Get a value from a nested array based on a separated key
     *
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->getValue($key, $this->values);
    }

    /**
     * Set nested array values based on a separated key
     *
     * @param  string $key
     * @param  mixed  $value
     * @return array
     */
    public function offsetSet($key, $value)
    {
        $this->setValue($key, $value, $this->values);
    }

    /**
     * Check an array has a value based on a separated key
     * @param  string $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return (bool)$this->getValue($key, $this->values);
    }

    /**
     * Remove nested array value based on a separated key
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        $keys = $this->parseKey($key);
        $array = &$this->values;

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                return;
            }

            $array =& $array[$key];
        }

        unset($array[array_shift($keys)]);
    }

    /**
     * Parse a separated key and cache the result
     *
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
     *
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
     * @param  string $key
     * @param  mixed  $value
     * @param  array  $array
     * @return array
     */
    protected function setValue($key, $value, array &$array = array())
    {
        $keys = $this->parseKey($key, $this->separator);
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
     * Merge arrays with nested keys into the values store
     *
     * Usage: $this->mergeArrays(array $array [, array $...])
     *
     * @return array
     */
    protected function mergeArrays()
    {
        $args = func_get_args();
        $merged = array_shift($args);
        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if (is_integer($k)) {
                    if (isset($merged[$k])) {
                        $merged[] = $v;
                    } else {
                        $merged[$k] = $v;
                    }
                } elseif (is_array($v) && isset($merged[$k]) && is_array($merged[$k])) {
                    $merged[$k] = $this->mergeArrays($merged[$k], $v);
                } else {
                    $this->setValue($k, $v, $merged);
                }
            }
        }

        return $merged;
    }

    /**
     * Flatten a nested array to a separated key
     *
     * @param  array  $array
     * @param  string $separator
     * @param  string $prepend
     * @return array
     */
    protected function flattenArray(array $array, $separator = null, $prepend = '')
    {
        $flattened = array();

        if (is_null($separator)) {
            $separator = $this->separator;
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $flattened = array_merge($flattened, $this->flattenArray($value, $separator, $prepend.$key.$separator));
            } else {
                $flattened[$prepend.$key] = $value;
            }
        }

        return $flattened;
    }

    /**
     * Get an ArrayIterator for the stored items
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }
}
