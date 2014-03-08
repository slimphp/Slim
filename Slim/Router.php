<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.5
 * @package     Slim
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
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
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.0.0
 */
class Router implements RouterInterface
{
    /**
     * The current (most recently dispatched) route
     * @var \Slim\Route
     */
    protected $currentRoute;

    /**
     * All route objects, numerically indexed
     * @var array[\Slim\Interfaces\RouteInterface]
     */
    protected $routes;

    /**
     * Named route objects, indexed by route name
     * @var array[\Slim\Interfaces\RouteInterface]
     */
    protected $namedRoutes;

    /**
     * Route objects that match the request URI
     * @var array[\Slim\Interfaces\RouteInterface]
     */
    protected $matchedRoutes;

    /**
     * Route groups
     * @var array
     */
    protected $routeGroups;
    
    /**
     * @var string Current Base URI
     */
    protected $currentBaseUri = '';
    
    /**
     * @var array Current stack of middleware
     */
    protected $currentMiddleware;
    
    /**
     * Constructor
     * @api
     */
    public function __construct()
    {
        $this->routes = array();
        $this->routeGroups = array();
        $this->currentMiddleware = array();
    }

    /**
     * Get current route
     *
     * This method will return the current \Slim\Route object. If a route
     * has not been dispatched, but route matching has been completed, the
     * first matching \Slim\Route object will be returned. If route matching
     * has not completed, null will be returned.
     *
     * @return \Slim\Interfaces\RouteInterface|null
     * @api
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
     * @param  string             $httpMethod  The HTTP request method
     * @param  string             $resourceUri The resource URI
     * @param  bool               $reload      Should matching routes be re-parsed?
     * @return array[\Slim\Interfaces\RouteInterface]
     * @api
     */
    public function getMatchedRoutes($httpMethod, $resourceUri, $save = true)
    {
        $matchedRoutes = array();
        
        foreach ($this->routeGroups as $index => $grouproutes) {
            foreach ($grouproutes as $uri => $group) {
                
                $groupSlashes = substr_count($uri, '/');
                $resourceUriParts = explode('/', $resourceUri);
                $resourceMatchUri = implode('/', array_slice($resourceUriParts, 0, $groupSlashes+1));
                
                if ($uri == $resourceMatchUri) {
                    unset($this->routeGroups[$index]);
                    
                    $this->currentBaseUri = $uri;
                    $routes = $group->routes;
                    
                    
                    $this->currentMiddleware = array_merge(
                        $this->currentMiddleware, 
                        $group->middleware
                    );
                    
                    if (is_callable($routes)) {
                        $routes($this->currentMiddleware);
                    }
                    
                    return $this->getMatchedRoutes($httpMethod, $resourceUri, $reload);
                }
            }
        }
        
        foreach ($this->routes as $route) {
            if (!$route->supportsHttpMethod($httpMethod) && !$route->supportsHttpMethod("ANY")) {
                continue;
            }
            
            if ($route->matches($resourceUri)) {
                $matchedRoutes[] = $route;
            }
        }
        
        return $this->matchedRoutes;
    }

    /**
     * Add a route
     *
     * This method registers a \Slim\Interfaces\RouteInterface object with the router.
     *
     * @param  \Slim\Interfaces\RouteInterface $route The route object
     * @api
     */
    public function map(RouteInterface $route)
    {
        $route->setPattern($this->currentBaseUri . $route->getPattern());
        $this->routes[] = $route;

        foreach ($this->currentMiddleware as $middleware) {
            $route->setMiddleware($middleware);
        }
    }

    /**
     * Add a route group to the array
     * @param  string     $group      The group pattern (ie. "/books/:id")
     * @param  array|null $middleware Optional parameter array of middleware
     * @return int                    The index of the new group
     * @api
     */
    public function addGroup($group, $routes, $middleware = array())
    {
        return array_push(
            $this->routeGroups, 
            array($group => (object)array(
                'routes'        => $routes,
                'middleware'    => $middleware
            ))
        );
    }

    /**
     * Get URL for named route
     * @param  string            $name   The name of the route
     * @param  array             $params Associative array of URL parameter names and replacement values
     * @return string                    The URL for the given route populated with provided replacement values
     * @throws \RuntimeException         If named route not found
     * @api
     */
    public function urlFor($name, $params = array())
    {
        if (!$this->hasNamedRoute($name)) {
            throw new \RuntimeException('Named route not found for name: ' . $name);
        }
        $search = array();
        foreach ($params as $key => $value) {
            $search[] = '#:' . preg_quote($key, '#') . '\+?(?!\w)#';
        }
        $pattern = preg_replace($search, $params, $this->getNamedRoute($name)->getPattern());

        //Remove remnants of unpopulated, trailing optional pattern segments, escaped special characters
        return preg_replace('#\(/?:.+\)|\(|\)|\\\\#', '', $pattern);
    }

    /**
     * Add named route
     * @param  string                                $name   The route name
     * @param  \Slim\Interfaces\RouteInterface       $route  The route object
     * @throws \RuntimeException                             If a named route already exists with the same name
     * @api
     */
    public function addNamedRoute($name, RouteInterface $route)
    {
        if ($this->hasNamedRoute($name)) {
            throw new \RuntimeException('Named route already exists with name: ' . $name);
        }
        $this->namedRoutes[(string) $name] = $route;
    }

    /**
     * Has named route
     * @param  string $name The route name
     * @return bool
     * @api
     */
    public function hasNamedRoute($name)
    {
        $this->getNamedRoutes();

        return isset($this->namedRoutes[(string) $name]);
    }

    /**
     * Get named route
     * @param  string                               $name
     * @return \Slim\Interfaces\RouteInterface|null
     * @api
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
     * @return \ArrayIterator
     * @api
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
