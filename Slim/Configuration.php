<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use Slim\Interfaces\ConfigurationInterface;
use Slim\Interfaces\ConfigurationHandlerInterface;

/**
 * Configuration
 *
 * This class uses a ConfigurationHandler class to parse
 * configuration data, accessed as an array.
 */
class Configuration implements ConfigurationInterface, \IteratorAggregate
{
    /**
     * Handler for Configuration values
     *
     * @var mixed
     */
    protected $handler;

    /**
     * Storage array of values
     *
     * @var array
     */
    protected $values = [];

    /**
     * Default values
     *
     * @var array
     */
    protected $defaults = [
        // Cookies
        'cookies.lifetime' => '20 minutes',
        'cookies.path' => '/',
        'cookies.domain' => null,
        'cookies.secure' => false,
        'cookies.httponly' => false,
        // HTTP
        'http.version' => '1.1',
        'http.trusted_proxies' => [],
        'http.trusted_headers' => [],
    ];

    /**
     * Create new configuration
     *
     * @param ConfigurationHandlerInterface $handler
     */
    public function __construct(ConfigurationHandlerInterface $handler)
    {
        $this->handler = $handler;
        $this->setDefaults();
    }

    /**
     * Set Slim's defaults using the handler
     *
     * @param  array $values
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
     *
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Call a method from the handler
     *
     * @param  string $method
     * @param  array $params
     * @return mixed
     */
    public function callHandlerMethod($method, array $params = [])
    {
        return call_user_func_array([$this->handler, $method], $params);
    }

    /**
     * Get a value
     *
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->handler[$key];
    }

    /**
     * Set a value
     *
     * @param string $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->handler[$key] = $value;
    }

    /**
     * Check a value exists
     *
     * @param  string $key
     * @return boolean
     */
    public function offsetExists($key)
    {
        return isset($this->handler[$key]);
    }

    /**
     * Remove a value
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        unset($this->handler[$key]);
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
