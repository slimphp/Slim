<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim;

class DispatcherResults
{
    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var string
     */
    protected $httpMethod;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var int
     * The following statuses are constants from Slim\Dispatcher
     * Slim\Dispatcher extends FastRoute\Dispatcher\GroupCountBased
     * Which implements the interface FastRoute\Dispatcher where the original constants are located
     * Slim\Dispatcher::NOT_FOUND = 0
     * Slim\Dispatcher::FOUND = 1
     * Slim\Dispatcher::NOT_ALLOWED = 2
     */
    protected $routeStatus;

    /**
     * @var callable|null
     */
    protected $routeHandler;

    /**
     * @var array
     */
    protected $routeArguments;

    /**
     * DispatcherResults constructor.
     * @param Dispatcher $dispatcher
     * @param $httpMethod
     * @param $uri
     * @param int $routeStatus
     * @param callable|null $routeHandler
     * @param array $routeArguments
     */
    public function __construct(
        Dispatcher $dispatcher,
        $httpMethod,
        $uri,
        $routeStatus,
        $routeHandler = null,
        $routeArguments = []
    ) {
        $this->dispatcher = $dispatcher;
        $this->httpMethod = $httpMethod;
        $this->uri = $uri;
        $this->routeStatus = $routeStatus;
        $this->routeHandler = $routeHandler;
        $this->routeArguments = $routeArguments;
    }

    /**
     * @return mixed
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @return string
     */
    public function getHttpMethod()
    {
        return $this->httpMethod;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return int
     */
    public function getRouteStatus()
    {
        return $this->routeStatus;
    }

    /**
     * @return callable|null
     */
    public function getRouteHandler()
    {
        return $this->routeHandler;
    }

    /**
     * @param bool $urlDecode
     * @return array
     */
    public function getRouteArguments($urlDecode = true)
    {
        if (!$urlDecode) {
            return $this->routeArguments;
        }

        $routeArguments = [];
        foreach ($this->routeArguments as $key => $value) {
            $routeArguments[$key] = urldecode($value);
        }

        return $routeArguments;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return $this->dispatcher->getAllowedMethods($this->httpMethod, $this->uri);
    }
}
