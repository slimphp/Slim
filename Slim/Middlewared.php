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
        $this->assumeIsBooted();
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
    public function add($newMiddleware)
    {
        if(is_array($newMiddleware)) {
            $this->addMiddlewares($newMiddleware);
        } else {
            $this->addMiddleware($newMiddleware);
        }
    }


    /**
     * check if the middleware chain is set
     * if not put himself in it center
     */
    private function assumeIsBooted()
    {
        if(!isset($this->topLevel)) {
            $this->topLevel = $this;
        }
    }

    public function __invoke()
    {
        throw new \Exception();
    }

    public function execMiddlewareStack($req, $res)
    {
        $this->assumeIsBooted();
        return call_user_func_array($this->topLevel, [$req, $res]);
    }

}