<?php

namespace Slim;

trait Middlewared {

    /**
     * @var Middleware
     */
    protected $topLevel;


    /**
     * Add middleware
     *
     * This method prepends new middleware to the application middleware stack.
     *
     * @param callable $newMiddleware
     */
    public function addMiddleware(callable $newMiddleware)
    {
        if(!isset($this->topLevel)) {
            $this->topLevel = $this;
        }
        $this->topLevel = new Middleware($newMiddleware, $this->topLevel);
    }

    /**
     * @param array $newMiddlewares
     */
    public function addMiddlewares(array $newMiddlewares)
    {
        foreach($newMiddlewares as $middleware) {
            $this->addMiddleware($middleware);
        }
    }

    /**
     * @return Middleware
     */
    public function getTopLevelMiddleware()
    {
        return $this->topLevel;
    }

    /**
     * alias for addMiddleware or addMiddlewares
     * @param callable|array $newMiddleware
     */
    public function add(callable $newMiddleware)
    {
        $this->addMiddleware($newMiddleware);
    }



    public function __invoke()
    {
        throw new \Exception();
    }

    public function execMiddlewareStack($req, $resp)
    {
        $topLevel = $this->topLevel;
        return $topLevel($req, $resp);
    }

}