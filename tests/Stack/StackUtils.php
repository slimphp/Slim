<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Stack;

use ReflectionMethod;
use ReflectionProperty;
use Slim\Stack\Stack;

class StackUtils
{
    public static $stackName = 'stack';

    /**
    * Returns the accessible stack instance
    *
    * @param mixed $instance The stack or parent instance
    * @return Stack The accessible stack instance
    */
    public static function getStackInstance($instance)
    {
        if ($instance instanceof Stack === false) {
            $prop = new ReflectionProperty($instance, static::$stackName);
            $prop->setAccessible(true);
            $stack = $prop->getValue($instance);
        } else {
            $stack = $instance;
        }

        return $stack;
    }

    /**
    * Returns the internal stack queue array
    *
    * @param mixed $instance The stack or parent instance
    * @return callable[] The stack queue
    */
    public static function getQueue($instance)
    {
        return static::getProperty($instance, 'queue');
    }

    /**
    * Returns the last item in the internal queue
    *
    * @param mixed $instance The stack or parent instance
    * @return callable|null The last item
    */
    public static function getBottom($instance)
    {
        $queue = static::getQueue($instance);

        return array_pop($queue);
    }

    /**
    * Returns the internal resolvers array
    *
    * @param mixed $instance The stack or parent instance
    * @return callable[] The resolvers array
    */
    public static function getResolvers($instance)
    {
        return static::getProperty($instance, 'resolvers');
    }

    /**
    * Returns an invokable stack method
    *
    * @param mixed $instance The stack or parent instance
    * @param string $name The method name
    * @return ReflectionMethod The invokable method
    */
    public static function getMethod($instance, $name)
    {
        $stack = static::getStackInstance($instance);
        $method = new ReflectionMethod($stack, $name);
        $method->setAccessible(true);

        return $method;
    }

    /**
    * Returns a stack property value
    *
    * @param mixed $instance The stack or parent instance
    * @param mixed $name The property name
    * @return mixed The property value
    */
    public static function getProperty($instance, $name)
    {
        $stack = static::getStackInstance($instance);
        $prop = new ReflectionProperty($stack, $name);
        $prop->setAccessible(true);

        return $prop->getValue($stack);
    }
}
