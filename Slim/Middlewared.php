<?php

namespace Slim;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


/**
 * Middlewared
 *
 * This trait define a common behaviour for middlewares management
 * in App and Route
 */

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
        $this->assumeIsBooted();
        foreach($newMiddlewares as $middleware) {
            $this->addMiddleware($middleware);
        }
    }

    /**
     * @return Middleware
     */
    public function getTopLevelMiddleware()
    {
        $this->assumeIsBooted();
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


    /**
     * A middlewared Object is the kernel of an Middleware stack,
     * It should be callbable,
     * Middlewared::ExecMiddlewareStack will call it
     * It should take a request and a response and return a response
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    abstract public function __invoke(RequestInterface $request, ResponseInterface $response);


    protected function execMiddlewareStack($req, $res)
    {
        $this->assumeIsBooted();
        return call_user_func_array($this->topLevel, [$req, $res]);
    }

}