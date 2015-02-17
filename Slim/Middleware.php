<?php
namespace Slim;

class Middleware
{
    /**
     * @var callable
     */
    protected $callable;

    /**
     * @var callable
     */
    protected $next;

    /**
     * Constructor
     *
     * @param callable $callable
     * @param callable $next
     */
    public function __construct(callable $callable, callable $next)
    {
        $this->callable = $callable;
        $this->next = $next;
    }

    /**
     * Invoke middleware
     *
     * @param \Slim\Interfaces\Http\RequestInterface $req
     * @param \Psr\Http\Message\ResponseInterface $res
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($req, $res)
    {
        return call_user_func_array($this->callable, [$req, $res, $this->next]);
    }
}
