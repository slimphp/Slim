<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Mocks;

use Psr\Container\ContainerInterface;
use ArrayAccess;

/**
 * Class MockContainer
 * @package Slim\Tests\Mocks
 */
class MockContainer implements ArrayAccess, ContainerInterface
{
    /**
     * @var array
     */
    private $refs = [];

    /**
     * @param string $id
     * @return mixed
     * @throws MockContainerNotFoundException
     */
    public function get($id)
    {
        if (isset($this->refs[$id])) {
            return $this->refs[$id];
        }

        throw new MockContainerNotFoundException("Reference to {$id} not found in container.");
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return isset($this->refs[$id]);
    }

    /**
     * @param $name
     * @return mixed
     * @throws MockContainerNotFoundException
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param $name
     * @param $value
     * @return self
     */
    public function __set($name, $value)
    {
        $this->refs[$name] = $value;
        return $this;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @param mixed $offset
     * @return mixed
     * @throws MockContainerNotFoundException
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return self
     */
    public function offsetSet($offset, $value)
    {
        $this->refs[$offset] = $value;
        return $this;
    }

    /**
     * @param mixed $offset
     * @return self
     */
    public function offsetUnset($offset)
    {
        unset($this->refs[$offset]);
        return $this;
    }
}
