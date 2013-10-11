<?php

namespace Slim\Interfaces;

interface MiddlewareInterface
{
    public function setApplication();

    public function getApplication();

    public function setNextMiddleware($middleware);

    public function getNextMiddleware();

    public function call();
}
