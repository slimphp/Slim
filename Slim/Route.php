<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use Closure;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Container\ContainerInterface;
use Slim\Interfaces\RouteInterface;
use Slim\CallableResolverAwareTrait;

use Slim\MiddlewareAware;

/**
 * Route
 */
class Route implements RouteInterface
{
    use CallableResolverAwareTrait;

    use MiddlewareAware {
        add as addMiddleware;
    }

    /**
     * Container
     *
     * @var ContainerInterface
     */
    private $container;

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
        $this->callable = $callable;
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
        if ($callable instanceof Closure) {
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
     * Set container for use with resolveCallable
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
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
    public function run(ServerRequestInterface $request, ResponseInterface $response)
    {
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
        // invoke route callable
        try {
            ob_start();
            $function = $this->callable;
            $newResponse = $function($request, $response, $request->getAttributes());
            $output = ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }

        // if route callback returns a ResponseInterface, then use it
        if ($newResponse instanceof ResponseInterface) {
            $response = $newResponse;
        }

        // if route callback retuns a string, then append it to the response
        if (is_string($newResponse)) {
            $response->getBody()->write($newResponse);
        }

        // append output buffer content if there is any
        if ($output) {
            $response->getBody()->write($output);
        }

        return $response;
    }
}
