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
 * Settings
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.6.7
 */
class Settings implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @var array
     */
    protected $container = array();

    /**
     * Constructor merges users settings with the default settings
     *
     * @param array $userSettings Key-Value array of application settings
     * @return void
     */
    public function __construct($userSettings = array())
    {
        $this->container = array_merge($this->getDefaultSettings(), $userSettings);
    }

    /**
     * Get default application settings
     * @return array
     */
    public function getDefaultSettings()
    {
        return array(
            //Application
            'mode' => 'development',
            //Debugging
            'debug' => true,
            //Logging
            'log.writer' => null,
            'log.level' => \Slim\Log::DEBUG,
            'log.enabled' => true,
            //View
            'templates.path' => './templates',
            'view' => '\Slim\View',
            //Cookies
            'cookies.lifetime' => '20 minutes',
            'cookies.path' => '/',
            'cookies.domain' => null,
            'cookies.secure' => false,
            'cookies.httponly' => false,
            //Encryption
            'cookies.secret_key' => 'CHANGE_ME',
            'cookies.cipher' => MCRYPT_RIJNDAEL_256,
            'cookies.cipher_mode' => MCRYPT_MODE_CBC,
            //HTTP
            'http.version' => '1.1'
        );
    }

    /**
     * Set a settings attribute
     *
     * @param string $name the name of the setting to set or retrieve.
     * @param mixed $value If name is a string, the value of the setting identified by $name
     * @return mixed
     */
    public function set($name, $value = null) {
        if (func_num_args() === 1) {
            if (is_array($name)) {
                $this->container = array_merge($this->container, $name);
            }
        } else {
            if(is_array($value)) {
                throw new \InvalidArgumentException('Arrays are not allowed here.');
            }
            $this->container[$name] = $value;
        }
    }

    /**
     * Get a settings attribute value
     *
     * @param string $name
     * @return mixed
     */
    public function get($name) {
        return isset($this->container[$name]) ? $this->container[$name] : null;
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
        $this->set($offset, $value);
    }

    /**
     * Unset a parameter
     *
     * @param string $offset
     */
    public function offsetUnset( $offset )
    {
        unset($this->container[$offset]);
    }

    /**
     * Get a parameter
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet( $offset )
    {
        return $this->get($offset);
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
