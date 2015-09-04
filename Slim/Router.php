<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use InvalidArgumentException;
use RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\RouteParser\Std as StdParser;
use FastRoute\DataGenerator;
use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedGenerator;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use Slim\Interfaces\RouterInterface;
use Slim\Interfaces\RouteInterface;

/**
 * Router
 *
 * This class organizes Slim application route objects. It is responsible
 * for registering route objects, assigning names to route objects,
 * finding routes that match the current HTTP request, and creating
 * URLs for a named route.
 */
class Router extends RouteCollector implements RouterInterface
{
    /**
     * Parser
     *
     * @var \FastRoute\RouteParser
     */
    private $routeParser;

    /**
     * Base path used in pathFor()
     *
     * @var string
     */
    protected $basePath = '';

    /**
     * Routes
     *
     * @var Route[]
     */
    protected $routes = [];

    /**
     * Named routes
     *
     * @var null|Route[]
     */
    protected $namedRoutes;

    /**
     * Route groups
     *
     * @var RouteGroup[]
     */
    protected $routeGroups = [];

    private $finalized = false;

    /**
     * Create new router
     *
     * @param RouteParser   $parser
     * @param DataGenerator $generator
     */
    public function __construct(RouteParser $parser = null, DataGenerator $generator = null)
    {
        $parser = $parser ? $parser : new StdParser;
        $generator = $generator ? $generator : new GroupCountBasedGenerator;
        parent::__construct($parser, $generator);
        $this->routeParser = $parser;
    }

    /**
     * Set the base path used in pathFor()
     *
     * @param string $basePath
     *
     * @return self
     */
    public function setBasePath($basePath)
    {
        if (!is_string($basePath)) {
            throw new InvalidArgumentException('Router basePath must be a string');
        }

        $this->basePath = $basePath;

        return $this;
    }

    /**
     * Add route
     *
     * @param  string[] $methods Array of HTTP methods
     * @param  string   $pattern The route pattern
     * @param  callable $handler The route callable
     *
     * @return RouteInterface
     *
     * @throws InvalidArgumentException if the route pattern isn't a string
     */
    public function map($methods, $pattern, $handler)
    {
        if (!is_string($pattern)) {
            throw new InvalidArgumentException('Route pattern must be a string');
        }

        // Prepend parent group pattern(s)
        if ($this->routeGroups) {
            // If any route in the group only has / we remove it
            if ($pattern === '/') {
                $pattern = '';
            }
            $pattern = $this->processGroups() . $pattern;
        }

        // Add route
        $route = new Route($methods, $pattern, $handler, $this->routeGroups);
        $this->routes[] = $route;

        return $route;
    }

    /**
     * Finalize registered routes in preparation for dispatching
     *
     * NOTE: The routes can only be finalized once.
     */
    public function finalize()
    {
        if (!$this->finalized) {
            foreach ($this->getRoutes() as $route) {
                $route->finalize();
                $this->addRoute($route->getMethods(), $route->getPattern(), [$route, 'run']);
            }
            $this->finalized = true;
        }
    }

    /**
     * Dispatch router for HTTP request
     *
     * @param  ServerRequestInterface $request The current HTTP request object
     *
     * @return array
     *
     * @link   https://github.com/nikic/FastRoute/blob/master/src/Dispatcher.php
     */
    public function dispatch(ServerRequestInterface $request)
    {
        $this->finalize();

        $dispatcher = new GroupCountBasedDispatcher($this->getData());
        $uri = '/' . ltrim($request->getUri()->getPath(), '/');
        
        return $dispatcher->dispatch($request->getMethod(), $uri);
    }

    /**
     * Get route objects
     *
     * @return Route[]
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Get named route object
     *
     * @param string $name        Route name
     *
     * @return Route
     *
     * @throws RuntimeException   If named route does not exist
     */
    public function getNamedRoute($name)
    {
        if (is_null($this->namedRoutes)) {
            $this->buildNameIndex();
        }
        if (!isset($this->namedRoutes[$name])) {
            throw new RuntimeException('Named route does not exist for name: ' . $name);
        }
        return $this->namedRoutes[$name];
    }

    /**
     * Process route groups
     *
     * @return string A group pattern to prefix routes with
     */
    protected function processGroups()
    {
        $pattern = "";
        foreach ($this->routeGroups as $group) {
            $pattern .= $group->getPattern();
        }
        return $pattern;
    }

    /**
     * Add a route group to the array
     *
     * @param string   $pattern
     * @param callable $callable
     *
     * @return RouteGroup
     */
    public function pushGroup($pattern, $callable)
    {
        $group = new RouteGroup($pattern, $callable);
        array_push($this->routeGroups, $group);
        return $group;
    }

    /**
     * Removes the last route group from the array
     *
     * @return RouteGroup|bool The RouteGroup if successful, else False
     */
    public function popGroup()
    {
        $group = array_pop($this->routeGroups);
        return $group instanceof RouteGroup ? $group : false;
    }

    /**
     * Build the path for a named route
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws RuntimeException         If named route does not exist
     * @throws InvalidArgumentException If required data not provided
     */
    public function pathFor($name, array $data = [], array $queryParams = [])
    {
        $route = $this->getNamedRoute($name);
        $pattern = $route->getPattern();

        $routeDatas = $this->routeParser->parse($pattern);
        // $routeDatas is an array of all possible routes that can be made. There is
        // one routedata for each optional parameter plus one for no optional parameters.
        //
        // The most specific is last, so we look for that first.
        $routeDatas = array_reverse($routeDatas);

        $segments = [];
        foreach ($routeDatas as $routeData) {
            foreach ($routeData as $item) {
                if (is_string($item)) {
                    // this segment is a static string
                    $segments[] = $item;
                    continue;
                }

                // This segment has a parameter: first element is the name
                if (!array_key_exists($item[0], $data)) {
                    // we don't have a data element for this segment: cancel
                    // testing this routeData item, so that we can try a less
                    // specific routeData item.
                    $segments = [];
                    $segmentName = $item[0];
                    break;
                }
                $segments[] = $data[$item[0]];
            }
            if (!empty($segments)) {
                // we found all the parameters for this route data, no need to check
                // less specific ones
                break;
            }
        }

        if (empty($segments)) {
            throw new InvalidArgumentException('Missing data for URL segment: ' . $segmentName);
        }
        $url = implode('', $segments);

        if ($this->basePath) {
            $url = $this->basePath . $url;
        }

        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }

    /**
     * Build the path for a named route.
     *
     * This method is deprecated. Use pathFor() from now on.
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws RuntimeException         If named route does not exist
     * @throws InvalidArgumentException If required data not provided
     */
    public function urlFor($name, array $data = [], array $queryParams = [])
    {
        trigger_error('urlFor() is deprecated. Use pathFor() instead.', E_USER_DEPRECATED);
        return $this->pathFor($name, $data, $queryParams);
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
