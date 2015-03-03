<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Router
 *
 * This class organizes Slim application route objects. It is responsible
 * for registering route objects, assigning names to route objects,
 * finding routes that match the current HTTP request, and creating
 * URLs for a named route.
 */
class Router extends \FastRoute\RouteCollector
{
    protected $routes = [];

    public function __construct()
    {
        parent::__construct(new \FastRoute\RouteParser\Std, new \FastRoute\DataGenerator\GroupCountBased);
    }

    public function map($name, $methods, $pattern, $handler)
    {
        $route = new Route($methods, $pattern, $handler);
        $this->routes[$name] = $route;
        foreach ($methods as $method) {
            parent::addRoute($method, $pattern, $route);
        }

        return $route;
    }

    public function dispatch(RequestInterface $request, ResponseInterface $response)
    {
        $dispatcher = new \FastRoute\Dispatcher\GroupCountBased($this->getData());

        return $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
    }

    // TODO: Re-implement urlFor()
}
