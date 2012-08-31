<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.6.7
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
 * MiddlewareContainer
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.6.7
 */
class MiddlewareContainer implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @var array
     */
    protected $container;

    /**
     * @var app
     */
    protected $app;

    /**
     * Constructor
     * @param  Slim application
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->container = array($this->app);
    }

    /**
     * Add middleware
     *
     * This method prepends new middleware to the application middleware stack.
     * The argument must be an instance that subclasses Slim_Middleware.
     *
     * @param   Slim_Middleware
     * @return void
     */
    public function add(\Slim\Middleware $newMiddleware)
    {
        $newMiddleware->setApplication($this->app);
        $newMiddleware->setNextMiddleware($this->container[0]);
        array_unshift($this->container, $newMiddleware);
    }

    /***** ARRAY ACCESS *****/

    /**
     * Check if a parameter is set
     *
     * @param string $offset
     * @return boolean
     */
    public function offsetExists( $offset )
    {
        return array_key_exists($offset, $this->container);
    }

    /**
     * Set a parameter
     *
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet( $offset, $value )
    {
        if(!($value instanceOf \Slim\Middleware)) {
            throw new \InvalidArgumentException('Value must be instance of \Slim\Middleware');
        }
        $this->add($value);
    }

    /**
     * Unset a parameter
     *
     * @param string $offset
     */
    public function offsetUnset( $offset )
    {
    }

    /**
     * Get a parameter
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet( $offset )
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /***** ARRAY ITERATOR ****/

   /**
    * Return array iterator
    *
    * @return ArrayIterator
    */
    public function getIterator()
    {
        return new \ArrayIterator($this->container);
    }
}
