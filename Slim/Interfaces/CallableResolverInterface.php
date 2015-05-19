<?php


namespace Slim\Interfaces;

interface CallableResolverInterface
{
    public function setToResolve($toResolve);
    public function __invoke();
}