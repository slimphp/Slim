<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use FastRoute\RouteParser;
use FastRoute\RouteParser\Std as StdParser;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use RuntimeException;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;

/**
 * RouteCollector is used to collect routes and route groups
 * as well as generate paths and URLs relative to its environment
 */
class RouteCollector implements RouteCollectorInterface
{
    /**
     * Parser
     *
     * @var RouteParser
     */
    protected $routeParser;

    /**
     * @var CallableResolverInterface
     */
    protected $callableResolver;

    /**
     * @var ContainerInterface|null
     */
    protected $container;

    /**
     * @var InvocationStrategyInterface
     */
    protected $defaultInvocationStrategy;

    /**
     * Base path used in pathFor()
     *
     * @var string
     */
    protected $basePath = '';

    /**
     * Path to fast route cache file. Set to false to disable route caching
     *
     * @var string|null
     */
    protected $cacheFile;

    /**
     * Routes
     *
     * @var RouteInterface[]
     */
    protected $routes = [];

    /**
     * Route groups
     *
     * @var RouteGroup[]
     */
    protected $routeGroups = [];

    /**
     * Route counter incrementer
     *
     * @var int
     */
    protected $routeCounter = 0;

    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * Create new router
     *
     * @param ResponseFactoryInterface      $responseFactory
     * @param CallableResolverInterface     $callableResolver
     * @param ContainerInterface|null       $container
     * @param InvocationStrategyInterface   $defaultInvocationStrategy
     * @param RouteParser                   $parser
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        CallableResolverInterface $callableResolver,
        ContainerInterface $container = null,
        InvocationStrategyInterface $defaultInvocationStrategy = null,
        RouteParser $parser = null
    ) {
        $this->responseFactory = $responseFactory;
        $this->callableResolver = $callableResolver;
        $this->container = $container;
        $this->defaultInvocationStrategy = $defaultInvocationStrategy ?? new RequestResponse();
        $this->routeParser = $parser ?? new StdParser;
    }

    /**
     * @return CallableResolverInterface
     */
    public function getCallableResolver(): CallableResolverInterface
    {
        return $this->callableResolver;
    }

    /**
     * Get default route invocation strategy
     *
     * @return InvocationStrategyInterface
     */
    public function getDefaultInvocationStrategy(): InvocationStrategyInterface
    {
        return $this->defaultInvocationStrategy;
    }

    /**
     * @param InvocationStrategyInterface $strategy
     * @return self
     */
    public function setDefaultInvocationStrategy(InvocationStrategyInterface $strategy): RouteCollectorInterface
    {
        $this->defaultInvocationStrategy = $strategy;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheFile(): ?string
    {
        return $this->cacheFile;
    }

    /**
     * {@inheritdoc}
     */
    public function setCacheFile(string $cacheFile): RouteCollectorInterface
    {
        if (file_exists($cacheFile) && !is_readable($cacheFile)) {
            throw new RuntimeException(
                sprintf('Route collector cache file `%s` is not readable', $cacheFile)
            );
        }

        if (!file_exists($cacheFile) && !is_writable(dirname($cacheFile))) {
            throw new RuntimeException(
                sprintf('Route collector cache file directory `%s` is not writable', dirname($cacheFile))
            );
        }

        $this->cacheFile = $cacheFile;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Set the base path used in pathFor()
     *
     * @param string $basePath
     *
     * @return self
     */
    public function setBasePath(string $basePath): RouteCollectorInterface
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamedRoute(string $name): RouteInterface
    {
        foreach ($this->routes as $route) {
            if ($name === $route->getName()) {
                return $route;
            }
        }
        throw new RuntimeException('Named route does not exist for name: ' . $name);
    }

    /**
     * {@inheritdoc}
     */
    public function removeNamedRoute(string $name): RouteCollectorInterface
    {
        /** @var Route $route */
        $route = $this->getNamedRoute($name);
        unset($this->routes[$route->getIdentifier()]);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function lookupRoute(string $identifier): RouteInterface
    {
        if (!isset($this->routes[$identifier])) {
            throw new RuntimeException('Route not found, looks like your route cache is stale.');
        }
        return $this->routes[$identifier];
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
        $group = new RouteGroup($pattern, $callable, $this->callableResolver);
        $this->routeGroups[] = $group;
        return $group;
    }

    /**
     * Removes the last route group from the array
     *
     * @return RouteGroupInterface|null The last RouteGroup, if one exists
     */
    public function popGroup(): ?RouteGroupInterface
    {
        return array_pop($this->routeGroups);
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
     * {@inheritdoc}
     */
    public function map(array $methods, string $pattern, $handler): RouteInterface
    {
        // Prepend parent group pattern(s)
        if ($this->routeGroups) {
            $pattern = $this->processGroups() . $pattern;
        }

        $route = $this->createRoute($methods, $pattern, $handler);
        $this->routes[$route->getIdentifier()] = $route;
        $this->routeCounter++;

        return $route;
    }

    /**
     * {@inheritdoc}
     */
    protected function createRoute(array $methods, string $pattern, $callable): RouteInterface
    {
        return new Route(
            $methods,
            $pattern,
            $callable,
            $this->responseFactory,
            $this->callableResolver,
            $this->container,
            $this->defaultInvocationStrategy,
            $this->routeGroups,
            $this->routeCounter
        );
    }

    /**
     * {@inheritdoc}
     */
    public function relativePathFor(string $name, array $data = [], array $queryParams = []): string
    {
        $route = $this->getNamedRoute($name);
        $pattern = $route->getPattern();

        $segments = [];
        $segmentName = '';

        /*
         * $routes is an associative array of expressions representing a route as multiple segments
         * There is an expression for each optional parameter plus one without the optional parameters
         * The most specific is last, hence why we reverse the array before iterating over it
         */
        $expressions = array_reverse($this->routeParser->parse($pattern));
        foreach ($expressions as $expression) {
            foreach ($expression as $segment) {
                /*
                 * Each $segment is either a string or an array of strings
                 * containing optional parameters of an expression
                 */
                if (is_string($segment)) {
                    $segments[] = $segment;
                    continue;
                }

                /*
                 * If we don't have a data element for this segment in the provided $data
                 * we cancel testing to move onto the next expression with a less specific item
                 */
                if (!array_key_exists($segment[0], $data)) {
                    $segments = [];
                    $segmentName = $segment[0];
                    break;
                }

                $segments[] = $data[$segment[0]];
            }

            /*
             * If we get to this logic block we have found all the parameters
             * for the provided $data which means we don't need to continue testing
             * less specific expressions
             */
            if (!empty($segments)) {
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function urlFor(string $name, array $data = [], array $queryParams = []): string
    {
        trigger_error('urlFor() is deprecated. Use pathFor() instead.', E_USER_DEPRECATED);
        return $this->pathFor($name, $data, $queryParams);
    }
}
