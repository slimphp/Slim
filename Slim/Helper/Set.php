<?php
namespace Slim\Helper;

class Set implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * Key-value array of arbitrary data
     * @var array
     */
    protected $data = array();

    /**
     * Constructor
     * @param array $items Prepopulate set with this key-value array
     */
    public function __construct($items = array())
    {
        $this->add($items);
    }

    /**
     * Normalize data key
     *
     * Used to transform data key into the necessary
     * key format for this set. Used in subclasses
     * like \Slim\Http\Headers.
     *
     * @param  string $key The data key
     * @return mixed       The transformed/normalized data key
     */
    protected function normalizeKey($key)
    {
        return $key;
    }

    /**
     * Set data key to value
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public function set($key, $value)
    {
        $this->data[$this->normalizeKey($key)] = $value;
    }

    /**
     * Get data value with key
     * @param  string $key     The data key
     * @param  mixed  $default The value to return if data key does not exist
     * @return mixed           The data value, or the default value
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->data[$this->normalizeKey($key)];
        }

        return $default;
    }

    /**
     * Add data to set
     * @param array $items Key-value array of data to append to this set
     */
    public function add($items)
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value); // Ensure keys are normalized
        }
    }

    /**
     * Fetch set data
     * @return array This set's key-value data array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Fetch set data keys
     * @return array This set's key-value data array keys
     */
    public function keys()
    {
        return array_keys($this->data);
    }

    /**
     * Does this set contain a key?
     * @param  string  $key The data key
     * @return boolean
     */
    public function has($key)
    {
        return array_key_exists($this->normalizeKey($key), $this->data);
    }

    /**
     * Remove value with key from this set
     * @param  string $key The data key
     */
    public function remove($key)
    {
        unset($this->data[$this->normalizeKey($key)]);
    }

    /**
     * Array Access
     */

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Countable
     */

    public function count()
    {
        return count($this->data);
    }

    /**
     * IteratorAggregate
     */

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
}
