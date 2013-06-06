<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.2.0
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
 * Container
 *
 * This is an IoC (Inversion of Control) container for the Slim Framework.
 * It allows simple and configurable dependency injection for most of the
 * Slim application's component objects (e.g. Request, Response, Router).
 *
 * This class is largely inspired by Fabien Potencier's Pimple component
 * which you can find here: https://github.com/fabpot/Pimple
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   2.3.0
 */
class Container implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $registry;

    /**
     * Constructor
     * @param array $values The default registry objects or values
     */
    public function __construct(array $values = array())
    {
        $this->registry = $values;
    }

    /**
     * Sets a value or object
     * @param  string $key   The name of the value or object
     * @param  mixed  $value The value or Closure to define the object
     */
    public function offsetSet($key, $value)
    {
        $this->registry[$key] = $value;
    }

    /**
     * Gets a value or object
     * @param   string $key               The name of the value or object
     * @return  mixed                     The value or object
     * @throws  \InvalidArgumentException If the container key is not defined
     */
    public function offsetGet($key)
    {
        if (!isset($this->registry[$key])) {
            throw new \InvalidArgumentException(sprintf('Container key "%s" is not defined', $key));
        }

        $isInvokable = is_object($this->registry[$key]) && method_exists($this->registry[$key], '__invoke');

        return $isInvokable ? $this->registry[$key]($this) : $this->registry[$key];
    }

    /**
     * Does a value or object exist for key?
     * @param  string $key The name of the value or object
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->registry);
    }

    /**
     * Unsets a value or object
     * @param  string $key The name of the value or object
     */
    public function offsetUnset($key)
    {
        unset($this->registry[$key]);
    }

    /**
     * Ensure a value or object will remain globally unique
     * @param  string  $key   The value or object name
     * @param  Closure        The closure that defines the object
     * @return mixed
     */
    public function singleton($key, $value)
    {
        $this->registry[$key] = function ($c) use ($value) {
            static $object;

            if (null === $object) {
                $object = $value($c);
            }

            return $object;
        };
    }
}
