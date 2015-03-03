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
        // $this->addRoute($methods, $pattern, $route);
        $this->addRoute($methods[0], $pattern, $route);

        return $route;
    }

    public function dispatch(RequestInterface $request, ResponseInterface $response)
    {
        $dispatcher = new \FastRoute\Dispatcher\GroupCountBased($this->getData());

        return $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
    }

    /**
     * Process route groups
     *
     * @return array An array with two elements: pattern, middlewareArr
     */
    protected function processGroups()
    {
        $pattern = "";
        $middleware = array();
        foreach ($this->routeGroups as $group) {
            $k = key($group);
            $pattern .= $k;
            if (is_array($group[$k])) {
                $middleware = array_merge($middleware, $group[$k]);
            }
        }
        return array($pattern, $middleware);
    }

    /**
     * Add a route group to the array
     *
     * @param  string     $group      The group pattern (ie. "/books/:id")
     * @param  array|null $middleware Optional parameter array of middleware
     * @return int                    The index of the new group
     */
    public function pushGroup($group, $middleware = array())
    {
        return array_push($this->routeGroups, array($group => $middleware));
    }

    /**
     * Removes the last route group from the array
     *
     * @return bool True if successful, else False
     */
    public function popGroup()
    {
        return (array_pop($this->routeGroups) !== null);
    }

    /**
     * Build URL for named route
     *
     * @param  string $routeName Route name
     * @param  array  $data      Route URI segments replacement data
     *
     * @return string
     * @throws \RuntimeException If named route does not exist
     * @throws \InvalidArgumentException If required data not provided
     */
    public function urlFor($name, $data = array())
    {
        if (!isset($this->routes[$name])) {
            throw new \RuntimeException('Named route does not exist for name: ' . $name);
        }
        $route = $this->routes[$name];
        $pattern = $route->getPattern();

        return preg_replace_callback('/{([^}]+)}/', function ($match) use ($data) {
            $segmentName = explode(':', $match[1])[0];
            if (!isset($data[$segmentName])) {
                throw new \InvalidArgumentException('Missing data for URL segment: ' . $segmentName);
            }

            return $data[$segmentName];
        }, $pattern);
    }
}
