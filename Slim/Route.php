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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Handlers\Strategies\RequestHandler;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RouteInterface;

/**
 * Route
 */
class Route extends Routable implements RouteInterface, MiddlewareInterface
{
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
     * @var InvocationStrategyInterface
     */
    protected $invocationStrategy;

    /**
     * Route parameters
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Route arguments parameters
     *
     * @var array
     */
    protected $savedArguments = [];

    /**
     * Create new route
     *
     * @param string[]                          $methods The route HTTP methods
     * @param string                            $pattern The route pattern
     * @param callable|string                   $callable The route callable
     * @param ResponseFactoryInterface          $responseFactory
     * @param CallableResolverInterface         $callableResolver
     * @param InvocationStrategyInterface|null  $invocationStrategy
     * @param RouteGroup[]                      $groups The parent route groups
     * @param int                               $identifier The route identifier
     */
    public function __construct(
        array $methods,
        string $pattern,
        $callable,
        ResponseFactoryInterface $responseFactory,
        CallableResolverInterface $callableResolver,
        InvocationStrategyInterface $invocationStrategy = null,
        array $groups = [],
        int $identifier = 0
    ) {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->callable = $callable;
        $this->responseFactory = $responseFactory;
        $this->callableResolver = $callableResolver;
        $this->invocationStrategy = $invocationStrategy ?? new RequestResponse();
        $this->groups = $groups;
        $this->identifier = 'route' . $identifier;
        $this->middlewareRunner = new MiddlewareRunner();
    }

    /**
     * Get route invocation strategy
     *
     * @return InvocationStrategyInterface
     */
    public function getInvocationStrategy(): InvocationStrategyInterface
    {
        return $this->invocationStrategy;
    }

    /**
     * Get route callable
     *
     * @return callable|string
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * This method enables you to override the Route's callable
     *
     * @param callable|string $callable
     * @return self
     */
    public function setCallable($callable): self
    {
        $this->callable = $callable;
        return $this;
    }

    /**
     * Get route methods
     *
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get parent route groups
     *
     * @return RouteGroup[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Get route name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get route identifier
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Set route name
     *
     * @param string $name
     *
     * @return self
     */
    public function setName(string $name): RouteInterface
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Retrieve a specific route argument
     *
     * @param string $name
     * @param string|null $default
     * @return mixed
     */
    public function getArgument(string $name, $default = null)
    {
        if (array_key_exists($name, $this->arguments)) {
            return $this->arguments[$name];
        }
        return $default;
    }

    /**
     * Set a route argument
     *
     * @param string $name
     * @param string $value
     * @param bool $includeInSavedArguments
     * @return self
     */
    public function setArgument(string $name, string $value, bool $includeInSavedArguments = true): RouteInterface
    {
        if ($includeInSavedArguments) {
            $this->savedArguments[$name] = $value;
        }

        $this->arguments[$name] = $value;
        return $this;
    }

    /**
     * Retrieve route arguments
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Replace route arguments
     *
     * @param array $arguments
     * @param bool $includeInSavedArguments
     * @return self
     */
    public function setArguments(array $arguments, bool $includeInSavedArguments = true): RouteInterface
    {
        if ($includeInSavedArguments) {
            $this->savedArguments = $arguments;
        }

        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @param MiddlewareInterface|string|callable $middleware
     * @return RouteInterface
     */
    public function add($middleware): RouteInterface
    {
        $this->addRouteMiddleware($middleware);
        return $this;
    }

    /**
     * @param MiddlewareInterface $middleware
     * @return RouteInterface
     */
    public function addMiddleware(MiddlewareInterface $middleware): RouteInterface
    {
        $this->addRouteMiddleware($middleware);
        return $this;
    }

    /********************************************************************************
     * Route Runner
     *******************************************************************************/

    /**
     * Prepare the route for use
     *
     * @param ServerRequestInterface $request
     * @param array $arguments
     * @return void
     */
    public function prepare(ServerRequestInterface $request, array $arguments): void
    {
        // Remove temp arguments
        $this->setArguments($this->savedArguments);

        // Add the arguments
        foreach ($arguments as $k => $v) {
            $this->setArgument($k, $v, false);
        }
    }

    /**
     * Finalize the route in preparation for dispatching
     * @return void
     */
    public function finalize(): void
    {
        if ($this->finalized) {
            return;
        }

        $groupMiddleware = [];
        foreach ($this->groups as $group) {
            foreach ($group->getMiddleware() as $middleware) {
                array_unshift($groupMiddleware, $middleware);
            }
        }

        $middleware = array_merge(array_reverse($groupMiddleware), $this->getMiddleware());
        $middleware[] = $this;
        $this->middlewareRunner->setMiddleware($middleware);

        $this->finalized = true;
    }

    /**
     * Run route
     *
     * This method traverses the middleware stack, including the route's callable
     * and captures the resultant HTTP response object. It then sends the response
     * back to the Application.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function run(ServerRequestInterface $request): ResponseInterface
    {
        // Finalise route now that we are about to run it
        $this->finalize();

        // Traverse middleware stack and fetch updated response
        return $this->middlewareRunner->run($request);
    }

    /**
     * Dispatch route callable against current Request and Response objects
     *
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     *
     * @param ServerRequestInterface $request   The current Request object
     * @param RequestHandlerInterface $handler  The current RequestHandler object
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $callable = $this->callableResolver->resolve($this->callable);

        $strategy = $this->invocationStrategy;
        if (is_array($callable) && $callable[0] instanceof RequestHandlerInterface) {
            $strategy = new RequestHandler();
        }

        $response = $this->responseFactory->createResponse();
        return $strategy($callable, $request, $response, $this->arguments);
    }
}
