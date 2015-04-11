<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use Pimple\Container;

/**
 * ResolveCallable
 *
 * This is an internal class that enables resolution of 'class:method' strings
 * into a closure. This class is an implementation detail and is used only inside
 * of the Slim application.
 */
trait ResolveCallable
{
    /**
     * Container
     *
     * @var Container
     */
    protected $container;

    /**
     * Resolve a string of the format 'class:method' into a closure that the
     * router can dispatch.
     *
     * @param  string $callable
     *
     * @return Closure
     */
    protected function resolveCallable($callable)
    {
        if (is_string($callable) && preg_match('!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!', $callable, $matches)) {
            // $callable is a class:method string, so wrap it into a closure, retriving the class from Pimple if registered there

            if ((! $this instanceof Container) && (! $this->container instanceof Container)) {
                throw new \RuntimeException('Cannot resolve callable string');
            }

            $class = $matches[1];
            $method = $matches[2];
            $callable = function() use ($class, $method) {
                static $obj = null;
                if ($obj === null) {
                    if (isset($this[$class])) {
                        $obj = $this[$class];
                    } else {
                        if (!class_exists($class)) {
                            throw new \RuntimeException('Route callable class does not exist');
                        }
                        $obj = new $class;
                    }
                    if (!is_callable([$obj, $method])) {
                        throw new \RuntimeException('Route callable method does not exist');
                    }
                }
                return call_user_func_array(array($obj, $method), func_get_args());
            };
        }

        return $callable;
    }
}