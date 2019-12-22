<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @author  Temuri Takalandze <takalandzet@gmail.com>
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Controller;

use BadMethodCallException;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Abstract Class AbstractController.
 *
 * @category Controller
 * @package  Slim\Controller
 * @author   Temuri Takalandze <takalandzet@gmail.com>
 * @license  https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 * @link     https://github.com/slimphp/Slim
 */
abstract class AbstractController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var array
     */
    private $args;

    /**
     * @param LoggerInterface $logger    Monolog instance.
     * @param \DI\Container   $container Service Container.
     */
    public function __construct(LoggerInterface $logger, Container $container)
    {
        $this->logger = $logger;
        $this->container = $container;
    }

    public function __call($name, $arguments)
    {
        $name .= 'Action';

        $this->request = $arguments[0] ?? null;
        $this->response = $arguments[1] ?? null;
        $this->args = $arguments[2] ?? [];

        if (!method_exists($this, $name)) {
            throw new BadMethodCallException(
                "Undefined action {$name}()"
            );
        }

        return call_user_func([$this, $name]);
    }

    /**
     * Test if the container can provide something for the given name.
     *
     * @param string $name Entry name or a class name.
     *
     * @throws InvalidArgumentException The name parameter must be of type string.
     *
     * @return bool
     */
    protected function has(string $name): bool
    {
        return $this->container->has($name);
    }

    /**
     * Returns an entry of the container by its name.
     *
     * @param string $name Entry name or a class name.
     *
     * @throws DependencyException Error while resolving the entry.
     * @throws NotFoundException No entry found for the given name.
     *
     * @return mixed Service.
     */
    protected function get(string $name)
    {
        return $this->container->get($name);
    }

    /**
     * Get logger.
     *
     * @return LoggerInterface Logger.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Get current request object.
     *
     * @return Request Current request object.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get current response object.
     *
     * @return Response Current response object.
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Resolve current request arguments.
     *
     * @param string $name         Argument name.
     * @param mixed  $defaultValue Value if argument not found.
     *
     * @return mixed Argument value.
     */
    protected function resolveArg(string $name, $defaultValue = null)
    {
        return $this->args[$name] ?? $defaultValue;
    }

    /**
     * Return JSON response.
     *
     * @param mixed $data Response data.
     *
     * @return Response New JSON Response.
     */
    protected function json($data = null): Response
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        $this->response->getBody()->write($json);

        return $this->response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Renders a view.
     *
     * @param string $view
     * @param array  $parameters
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    protected function render(string $view, array $parameters = []): Response
    {
        if (!$this->container->has('view')) {
            throw new \LogicException(
                'You can not use the "render" method if the slim/twig-view ' .
                'is not available. Try running "composer require slim/twig-view".'
            );
        }

        return $this->container
            ->get('view')
            ->render(
                $this->response,
                $view,
                $parameters
            );
    }
}
