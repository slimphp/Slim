<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\AdvancedCallableResolverInterface;

class DeferredCallable
{
    /**
     * @var callable|string
     */
    protected $callable;

    /**
     * @var CallableResolverInterface|null
     */
    protected $callableResolver;

    /**
     * @param callable|string                $callable
     * @param CallableResolverInterface|null $resolver
     */
    public function __construct($callable, ?CallableResolverInterface $resolver = null)
    {
        $this->callable = $callable;
        $this->callableResolver = $resolver;
    }

    public function __invoke(...$args)
    {
        $callable = $this->callable;
        if ($this->callableResolver instanceof AdvancedCallableResolverInterface) {
            $callable = $this->callableResolver->resolveMiddleware($callable);
            return $callable(...$args);
        }
        if ($this->callableResolver instanceof CallableResolverInterface) {
            $callable = $this->callableResolver->resolve($callable);
            return $callable(...$args);
        }
        if (is_callable($callable)) {
            return $callable(...$args);
        }
        throw new \RuntimeException('Need to come up with a name');
    }
}
