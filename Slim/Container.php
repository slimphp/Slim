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
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\Http\EnvironmentInterface;
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
        'cookieLifetime' => '20 minutes',
        'cookiePath' => '/',
        'cookieDomain' => null,
        'cookieSecure' => false,
        'cookieHttpOnly' => false,
        'httpVersion' => '1.1',
        'responseChunkSize' => 4096,
        'outputBuffering' => 'append',
    ];


    /********************************************************************************
     * Constructor sets up default Pimple services
     *******************************************************************************/

    /**
     * Create new container
     *
     * @param array $userSettings Associative array of application settings
     */
    public function __construct(array $userSettings = [])
    {
        parent::__construct();

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
        $this['environment'] = function ($c) {
            return new Environment($_SERVER);
        };

        /**
         * This service MUST return a NEW instance
         * of \Psr\Http\Message\ServerRequestInterface.
         */
        $this['request'] = $this->factory(
            /**
             * @param Container $c
             *
             * @return ServerRequestInterface
             */
            function ($c) {
                return Request::createFromEnvironment($c['environment']);
            }
        );

        /**
         * This service MUST return a NEW instance
         * of \Psr\Http\Message\ResponseInterface.
         */
        $this['response'] = $this->factory(
            /**
             * @param Container $c
             *
             * @return ResponseInterface
             */
            function ($c) {
                $headers = new Headers(['Content-Type' => 'text/html']);
                $response = new Response(200, $headers);

                return $response->withProtocolVersion($c['settings']['httpVersion']);
            }
        );

        /**
         * This service MUST return a SHARED instance
         * of \Slim\Interfaces\RouterInterface.
         *
         * @param Container $c
         *
         * @return RouterInterface
         */
        $this['router'] = function ($c) {
            return new Router();
        };

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
        $this['errorHandler'] = function ($c) {
            return new Error();
        };

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
        $this['notFoundHandler'] = function ($c) {
            return new NotFound();
        };

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
        $this['notAllowedHandler'] = function ($c) {
            return new NotAllowed;
        };

        /**
         * This service MUST return a NEW instance of
         * \Slim\Interfaces\CallableResolverInterface
         */
        $this['callableResolver'] = $this->factory(
            /**
             * @param Container $c
             *
             * @return CallableResolverInterface
             */
            function ($c) {
                return new CallableResolver($c);
            }
        );
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
