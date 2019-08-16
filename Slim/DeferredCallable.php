<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use RuntimeException;
use Slim\Interfaces\CallableResolverInterface;

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
        if ($resolver === null && is_string($callable) && preg_match(CallableResolver::$callablePattern, $callable)) {
            throw new RuntimeException(sprintf(
                'Slim callable notation %s is not allowed without callable resolver.',
                $callable
            ));
        }

        $this->callable = $callable;
        $this->callableResolver = $resolver;
    }

    public function __invoke(...$args)
    {
        /** @var callable $callable */
        $callable = $this->callable;
        if ($this->callableResolver) {
            $callable = $this->callableResolver->resolve($callable);
        }

        return $callable(...$args);
    }
}
