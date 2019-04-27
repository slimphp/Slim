<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim;

use Closure;
use Slim\Interfaces\RouteGroupInterface;

class RouteGroup extends Routable implements RouteGroupInterface
{
    /**
     * @param string   $pattern  The pattern prefix for the group
     * @param callable $callable The group callable
     */
    public function __construct($pattern, $callable)
    {
        $this->pattern = $pattern;
        $this->callable = $callable;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(App $app = null)
    {
        $callable = $this->resolveCallable($this->callable);
        if ($callable instanceof Closure && $app !== null) {
            $callable = $callable->bindTo($app);
        }

        $callable($app);
    }
}
