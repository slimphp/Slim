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
use Slim\Interfaces\RouterInterface;

/**
 * Router
 *
 * This class organizes Slim application route objects. It is responsible
 * for registering route objects, assigning names to route objects,
 * finding routes that match the current HTTP request, and creating
 * URLs for a named route.
 */
class Router extends \FastRoute\RouteCollector implements RouterInterface
{
    /**
     * Routes
     */
    protected $routes = [];

    /**
     * Named routes
     *
     * @var null|array
     */
    protected $namedRoutes;

    /**
     * Route groups
     *
     * @var array
     */
    protected $routeGroups = [];

    /**
     * Create new router
     *
     * @param \FastRoute\RouteParser   $parser
     * @param \FastRoute\DataGenerator $generator
     */
    public function __construct(\FastRoute\RouteParser $parser = null, \FastRoute\DataGenerator $generator = null)
    {
        $parser = $parser ? $parser : new \FastRoute\RouteParser\Std;
        $generator = $generator ? $generator : new \FastRoute\DataGenerator\GroupCountBased;
        parent::__construct($parser, $generator);
    }

    /**
     * Add route
     *
     * @param  string[] $methods Array of HTTP methods
     * @param  string   $pattern The route pattern
     * @param  callable $handler The route callable
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function map($methods, $pattern, $handler)
    {
        // Prepend group pattern
        list($groupPattern, $groupMiddleware) = $this->processGroups();
        $pattern = $groupPattern . $pattern;

        // Add route
        $route = new Route($methods, $pattern, $handler);
        foreach ($groupMiddleware as $middleware) {
            $route->add($middleware);
        }
        $this->addRoute($methods, $pattern, [$route, 'run']);
        $this->routes[] = $route;

        return $route;
    }

    /**
     * Dispatch router for HTTP request
     *
     * @param  RequestInterface $request The current HTTP request object
     *
     * @return array
     * @link   https://github.com/nikic/FastRoute/blob/master/src/Dispatcher.php
     */
    public function dispatch(RequestInterface $request)
    {
        $dispatcher = new \FastRoute\Dispatcher\GroupCountBased($this->getData());

        return $dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath()
        );
    }

    /**
     * Process route groups
     *
     * @return array An array with two elements: pattern, middlewareArr
     */
    protected function processGroups()
    {
        $pattern = "";
        $middleware = [];
        foreach ($this->routeGroups as $group) {
            $k = key($group);
            $pattern .= $k;
            if (is_array($group[$k])) {
                $middleware = array_merge($middleware, $group[$k]);
            }
        }
        return [$pattern, $middleware];
    }

    /**
     * Add a route group to the array
     *
     * @param string     $group      The group pattern prefix
     * @param array|null $middleware Optional middleware
     *
     * @return int The index of the new group
     */
    public function pushGroup($group, $middleware = [])
    {
        return array_push($this->routeGroups, [$group => $middleware]);
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
     * @throws \RuntimeException         If named route does not exist
     * @throws \InvalidArgumentException If required data not provided
     */
    public function urlFor($name, $data = [])
    {
        if (is_null($this->namedRoutes)) {
            $this->buildNameIndex();
        }
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException('Named route does not exist for name: ' . $name);
        }
        $route = $this->namedRoutes[$name];
        $pattern = $route->getPattern();

        return preg_replace_callback('/{([^}]+)}/', function ($match) use ($data) {
            $segmentName = explode(':', $match[1])[0];
            if (!isset($data[$segmentName])) {
                throw new \InvalidArgumentException('Missing data for URL segment: ' . $segmentName);
            }

            return $data[$segmentName];
        }, $pattern);
    }

    /**
     * Build index of named routes
     */
    protected function buildNameIndex()
    {
        $this->namedRoutes = [];
        foreach ($this->routes as $route) {
            $name = $route->getName();
            if ($name) {
                $this->namedRoutes[$name] = $route;
            }
        }
    }
}
