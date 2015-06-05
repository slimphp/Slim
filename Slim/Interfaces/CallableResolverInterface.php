<?php


namespace Slim\Interfaces;

interface CallableResolverInterface
{

    /**
     * Receive a string that is to be resolved to a callable
     *
     * @param  string $toResolve
     *
     * @return void
     */
    public function setToResolve($toResolve);

    /**
     * Invoke the resolved callable.
     *
     * @return \Psr\Http\MessageResponseInterface
     */
    public function __invoke();
}
