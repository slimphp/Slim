<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Interfaces;

/**
 * Resolves a callable.
 *
 * @package Slim
 * @since 3.0.0
 */
interface CallableResolverInterface
{
    /**
     * Invoke the resolved callable.
     *
     * @param mixed $toResolve
     *
     * @return callable
     */
    public function resolve($toResolve);
}
