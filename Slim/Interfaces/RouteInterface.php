<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Interfaces;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Route Interface
 *
 * @package Slim
 * @since   3.0.0
 */
interface RouteInterface
{

    /**
     * Retrieve a specific route argument
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getArgument($name, $default = null);

    /**
     * Get route arguments
     *
     * @return array
     */
    public function getArguments();

    /**
     * Get route name
     *
     * @return null|string
     */
    public function getName();

    /**
     * Get route pattern
     *
     * @return string
     */
    public function getPattern();

    /**
     * Set a route argument
     *
     * @param string $name
     * @param string $value
     *
     * @return static
     */
    public function setArgument($name, $value);

    /**
     * Replace route arguments
     *
     * @param array $arguments
     *
     * @return static
     */
    public function setArguments(array $arguments);

    /**
     * Set route name
     *
     * @param string $name
     *
     * @return static
     * @throws InvalidArgumentException if the route name is not a string
     */
    public function setName($name);

    /**
     * Add middleware
     *
     * This method prepends new middleware to the route's middleware stack.
     *
     * @param callable|string $callable The callback routine
     *
     * @return RouteInterface
     */
    public function add($callable);

    /**
     * Prepare the route for use
     *
     * @param ServerRequestInterface $request
     * @param array $arguments
     */
    public function prepare(ServerRequestInterface $request, array $arguments);

    /**
     * Run route
     *
     * This method traverses the middleware stack, including the route's callable
     * and captures the resultant HTTP response object. It then sends the response
     * back to the Application.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function run(ServerRequestInterface $request, ResponseInterface $response);

    /**
     * Add a tag to the route
     *
     * @param  $tag
     * @return self
     */
    public function addTag($tag);

    /**
     * Add a list of tags to the route
     *
     * @param $tags
     * @return $this
     */
    public function addTags(array $tags);

    /**
     * Retrieve a list of tags
     *
     * @return array
     */
    public function getTags();

    /**
     * Determine whether a tag is in the route
     *
     * @param $tag
     * @return bool
     */
    public function hasTag($tag);

    /**
     * Dispatch route callable against current Request and Response objects
     *
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     *
     * @param ServerRequestInterface $request  The current Request object
     * @param ResponseInterface      $response The current Response object
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response);
}
