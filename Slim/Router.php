<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\RouteParser\Std as StdParser;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouterInterface;

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
     * Parser
     *
     * @var \FastRoute\RouteParser
     */
    protected $routeParser;

    /**
     * Callable resolver
     *
     * @var CallableResolverInterface|null
     */
    protected $callableResolver;

    /**
     * @var InvocationStrategyInterface|null
     */
    protected $routeInvocationStrategy;

    /**
     * Base path used in pathFor()
     *
     * @var string
     */
    protected $basePath = '';

    /**
     * Path to fast route cache file. Set to false to disable route caching
     *
     * @var string|False
     */
    protected $cacheFile = false;

    /**
     * Routes
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Route counter incrementer
     * @var int
     */
    protected $routeCounter = 0;

    /**
     * Route groups
     *
     * @var RouteGroup[]
     */
    protected $routeGroups = [];

    /**
     * @var Dispatcher|null
     */
    protected $dispatcher;

    /**
     * Create new router
     *
     * @param RouteParser   $parser
     */
    public function __construct(RouteParser $parser = null)
    {
        $this->routeParser = $parser ?: new StdParser;
    }

    /**
     * Set default route invocation strategy
     *
     * @param InvocationStrategyInterface $strategy
     */
    public function setDefaultInvocationStrategy(InvocationStrategyInterface $strategy)
    {
        $this->routeInvocationStrategy = $strategy;
    }

    /**
     * Get default route invocation strategy
     *
     * @return InvocationStrategyInterface|null
     */
    public function getDefaultInvocationStrategy()
    {
        return $this->routeInvocationStrategy;
    }

    /**
     * Set the base path used in pathFor()
     *
     * @param string $basePath
     *
     * @return self
     */
    public function setBasePath(string $basePath): self
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * Set path to fast route cache file. If this is false then route caching is disabled.
     *
     * @param string|bool $cacheFile
     *
     * @return self
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function setCacheFile($cacheFile)
    {
        if (!is_string($cacheFile) && $cacheFile !== false) {
            throw new InvalidArgumentException('Router cacheFile must be a string or false');
        }

        $this->cacheFile = $cacheFile;

        if ($cacheFile !== false && !is_writable(dirname($cacheFile))) {
            throw new RuntimeException('Router cacheFile directory must be writable');
        }


        return $this;
    }

    /**
     * Set callable resolver
     *
     * @param CallableResolverInterface $resolver
     */
    public function setCallableResolver(CallableResolverInterface $resolver)
    {
        $this->callableResolver = $resolver;
    }

    /**
     * Add route
     *
     * @param  string[] $methods Array of HTTP methods
     * @param  string   $pattern The route pattern
     * @param  callable|string $handler The route callable
     *
     * @return RouteInterface
     */
    public function map(array $methods, string $pattern, $handler): RouteInterface
    {
        // Prepend parent group pattern(s)
        if ($this->routeGroups) {
            $pattern = $this->processGroups() . $pattern;
        }

        /** @var Route $route */
        $route = $this->createRoute($methods, $pattern, $handler);
        $this->routes[$route->getIdentifier()] = $route;
        $this->routeCounter++;

        return $route;
    }

    /**
     * Dispatch router for HTTP request
     *
     * @param  ServerRequestInterface $request The current HTTP request object
     *
     * @return RoutingResults
     */
    public function dispatch(ServerRequestInterface $request): RoutingResults
    {
        $uri = '/' . ltrim(rawurldecode($request->getUri()->getPath()), '/');
        return $this->createDispatcher()->dispatch($request->getMethod(), $uri);
    }

    /**
     * Create a new Route object
     *
     * @param  string[] $methods Array of HTTP methods
     * @param  string   $pattern The route pattern
     * @param  callable|string $callable The route callable
     *
     * @return RouteInterface
     */
    protected function createRoute(array $methods, string $pattern, $callable): RouteInterface
    {
        $route = new Route($methods, $pattern, $callable, $this->routeGroups, $this->routeCounter);
        if ($this->callableResolver) {
            $route->setCallableResolver($this->callableResolver);
        }
        if ($this->routeInvocationStrategy) {
            $route->setInvocationStrategy($this->routeInvocationStrategy);
        }

        return $route;
    }

    /**
     * @return Dispatcher
     */
    protected function createDispatcher(): Dispatcher
    {
        if ($this->dispatcher) {
            return $this->dispatcher;
        }

        $routeDefinitionCallback = function (RouteCollector $r) {
            foreach ($this->getRoutes() as $route) {
                $r->addRoute($route->getMethods(), $route->getPattern(), $route->getIdentifier());
            }
        };

        if ($this->cacheFile) {
            /** @var Dispatcher $dispatcher */
            $dispatcher = \FastRoute\cachedDispatcher($routeDefinitionCallback, [
                'dispatcher' => Dispatcher::class,
                'routeParser' => $this->routeParser,
                'cacheFile' => $this->cacheFile,
            ]);
        } else {
            /** @var Dispatcher $dispatcher */
            $dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback, [
                'dispatcher' => Dispatcher::class,
                'routeParser' => $this->routeParser,
            ]);
        }

        $this->dispatcher = $dispatcher;
        return $this->dispatcher;
    }

    /**
     * @param Dispatcher $dispatcher
     */
    public function setDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Get route objects
     *
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get named route object
     *
     * @param string $name        Route name
     *
     * @return RouteInterface
     *
     * @throws RuntimeException   If named route does not exist
     */
    public function getNamedRoute(string $name): RouteInterface
    {
        foreach ($this->routes as $route) {
            if ($name == $route->getName()) {
                return $route;
            }
        }
        throw new RuntimeException('Named route does not exist for name: ' . $name);
    }

    /**
     * Remove named route
     *
     * @param string $name        Route name
     *
     * @throws RuntimeException   If named route does not exist
     */
    public function removeNamedRoute(string $name)
    {
        /** @var Route $route */
        $route = $this->getNamedRoute($name);
        unset($this->routes[$route->getIdentifier()]);
    }

    /**
     * Process route groups
     *
     * @return string A group pattern to prefix routes with
     */
    protected function processGroups(): string
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
     * @return RouteGroupInterface
     */
    public function pushGroup(string $pattern, $callable): RouteGroupInterface
    {
        $group = new RouteGroup($pattern, $callable);
        $this->routeGroups[] = $group;
        return $group;
    }

    /**
     * Removes the last route group from the array
     *
     * @return RouteGroup|null The last RouteGroup, if one exists
     */
    public function popGroup()
    {
        return array_pop($this->routeGroups);
    }

    /**
     * @param string $identifier
     * @return RouteInterface
     */
    public function lookupRoute(string $identifier): RouteInterface
    {
        if (!isset($this->routes[$identifier])) {
            throw new RuntimeException('Route not found, looks like your route cache is stale.');
        }
        return $this->routes[$identifier];
    }

    /**
     * Build the path for a named route excluding the base path
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
    public function relativePathFor(string $name, array $data = [], array $queryParams = []): string
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
        $segmentName = '';
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
     * Build the path for a named route including the base path
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
    public function pathFor(string $name, array $data = [], array $queryParams = []): string
    {
        $url = $this->relativePathFor($name, $data, $queryParams);

        if ($this->basePath) {
            $url = $this->basePath . $url;
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
    public function urlFor(string $name, array $data = [], array $queryParams = []): string
    {
        trigger_error('urlFor() is deprecated. Use pathFor() instead.', E_USER_DEPRECATED);
        return $this->pathFor($name, $data, $queryParams);
    }
}
