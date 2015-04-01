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
use Slim\Interfaces\RouteInterface;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Route
 */
class Route implements RouteInterface, ServiceProviderInterface
{
    use ResolveCallable;
    use MiddlewareAware {
        add as addMiddleware;
    }

    /**
     * HTTP methods supported by this route
     *
     * @var string[]
     */
    protected $methods = [];

    /**
     * Route pattern
     *
     * @var string
     */
    protected $pattern;

    /**
     * Route callable
     *
     * @var callable
     */
    protected $callable;

    /**
     * Route name
     *
     * @var null|string
     */
    protected $name;

    /**
     * Route parsed arguments
     *
     * @var array
     */
    protected $parsedArgs = [];

    /**
     * Create new route
     *
     * @param string[] $methods       The route HTTP methods
     * @param string   $pattern       The route pattern
     * @param callable $callable      The route callable
     */
    public function __construct($methods, $pattern, $callable)
    {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->setCallable($callable);
        $this->seedMiddlewareStack();
    }

    /**
     * Add middleware
     *
     * This method prepends new middleware to the route's middleware stack.
     *
     * @param  mixed    $callable The callback routine
     *
     * @return RouteInterface
     */
    public function add($callable)
    {
        $callable = $this->resolveCallable($callable);
        if ($callable instanceof \Closure) {
            $callable = $callable->bindTo($this->container);
        }

        return $this->addMiddleware($callable);
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
     * Get route pattern
     *
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
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
     * Set route callable
     *
     * @param callable $callable
     *
     * @throws \InvalidArgumentException If argument is not callable
     */
    protected function setCallable(callable $callable)
    {
        $this->callable = $callable;
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
     * Set route name
     *
     * @param string $name
     */
    public function setName($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('Route name must be a string');
        }
        $this->name = $name;
        return $this;
    }

    /**
     * Set container for use with resolveCallable
     *
     * @param \Pimple\Container $container
     */
    public function register(Container $container)
    {
        $this->container = $container;
        return $this;
    }

    /********************************************************************************
    * Route Runner
    *******************************************************************************/

    /**
     * Run route
     *
     * This method traverses the middleware stack, including the route's callable
     * and captures the resultant HTTP response object. It then sends the response
     * back to the Application.
     */
    public function run(RequestInterface $request, ResponseInterface $response, array $args)
    {
        $this->parsedArgs = $args;

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
     * @param RequestInterface $request The current Request object
     * @param ResponseInterface $response The current Response object
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response)
    {
        // Invoke route callable
        try {
            ob_start();
            $newResponse = call_user_func_array($this->callable, [$request, $response, $this->parsedArgs]);
            $output = ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }

        // End if route callback returns Interfaces\Http\ResponseInterface object
        if ($newResponse instanceof ResponseInterface) {
            return $newResponse;
        }

        // Else append output buffer content
        if ($output) {
            $response->getBody()->write($output);
        }

        return $response;
    }
}
