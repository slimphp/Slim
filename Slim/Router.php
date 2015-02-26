<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use \Slim\Interfaces\RouterInterface;
use \Slim\Interfaces\RouteInterface;

/**
 * Router
 *
 * This class organizes Slim application route objects. It is responsible
 * for registering route objects, assigning names to route objects,
 * finding routes that match the current HTTP request, and creating
 * URLs for a named route.
 */
class Router implements RouterInterface
{
    /**
     * The current (most recently dispatched) route
     *
     * @var RouteInterface
     */
    protected $currentRoute;

    /**
     * All route objects, numerically indexed
     *
     * @var RouteInterface[]
     */
    protected $routes;

    /**
     * Named route objects, indexed by route name
     *
     * @var RouteInterface[]
     */
    protected $namedRoutes;

    /**
     * Route objects that match the request URI
     *
     * @var RouteInterface[]
     */
    protected $matchedRoutes;

    /**
     * Route groups
     *
     * @var array
     */
    protected $routeGroups;

    /**
     * Request base URL (script name)
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Create new Router
     */
    public function __construct()
    {
        $this->routes = array();
        $this->routeGroups = array();
    }

    /**
     * Set base URL
     *
     * @param string $url
     */
    public function setBaseUrl($url)
    {
        $this->basUrl = $url;
    }

    /**
     * Get current route
     *
     * This method will return the current \Slim\Route object. If a route
     * has not been dispatched, but route matching has been completed, the
     * first matching \Slim\Route object will be returned. If route matching
     * has not completed, null will be returned.
     *
     * @return RouteInterface|null
     */
    public function getCurrentRoute()
    {
        if ($this->currentRoute !== null) {
            return $this->currentRoute;
        }

        if (is_array($this->matchedRoutes) && count($this->matchedRoutes) > 0) {
            return $this->matchedRoutes[0];
        }

        return null;
    }

    /**
     * Get route objects that match a given HTTP method and URI
     *
     * This method is responsible for finding and returning all \Slim\Interfaces\RouteInterface
     * objects that match a given HTTP method and URI. Slim uses this method to
     * determine which \Slim\Interfaces\RouteInterface objects are candidates to be
     * dispatched for the current HTTP request.
     *
     * @param  string           $httpMethod  The HTTP request method
     * @param  string           $resourceUri The resource URI
     * @param  bool             $reload      Should matching routes be re-parsed?
     * @return RouteInterface[]
     */
    public function getMatchedRoutes($httpMethod, $resourceUri, $save = true)
    {
        $matchedRoutes = array();
        foreach ($this->routes as $route) {
            if (!$route->supportsHttpMethod($httpMethod) && !$route->supportsHttpMethod("ANY")) {
                continue;
            }

            if ($route->matches($resourceUri)) {
                $matchedRoutes[] = $route;
            }
        }

        if ($save === true) {
            $this->matchedRoutes = $matchedRoutes;
        }

        return $matchedRoutes;
    }

    /**
     * Register a route with the router
     *
     * @param RouteInterface $route The route object
     */
    public function map(RouteInterface $route)
    {
        list($groupPattern, $groupMiddleware) = $this->processGroups();
        $route->setPattern($groupPattern . $route->getPattern());
        $this->routes[] = $route;
        foreach ($groupMiddleware as $middleware) {
            $route->setMiddleware($middleware);
        }
    }

    /**
     * Process route groups
     *
     * @return array An array with the elements: pattern, middlewareArr
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
     * Get URL for named route
     *
     * @param  string            $name        The name of the route
     * @param  array             $params      Associative array of URL parameter names and replacement values
     * @param  array             $queryParams Associative array of query string parameters.
     * @return string                         The URL for the given route populated with provided replacement values
     * @throws \RuntimeException              If named route not found
     */
    public function urlFor($name, array $params = array(), array $queryParams = array())
    {
        if (!$this->hasNamedRoute($name)) {
            throw new \RuntimeException('Named route not found for name: ' . $name);
        }

        $url = $this->getNamedRoute($name)->getPattern();

        if ($params) {
            $search = array();
            foreach ($params as $key => $value) {
                $search[] = '#:' . preg_quote($key, '#') . '\+?(?!\w)#';
            }
            $url = preg_replace($search, $params, $url);
        }

        //Remove remnants of unpopulated, trailing optional pattern segments, escaped special characters
        $url = preg_replace('#\(/?:.+\)|\(|\)|\\\\#', '', $url);

        // Addon query string parameters
        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $this->baseUrl . $url;
    }

    /**
     * Add named route
     *
     * @param  string                          $name   The route name
     * @param  \Slim\Interfaces\RouteInterface $route  The route object
     * @throws \RuntimeException                       If a named route already exists with the same name
     */
    public function addNamedRoute($name, RouteInterface $route)
    {
        if ($this->hasNamedRoute($name)) {
            throw new \RuntimeException('Named route already exists with name: ' . $name);
        }
        $this->namedRoutes[(string) $name] = $route;
    }

    /**
     * Does this router have a given named route?
     *
     * @param  string $name The route name
     * @return bool
     */
    public function hasNamedRoute($name)
    {
        $this->getNamedRoutes();

        return isset($this->namedRoutes[(string) $name]);
    }

    /**
     * Get named route
     *
     * @param  string              $name
     * @return RouteInterface|null
     */
    public function getNamedRoute($name)
    {
        $this->getNamedRoutes();
        if ($this->hasNamedRoute($name)) {
            return $this->namedRoutes[(string) $name];
        }

        return null;
    }

    /**
     * Get external iterator for named routes
     *
     * @return \ArrayIterator
     */
    public function getNamedRoutes()
    {
        if (is_null($this->namedRoutes)) {
            $this->namedRoutes = array();
            foreach ($this->routes as $route) {
                if ($route->getName() !== null) {
                    $this->addNamedRoute($route->getName(), $route);
                }
            }
        }

        return new \ArrayIterator($this->namedRoutes);
    }
}
