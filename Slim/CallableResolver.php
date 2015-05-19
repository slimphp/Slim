<?php

namespace Slim;

use Interop\Container\ContainerInterface;

final class CallableResolver
{
    
    protected $container;
    
    protected $toResolve;
    
    protected $resolved;
    
    public function __construct(Container $container, $toResolve = null)
    {
        $this->toResolve = $toResolve;
        $this->container = $container;
    }
    
    public function setToResolve($toResolve)
    {
        $this->toResolve = $toResolve;
    }
    
    private function resolve()
    {
        // check it's callable
        if (is_callable($this->toResolve)) {
            $this->resolved = $this->toResolve;
            
        // check for slim callable as "class:method"
        } elseif (is_string($this->toResolve)) {
            $callable_pattern = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';
            if (preg_match($callable_pattern, $this->toResolve, $matches)) {
                $class = $matches[1];
                $method = $matches[2];
                
                if ($this->container->has($class)) {
                    $this->resolved = [$this->container->get($class), $method];
                } else {
                    if (!class_exists($class)) {
                        throw new \RuntimeException(sprintf('Callable %s does not exist', $class));
                    }
                    $this->resolved = [new $class, $method];
                }
                if (!is_callable($this->resolved)) {
                    throw new \RuntimeException(sprintf('%s is not resolvable', $this->toResolve));
                }
            } else {
                throw new \RuntimeException(sprintf('%s is not resolvable', $this->toResolve));
            }
        }
    }
    
    public function __invoke()
    {
        if (!isset($this->resolved)) {
            $this->resolve();
        }
        return call_user_func_array($this->resolved, func_get_args());
    }
}
