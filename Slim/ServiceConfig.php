<?php

namespace Slim;

use Interop\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Handlers\Error;
use Slim\Handlers\NotAllowed;
use Slim\Handlers\NotFound;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\Http\EnvironmentInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RouterInterface;
use Slim\Interfaces\ServiceConfigInterface;

class ServiceConfig implements ServiceConfigInterface
{
    /**
     * @var EnvironmentInterface
     */
    private $environment;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var InvocationStrategyInterface
     */
    private $foundHandler;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Services constructor.
     * @param \Interop\Container\ContainerInterface $container
     * @param array $settings
     */
    public function __construct(ContainerInterface $container, array $settings = [])
    {
        $this->container = $container;
        $this->settings = $settings;
    }

    /**
     * This service MUST return a shared instance
     * of \Slim\Interfaces\Http\EnvironmentInterface.
     *
     * @return EnvironmentInterface
     */
    public function getEnvironment()
    {
        if (!$this->environment instanceof EnvironmentInterface) {
            $this->environment = $this->newEnvironment();
        }
        return $this->environment;
    }

    /**
     * @return EnvironmentInterface
     */
    public function newEnvironment()
    {
        return new Environment($_SERVER);
    }

    /**
     * PSR-7 Request object
     *
     * @return ServerRequestInterface
     */
    public function newRequest()
    {
        return Request::createFromEnvironment($this->newEnvironment());
    }

    /**
     * PSR-7 Response object
     *
     * @return ResponseInterface
     */
    public function newResponse()
    {
        $headers = new Headers(['Content-Type' => 'text/html']);
        $response = new Response(200, $headers);

        return $response->withProtocolVersion($this->settings['httpVersion']);
    }

    /**
     * This service MUST return a SHARED instance
     * of \Slim\Interfaces\RouterInterface.
     *
     * @return RouterInterface
     */
    public function getRouter()
    {
        if (!$this->router instanceof RouterInterface) {
            $this->router = $this->newRouter();
        }
        return $this->router;
    }

    /**
     * @return RouterInterface
     */
    public function newRouter()
    {
        return new Router();
    }

    /**
     * This service MUST return a SHARED instance
     * of \Slim\Interfaces\InvocationStrategyInterface.
     *
     * @return InvocationStrategyInterface
     */
    public function getFoundHandler()
    {
        if (!$this->foundHandler instanceof InvocationStrategyInterface) {
            $this->foundHandler = $this->newFoundHandler();
        }
        return $this->foundHandler;
    }

    /**
     * @return InvocationStrategyInterface
     */
    public function newFoundHandler()
    {
        return new RequestResponse();
    }

    /**
     * This service MUST return a callable
     * that accepts three arguments:
     *
     * 1. Instance of \Psr\Http\Message\ServerRequestInterface
     * 2. Instance of \Psr\Http\Message\ResponseInterface
     * 3. Instance of \Exception
     *
     * The callable MUST return an instance of
     * \Psr\Http\Message\ResponseInterface.
     *
     * @return callable
     */
    public function newErrorHandler()
    {
        return new Error();
    }

    /**
     * This service MUST return a callable
     * that accepts two arguments:
     *
     * 1. Instance of \Psr\Http\Message\ServerRequestInterface
     * 2. Instance of \Psr\Http\Message\ResponseInterface
     *
     * The callable MUST return an instance of
     * \Psr\Http\Message\ResponseInterface.
     *
     * @return callable
     */
    public function newNotFoundHandler()
    {
        return new NotFound();
    }

    /**
     * This service MUST return a callable
     * that accepts three arguments:
     *
     * 1. Instance of \Psr\Http\Message\ServerRequestInterface
     * 2. Instance of \Psr\Http\Message\ResponseInterface
     * 3. Array of allowed HTTP methods
     *
     * The callable MUST return an instance of
     * \Psr\Http\Message\ResponseInterface.
     *
     * @return callable
     */
    public function newNotAllowedHandler()
    {
        return new NotAllowed();
    }

    /**
     * Instance of \Slim\Interfaces\CallableResolverInterface
     *
     * @return CallableResolverInterface
     */
    public function newCallableResolver()
    {
        return new CallableResolver($this->container);
    }
}