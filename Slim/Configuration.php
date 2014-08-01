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

use \Slim\Interfaces\ConfigurationInterface;
use \Slim\Interfaces\ConfigurationHandlerInterface;

/**
 * Configuration
 * Uses a ConfigurationHandler class to parse configuration data, accessed as an array.
 *
 * @package    Slim
 * @author     John Porter
 * @since      3.0.0
 */
class Configuration implements ConfigurationInterface, \IteratorAggregate
{
    /**
     * Handler for Configuration values
     * @var mixed
     */
    protected $handler;
    /**
     * Storage array of values
     * @var array
     */
    protected $values = array();

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
        'session.handler' => null,
        'session.flash_key' => 'slimflash',
        'session.encrypt' => false,
        // HTTP
        'http.version' => '1.1',
        // Routing
        'routes.case_sensitive' => true,
        'routes.context' => null
    );

    /**
     * Constructor
     * @param mixed $handler
     */
    public function __construct(ConfigurationHandlerInterface $handler)
    {
        $this->handler = $handler;

        $this->setDefaults();
    }

    /**
     * Set Slim's defaults using the handler
     */
    public function setArray(array $values)
    {
        $this->handler->setArray($values);
    }

    /**
     * Set Slim's defaults using the handler
     */
    public function setDefaults()
    {
        $this->handler->setArray($this->defaults);
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
     * Call a method from the handler
     * @param  string $method
     * @param  array $params
     * @return mixed
     */
    public function callHandlerMethod($method, array $params = array())
    {
        return call_user_func_array(array($this->handler, $method), $params);
    }

    /**
     * Get a value
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->handler[$key];
    }

    /**
     * Set a value
     * @param  string $key
     * @param  mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->handler[$key] = $value;
    }

    /**
     * Check a value exists
     * @param  string $key
     * @return boolean
     */
    public function offsetExists($key)
    {
        return isset($this->handler[$key]);
    }

    /**
     * Remove a value
     * @param  string $key
     */
    public function offsetUnset($key)
    {
        unset($this->handler[$key]);
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
