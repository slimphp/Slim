<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Interfaces;

use Psr\Http\Message\ResponseInterface;

/**
 * Callable Resolver Interface
 *
 * @package Slim
 * @since 3.0.0
 */
interface CallableResolverInterface
{
    /**
     * Receive a string that is to be resolved to a callable
     *
     * @param string $toResolve
     */
    public function setToResolve($toResolve);

    /**
     * Invoke the resolved callable.
     *
     * @return ResponseInterface
     */
    public function __invoke();
}
