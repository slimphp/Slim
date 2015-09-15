<?php

namespace Slim\Interfaces;

interface ServiceConfigInterface
{
    /**
     * This service MUST return a SHARED instance
     * of \Slim\Interfaces\RouterInterface.
     *
     * @return \Slim\Interfaces\RouterInterface.
     */
    public function getRouter();

    /**
     * This service MUST return a shared instance
     * of \Slim\Interfaces\Http\EnvironmentInterface.
     *
     * @return \Slim\Interfaces\Http\EnvironmentInterface
     */
    public function getEnvironment();

    /**
     * This service MUST return a SHARED instance
     * of \Slim\Interfaces\InvocationStrategyInterface.
     *
     * @return \Slim\Interfaces\InvocationStrategyInterface
     */
    public function getFoundHandler();

    /**
     * @return \Slim\Interfaces\Http\EnvironmentInterface
     */
    public function newEnvironment();

    /**
     * PSR-7 Request object
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function newRequest();

    /**
     * PSR-7 Request object
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function newResponse();

    /**
     * @return \Slim\Interfaces\RouterInterface
     */
    public function newRouter();

    /**
     * @return \Slim\Interfaces\InvocationStrategyInterface
     */
    public function newFoundHandler();

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
    public function newErrorHandler();

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
    public function newNotFoundHandler();

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
    public function newNotAllowedHandler();

    /**
     * @return \Slim\Interfaces\CallableResolverInterface
     */
    public function newCallableResolver();
}
