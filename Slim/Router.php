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
     * @var array
     */
    protected $routeGroups = [];

    /**
     * Create new router
     *
     * @param \FastRoute\RouteParser   $parser
     * @param \FastRoute\DataGenerator $generator
     */
    public function __construct(RouteParser $parser = null, DataGenerator $generator = null)
    {
        $parser = $parser ? $parser : new StdParser;
        $generator = $generator ? $generator : new GroupCountBasedGenerator;
        parent::__construct($parser, $generator);
        $this->routeParser = $parser;
    }

    /**
     * Add route
     *
     * @param  string[] $methods Array of HTTP methods
     * @param  string   $pattern The route pattern
     * @param  callable $handler The route callable
     *
     * @return \Slim\Interfaces\RouteInterface
     * @throws InvalidArgumentException if the route pattern isn't a string
     */
    public function map($methods, $pattern, $handler)
    {

        if (!is_string($pattern)) {
            throw new InvalidArgumentException('Route pattern must be a string');
        }

        // Prepend group pattern
        $groupMiddleware = [];
        if ($this->routeGroups) {
            list($groupPattern, $groupMiddleware) = $this->processGroups();
            $pattern = $groupPattern . $pattern;
        }

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
     * @param  ServerRequestInterface $request The current HTTP request object
     *
     * @return array
     * @link   https://github.com/nikic/FastRoute/blob/master/src/Dispatcher.php
     */
    public function dispatch(ServerRequestInterface $request)
    {
        $dispatcher = new GroupCountBasedDispatcher($this->getData());

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
     * @param  string $name        Route name
     * @param  array  $data        Route URI segments replacement data
     * @param  array  $queryParams Optional query string parameters
     *
     * @return string
     * @throws \RuntimeException         If named route does not exist
     * @throws \InvalidArgumentException If required data not provided
     */
    public function urlFor($name, array $data = [], array $queryParams = [])
    {
        if (is_null($this->namedRoutes)) {
            $this->buildNameIndex();
        }
        if (!isset($this->namedRoutes[$name])) {
            throw new RuntimeException('Named route does not exist for name: ' . $name);
        }
        $route = $this->namedRoutes[$name];
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

        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
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
