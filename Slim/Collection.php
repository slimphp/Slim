<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use \Slim\Interfaces\CollectionInterface;
use \Slim\Interfaces\CryptInterface;

/**
 * Collection
 *
 * This class provides a common interface used by many other
 * classes in a Slim application that manage "collections"
 * of data that must be inspected and/or manipulated
 */
class Collection implements CollectionInterface, \ArrayAccess, \IteratorAggregate
{
    /**
     * The source data
     *
     * @var array
     */
    protected $data = array();

    /**
     * Create new collection
     *
     * @param array $items Pre-populate collection with this key-value array
     */
    public function __construct(array $items = array())
    {
        $this->replace($items);
    }

    /********************************************************************************
     * Collection interface
     *******************************************************************************/

    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public function set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Get collection item for key
     *
     * @param  string $key     The data key
     * @param  mixed  $default The default value to return if data key does not exist
     * @return mixed           The key's value, or the default value
     */
    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->offsetGet($key);
        }

        return $default;
    }

    /**
     * Add item to collection
     *
     * @param array $items Key-value array of data to append to this collection
     */
    public function replace(array $items)
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Get all items in collection
     *
     * @return array The collection's source data
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Get collection keys
     *
     * @return array The collection's source data keys
     */
    public function keys()
    {
        return array_keys($this->data);
    }

    /**
     * Does this collection have a given key?
     *
     * @param  string $key The data key
     * @return bool
     */
    public function has($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     */
    public function remove($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Remove all items from collection
     */
    public function clear()
    {
        $this->data = array();
    }

    /**
     * Encrypt collection values
     *
     * @param CryptInterface $crypt
     */
    public function encrypt(CryptInterface $crypt)
    {
        foreach ($this->data as $key => $value) {
            $this->set($key, $crypt->encrypt($value));
        }
    }

    /**
     * Decrypt collection values
     *
     * @param CryptInterface $crypt
     */
    public function decrypt(CryptInterface $crypt)
    {
        foreach ($this->data as $key => $value) {
            $this->set($key, $crypt->decrypt($value));
        }
    }

    /********************************************************************************
     * ArrayAccess interface
     *******************************************************************************/

    /**
     * Does this collection have a given key?
     *
     * @param  string $key The data key
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get collection item for key
     *
     * @param  string $key The data key
     * @return mixed       The key's value, or the default value
     */
    public function offsetGet($key)
    {
        return $this->data[$key];
    }

    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public function offsetSet($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     */
    public function offsetUnset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Get number of items in collection
     *
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /********************************************************************************
     * IteratorAggregate interface
     *******************************************************************************/

    /**
     * Get collection iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
}
