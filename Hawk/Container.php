<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Hawk;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Pimple\Container as PimpleContainer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hawk\Exception\ContainerValueNotFoundException;
use Hawk\Handlers\Error;
use Hawk\Handlers\NotFound;
use Hawk\Handlers\NotAllowed;
use Hawk\Handlers\Strategies\RequestResponse;
use Hawk\Http\Environment;
use Hawk\Http\Headers;
use Hawk\Http\Request;
use Hawk\Http\Response;
use Hawk\Interfaces\CallableResolverInterface;
use Hawk\Interfaces\Http\EnvironmentInterface;
use Hawk\Interfaces\InvocationStrategyInterface;
use Hawk\Interfaces\RouterInterface;

/**
 * Slim's default DI container is Pimple.
 *
 * Hawk\App expects a container that implements Interop\Container\ContainerInterface
 * with these service keys configured and ready for use:
 *
 *  - settings: an array or instance of \ArrayAccess
 *  - environment: an instance of \Hawk\Interfaces\Http\EnvironmentInterface
 *  - request: an instance of \Psr\Http\Message\ServerRequestInterface
 *  - response: an instance of \Psr\Http\Message\ResponseInterface
 *  - router: an instance of \Hawk\Interfaces\RouterInterface
 *  - foundHandler: an instance of \Hawk\Interfaces\InvocationStrategyInterface
 *  - errorHandler: a callable with the signature: function($request, $response, $exception)
 *  - notFoundHandler: a callable with the signature: function($request, $response)
 *  - notAllowedHandler: a callable with the signature: function($request, $response, $allowedHttpMethods)
 *  - callableResolver: an instance of \Hawk\Interfaces\CallableResolverInterface
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
        'outputBuffering' => false,
        'determineRouteBeforeAppMiddleware' => true,
        'displayErrorDetails' => false,
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
         * @return array|\ArrayAccess
         */
        $this['settings'] = function () use ($userSettings, $defaultSettings) {
            return new Collection(array_merge($defaultSettings, $userSettings));
        };

        if (!isset($this['environment'])) {
            /**
             * This service MUST return a shared instance
             * of \Hawk\Interfaces\Http\EnvironmentInterface.
             *
             * @return EnvironmentInterface
             */
            $this['environment'] = function () {
                return new Environment($_SERVER);
            };
        }

        if (!isset($this['request'])) {
            /**
             * PSR-7 Request object
             *
             * @param Container $c
             *
             * @return ServerRequestInterface
             */
            $this['request'] = function ($c) {
                return Request::createFromEnvironment($c->get('environment'));
            };
        }

        if (!isset($this['response'])) {
            /**
             * PSR-7 Response object
             *
             * @param Container $c
             *
             * @return ResponseInterface
             */
            $this['response'] = function ($c) {
                $headers = new Headers(['Content-Type' => 'text/html']);
                $response = new Response(200, $headers);

                return $response->withProtocolVersion($c->get('settings')['httpVersion']);
            };
        }

        if (!isset($this['router'])) {
            /**
             * This service MUST return a SHARED instance
             * of \Hawk\Interfaces\RouterInterface.
             *
             * @return RouterInterface
             */
            $this['router'] = function () {
                return new Router;
            };
        }

        if (!isset($this['foundHandler'])) {
            /**
             * This service MUST return a SHARED instance
             * of \Hawk\Interfaces\InvocationStrategyInterface.
             *
             * @return InvocationStrategyInterface
             */
            $this['foundHandler'] = function () {
                return new RequestResponse;
            };
        }

        /**
         * This service MUST return a callable
         * that accepts three arguments:
         *
         * 1. Instance of \Psr\Http\Message\ServerRequestInterface
         * 2. Instance of \Psr\Http\Message\ResponseInterface
         * 3. An array(All route's arguments)
         *
         * The callable MUST return an instance of
         * \Psr\Http\Message\ResponseInterface.
         *
         * @param Container $c
         *
         * @return callable
         */
        if (!isset($this['authHandler'])) {
            $this['authHandler'] = function($c) {
                return function(ServerRequestInterface $request, ResponseInterface $response, $args) {
                    return $response;
                };
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
                return new Error($c->get('settings')['displayErrorDetails']);
            };
        }

        if (!isset($this['notFoundHandler'])) {
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
             * @return callable
             */
            $this['notFoundHandler'] = function () {
                return new NotFound;
            };
        }

        if (!isset($this['notAllowedHandler'])) {
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
             * @return callable
             */
            $this['notAllowedHandler'] = function () {
                return new NotAllowed;
            };
        }

        if (!isset($this['callableResolver'])) {
            /**
             * Instance of \Hawk\Interfaces\CallableResolverInterface
             *
             * @param Container $c
             *
             * @return CallableResolverInterface
             */
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
     * @throws ContainerValueNotFoundException  No entry was found for this identifier.
     * @throws ContainerException               Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if (!$this->offsetExists($id)) {
            throw new ContainerValueNotFoundException(sprintf('Identifier "%s" is not defined.', $id));
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
