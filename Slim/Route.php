<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim;

use Exception;
use Throwable;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RouteInterface;

/**
 * Route
 */
class Route extends Routable implements RouteInterface
{
    use MiddlewareAwareTrait;

    /**
     * HTTP methods supported by this route
     *
     * @var string[]
     */
    protected $methods = [];

    /**
     * Route identifier
     *
     * @var string
     */
    protected $identifier;

    /**
     * Route name
     *
     * @var null|string
     */
    protected $name;

    /**
     * Parent route groups
     *
     * @var RouteGroup[]
     */
    protected $groups;

    /**
     * @var bool
     */
    private $finalized = false;

    /**
     * @var \Slim\Interfaces\InvocationStrategyInterface
     */
    protected $routeInvocationStrategy;

    /**
     * Route parameters
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Create new route
     *
     * @param string|string[]   $methods The route HTTP methods
     * @param string            $pattern The route pattern
     * @param callable          $callable The route callable
     * @param RouteGroup[]      $groups The parent route groups
     * @param int               $identifier The route identifier
     */
    public function __construct($methods, $pattern, $callable, $groups = [], $identifier = 0)
    {
        $this->methods  = is_string($methods) ? [$methods] : $methods;
        $this->pattern  = $pattern;
        $this->callable = $callable;
        $this->groups   = $groups;
        $this->identifier = 'route' . $identifier;
        $this->routeInvocationStrategy = new RequestResponse();
    }

    /**
     * Set route invocation strategy
     *
     * @param InvocationStrategyInterface $strategy
     */
    public function setInvocationStrategy(InvocationStrategyInterface $strategy)
    {
        $this->routeInvocationStrategy = $strategy;
    }

    /**
     * Get route invocation strategy
     *
     * @return InvocationStrategyInterface
     */
    public function getInvocationStrategy()
    {
        return $this->routeInvocationStrategy;
    }

    /**
     * Finalize the route in preparation for dispatching
     */
    public function finalize()
    {
        if ($this->finalized) {
            return;
        }

        $groupMiddleware = [];
        foreach ($this->getGroups() as $group) {
            $groupMiddleware = array_merge($group->getMiddleware(), $groupMiddleware);
        }

        $this->middleware = array_merge($this->middleware, $groupMiddleware);

        foreach ($this->getMiddleware() as $middleware) {
            $this->addMiddleware($middleware);
        }

        $this->finalized = true;
    }

    /**
     * Get route callable
     *
     * @return callable
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * This method enables you to override the Route's callable
     *
     * @param string|\Closure $callable
     */
    public function setCallable($callable)
    {
        $this->callable = $callable;
    }

    /**
     * Get route methods
     *
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Get parent route groups
     *
     * @return RouteGroup[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Get route name
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get route identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set route name
     *
     * @param string $name
     *
     * @return self
     *
     * @throws InvalidArgumentException if the route name is not a string
     */
    public function setName($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Route name must be a string');
        }
        $this->name = $name;
        return $this;
    }

    /**
     * Set a route argument
     *
     * @param string $name
     * @param string $value
     *
     * @return self
     */
    public function setArgument($name, $value)
    {
        $this->arguments[$name] = $value;
        return $this;
    }

    /**
     * Replace route arguments
     *
     * @param array $arguments
     *
     * @return self
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * Retrieve route arguments
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Retrieve a specific route argument
     *
     * @param string $name
     * @param string|null $default
     *
     * @return mixed
     */
    public function getArgument($name, $default = null)
    {
        if (array_key_exists($name, $this->arguments)) {
            return $this->arguments[$name];
        }
        return $default;
    }

    /********************************************************************************
     * Route Runner
     *******************************************************************************/

    /**
     * Prepare the route for use
     *
     * @param ServerRequestInterface $request
     * @param array $arguments
     */
    public function prepare(ServerRequestInterface $request, array $arguments)
    {
        // Add the arguments
        foreach ($arguments as $k => $v) {
            $this->setArgument($k, $v);
        }
    }

    /**
     * Run route
     *
     * This method traverses the middleware stack, including the route's callable
     * and captures the resultant HTTP response object. It then sends the response
     * back to the Application.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function run(ServerRequestInterface $request, ResponseInterface $response)
    {
        // Finalise route now that we are about to run it
        $this->finalize();

        // Traverse middleware stack and fetch updated response
        return $this->callMiddlewareStack($request, $response);
    }

    /**
     * Dispatch route callable against current Request and Response objects
     *
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     *
     * @param ServerRequestInterface $request  The current Request object
     * @param ResponseInterface      $response The current Response object
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception  if the route callable throws an exception
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        // Resolve route callable
        $callable = $this->callable;
        if ($this->callableResolver) {
            $callable = $this->callableResolver->resolve($callable);
        }

        /** @var InvocationStrategyInterface $handler */
        $handler = $this->routeInvocationStrategy;

        $routeResponse = $handler($callable, $request, $response, $this->arguments);
        if (! $routeResponse instanceof ResponseInterface) {
            throw new \RuntimeException('Route handler must return instance of \Psr\Http\Message\ResponseInterface');
        }

        return $routeResponse;
    }
}
