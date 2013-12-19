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
namespace Slim;

class Collection implements \Countable, \IteratorAggregate, \Slim\Interfaces\CollectionInterface
{
    /**
     * Key-value array of data
     * @var array
     */
    protected $data = array();

    /**
     * Constructor
     * @param array $items Pre-populate collection with this key-value array
     * @api
     */
    public function __construct(array $items = array())
    {
        $this->replace($items);
    }

    /**
     * Set data key to value
     * @param string $key   The data key
     * @param mixed  $value The data value
     * @api
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
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
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return $default;
    }

    /**
     * Add data to set
     * @param array $items Key-value array of data to append to this set
     * @api
     */
    public function replace(array $items)
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Fetch set data
     * @return array This set's key-value data array
     * @api
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Fetch set data keys
     * @return array This set's key-value data array keys
     * @api
     */
    public function keys()
    {
        return array_keys($this->data);
    }

    /**
     * Does this set contain a key?
     * @param  string  $key The data key
     * @return boolean
     * @api
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Remove value with key from this set
     * @param  string $key The data key
     * @api
     */
    public function remove($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Clear all values
     * @api
     */
    public function clear()
    {
        $this->data = array();
    }

    /**
     * Encrypt set
     * @param  \Slim\Crypt $crypt
     * @api
     */
    public function encrypt(\Slim\Interfaces\CryptInterface $crypt)
    {
        foreach ($this as $elementName => $elementValue) {
            $this->set($elementName, $crypt->encrypt($elementValue));
        }
    }

    /**
     * Decrypt set
     * @param  \Slim\Crypt $crypt
     * @api
     */
    public function decrypt(\Slim\Interfaces\CryptInterface $crypt)
    {
        foreach ($this as $elementName => $elementValue) {
            $this->set($elementName, $crypt->decrypt($elementValue));
        }
    }

    /**
     * Get number of items in collection
     * @return int
     * @api
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Get collection iterator
     * @return \ArrayIterator
     * @api
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
}
