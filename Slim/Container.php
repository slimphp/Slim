<?php
namespace Slim;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\NotFoundException;
use Pimple\Container as PimpleContainer;

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
        'responseChunkSize' => 4096
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

        /**
         * This service MUST return an array or an
         * instance of \ArrayAccess.
         */
        $defaultSettings = $this->defaultSettings;
        $this['settings'] = function ($c) use ($userSettings, $defaultSettings) {
            return array_merge($defaultSettings, $userSettings);
        };

        /**
         * This service MUST return a shared instance
         * of \Slim\Interfaces\Http\EnvironmentInterface.
         */
        $this['environment'] = function ($c) {
            return new Http\Environment($_SERVER);
        };

        /**
         * This service MUST return a NEW instance
         * of \Psr\Http\Message\RequestInterface.
         */
        $this['request'] = $this->factory(function ($c) {
            $env = $c['environment'];
            $method = $env['REQUEST_METHOD'];
            $uri = Http\Uri::createFromEnvironment($env);
            $headers = Http\Headers::createFromEnvironment($env);
            $cookies = Http\Cookies::parseHeader($headers->get('Cookie', []));
            $serverParams = $env->all();
            $body = new Http\Body(fopen('php://input', 'r'));

            return new Http\Request($method, $uri, $headers, $cookies, $serverParams, $body);
        });

        /**
         * This service MUST return a NEW instance
         * of \Psr\Http\Message\ResponseInterface.
         */
        $this['response'] = $this->factory(function ($c) {
            $headers = new Http\Headers(['Content-Type' => 'text/html']);
            $response = new Http\Response(200, $headers);

            return $response->withProtocolVersion($c['settings']['httpVersion']);
        });

        /**
         * This service MUST return a SHARED instance
         * of \Slim\Interfaces\RouterInterface.
         */
        $this['router'] = function ($c) {
            return new Router();
        };

        /**
         * This service MUST return a callable
         * that accepts three arguments:
         *
         * 1. Instance of \Psr\Http\Message\RequestInterface
         * 2. Instance of \Psr\Http\Message\ResponseInterface
         * 3. Instance of \Exception
         *
         * The callable MUST return an instance of
         * \Psr\Http\Message\ResponseInterface.
         */
        $this['errorHandler'] = function ($c) {
            return new Handlers\Error;
        };

        /**
         * This service MUST return a callable
         * that accepts two arguments:
         *
         * 1. Instance of \Psr\Http\Message\RequestInterface
         * 2. Instance of \Psr\Http\Message\ResponseInterface
         *
         * The callable MUST return an instance of
         * \Psr\Http\Message\ResponseInterface.
         */
        $this['notFoundHandler'] = function ($c) {
            return new Handlers\NotFound;
        };

        /**
         * This service MUST return a callable
         * that accepts three arguments:
         *
         * 1. Instance of \Psr\Http\Message\RequestInterface
         * 2. Instance of \Psr\Http\Message\ResponseInterface
         * 3. Array of allowed HTTP methods
         *
         * The callable MUST return an instance of
         * \Psr\Http\Message\ResponseInterface.
         */
        $this['notAllowedHandler'] = function ($c) {
            return new Handlers\NotAllowed;
        };
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
        try {
            return $this->offsetGet($id);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundException($e->getMessage(), $e->getCode(), $e);
        }
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
