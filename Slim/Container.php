<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Pimple\Container as PimpleContainer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\NotFoundException;
use Slim\Handlers\Error;
use Slim\Handlers\NotFound;
use Slim\Handlers\NotAllowed;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\Http\EnvironmentInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RouterInterface;

/**
 * Slim's default DI container is Pimple.
 *
 * Slim\App expects a container that implements Interop\Container\ContainerInterface
 * with these service keys configured and ready for use:
 *
 *  - settings: an array or instance of \ArrayAccess
 *  - environment: an instance of \Slim\Interfaces\Http\EnvironmentInterface
 *  - request: an instance of \Psr\Http\Message\ServerRequestInterface
 *  - response: an instance of \Psr\Http\Message\ResponseInterface
 *  - router: an instance of \Slim\Interfaces\RouterInterface
 *  - foundHandler: an instance of \Slim\Interfaces\InvocationStrategyInterface
 *  - errorHandler: a callable with the signature: function($request, $response, $exception)
 *  - notFoundHandler: a callable with the signature: function($request, $response)
 *  - notAllowedHandler: a callable with the signature: function($request, $response, $allowedHttpMethods)
 *  - callableResolver: an instance of \Slim\Interfaces\CallableResolverInterface
 */
final class Container extends PimpleContainer implements ContainerInterface
{
    /**
     * Default settings
     *
     * @var array
     */
    private $defaultSettings = [
        'httpVersion' => '1.1',
        'responseChunkSize' => 4096,
        'outputBuffering' => 'append',
        'determineRouteBeforeAppMiddleware' => false,
    ];

    /**
     * Create new container
     *
     * @param array $values The parameters or objects.
     */
    public function __construct(array $values = [])
    {
        parent::__construct($values);

        $userSettings = isset($values['settings']) ? $values['settings'] : [];
        $this->registerDefaultServices($userSettings);
    }

    /**
     * This function registers the default services that Slim needs to work.
     *
     * All services are shared - that is, they are registered such that the
     * same instance is returned on subsequent calls.
     *
     * @param array $userSettings Associative array of application settings
     *
     * @return void
     */
    private function registerDefaultServices($userSettings)
    {
        $defaultSettings = $this->defaultSettings;

        /**
         * This service MUST return an array or an
         * instance of \ArrayAccess.
         *
         * @param Container $c
         *
         * @return array|\ArrayAccess
         */
        $this['settings'] = function ($c) use ($userSettings, $defaultSettings) {
            return array_merge($defaultSettings, $userSettings);
        };

        /**
         * This service MUST return a shared instance
         * of \Slim\Interfaces\Http\EnvironmentInterface.
         *
         * @param Container $c
         *
         * @return EnvironmentInterface
         */
        if (!isset($this['environment'])) {
            $this['environment'] = function ($c) {
                return new Environment($_SERVER);
            };
        }

        /**
         * PSR-7 Request object
         *
         * @param Container $c
         *
         * @return ServerRequestInterface
         */
        if (!isset($this['request'])) {
            $this['request'] = function ($c) {
                return Request::createFromEnvironment($c['environment']);
            };
        }

        /**
         * PSR-7 Response object
         *
         * @param Container $c
         *
         * @return ResponseInterface
         */
        if (!isset($this['response'])) {
            $this['response'] = function ($c) {
                $headers = new Headers(['Content-Type' => 'text/html']);
                $response = new Response(200, $headers);

                return $response->withProtocolVersion($c['settings']['httpVersion']);
            };
        }

        /**
         * This service MUST return a SHARED instance
         * of \Slim\Interfaces\RouterInterface.
         *
         * @param Container $c
         *
         * @return RouterInterface
         */
        if (!isset($this['router'])) {
            $this['router'] = function ($c) {
                return new Router();
            };
        }

        /**
         * This service MUST return a SHARED instance
         * of \Slim\Interfaces\InvocationStrategyInterface.
         *
         * @param Container $c
         *
         * @return InvocationStrategyInterface
         */
        if (!isset($this['foundHandler'])) {
            $this['foundHandler'] = function ($c) {
                return new RequestResponse();
            };
        }

        /**
         * This service MUST return a callable
         * that accepts three arguments:
         *
         * 1. Instance of \Psr\Http\Message\ServerRequestInterface
         * 2. Instance of \Psr\Http\Message\ResponseInterface
         * 3. Instance of \Exception
         *
         * The callable MUST return an instance of
         * \Psr\Http\Message\ResponseInterface.
         *
         * @param Container $c
         *
         * @return callable
         */
        if (!isset($this['errorHandler'])) {
            $this['errorHandler'] = function ($c) {
                return new Error();
            };
        }

        /**
         * This service MUST return a callable
         * that accepts two arguments:
         *
         * 1. Instance of \Psr\Http\Message\ServerRequestInterface
         * 2. Instance of \Psr\Http\Message\ResponseInterface
         *
         * The callable MUST return an instance of
         * \Psr\Http\Message\ResponseInterface.
         *
         * @param Container $c
         *
         * @return callable
         */
        if (!isset($this['notFoundHandler'])) {
            $this['notFoundHandler'] = function ($c) {
                return new NotFound();
            };
        }

        /**
         * This service MUST return a callable
         * that accepts three arguments:
         *
         * 1. Instance of \Psr\Http\Message\ServerRequestInterface
         * 2. Instance of \Psr\Http\Message\ResponseInterface
         * 3. Array of allowed HTTP methods
         *
         * The callable MUST return an instance of
         * \Psr\Http\Message\ResponseInterface.
         *
         * @param Container $c
         *
         * @return callable
         */
        if (!isset($this['notAllowedHandler'])) {
            $this['notAllowedHandler'] = function ($c) {
                return new NotAllowed;
            };
        }

        /**
         * Instance of \Slim\Interfaces\CallableResolverInterface
         *
         * @param Container $c
         *
         * @return CallableResolverInterface
         */
        if (!isset($this['callableResolver'])) {
            $this['callableResolver'] = function ($c) {
                return new CallableResolver($c);
            };
        }
    }

    /********************************************************************************
     * Methods to satisfy Interop\Container\ContainerInterface
     *******************************************************************************/

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundException  No entry was found for this identifier.
     * @throws ContainerException Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if (!$this->offsetExists($id)) {
            throw new NotFoundException(sprintf('Identifier "%s" is not defined.', $id));
        }
        return $this->offsetGet($id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($id)
    {
        return $this->offsetExists($id);
    }
}
