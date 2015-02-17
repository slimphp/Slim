<?php

namespace Slim;

trait Middlewared {

    /**
     * @var callable
     */
    protected $topLevel;


    /**
     * Add middleware
     *
     * This method prepends new middleware to the application middleware stack.
     *
     * @param callable $newMiddleware
     */
    public function add(callable $newMiddleware)
    {
        if(!isset($this->topLevel)) {
            $this->topLevel = $this;
        }
        $this->topLevel = new Middleware($newMiddleware, $this->topLevel);
    }

    public function __invoke()
    {
        throw new \Exception();
    }

    public function run($req, $resp)
    {
        $topLevel = $this->topLevel;
        $topLevel($req, $resp);
    }

}