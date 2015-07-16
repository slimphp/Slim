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
 * Slim's class referencing configurable services.
 *
 * You can use this class to pass special services to Slim\App. If you do not set special services,
 * those services will be used instead:
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
class Configuration implements ConfigurationInterface
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

    /**
     * @var array $userSettings Associative array of application settings
     */
    private $userSettings;

    /**
     * @var EnvironmentInterface
     */
    private $environment;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var InvocationStrategyInterface
     */
    private $foundHandler;

    /**
     * @var callable
     */
    private $errorHandler;

    /**
     * @var callable
     */
    private $notFoundHandler;

    /**
     * @var callable
     */
    private $notAllowedHandler;

    /**
     * @var CallableResolverInterface
     */
    private $callableResolver;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Create new container
     *
     * @param array $userSettings Associative array of application settings
     */
    public function __construct(array $userSettings = [])
    {
        $this->setSettings($userSettings);
    }

    /**
     * @return mixed
     */
    public function getSettings()
    {
        return $this->userSettings;
    }

    /**
     * @param mixed $userSettings
     */
    public function setSettings($userSettings)
    {
        $this->userSettings = array_merge($this->defaultSettings, $userSettings);
    }

    /**
     * @return EnvironmentInterface
     */
    public function getEnvironment()
    {
        if ($this->environment === null) {
            $this->environment = new Environment($_SERVER);
        }
        return $this->environment;
    }

    /**
     * @param EnvironmentInterface $environment
     */
    public function setEnvironment(EnvironmentInterface $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        if ($this->request === null) {
            $this->request = Request::createFromEnvironment($this->getEnvironment());
        }
        return $this->request;
    }

    /**
     * @param ServerRequestInterface $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        if ($this->response === null) {
            $headers = new Headers(['Content-Type' => 'text/html']);
            $this->response = new Response(200, $headers);
            $this->response->withProtocolVersion($this->getSettings()['httpVersion']);
        }
        return $this->response;
    }

    /**
     * @param mixed $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return mixed
     */
    public function getRouter()
    {
        if ($this->router === null) {
            $this->router = new Router();
        }
        return $this->router;
    }

    /**
     * @param mixed $router
     */
    public function setRouter($router)
    {
        $this->router = $router;
    }

    /**
     * @return InvocationStrategyInterface
     */
    public function getFoundHandler()
    {
        if ($this->foundHandler === null) {
            $this->foundHandler = new RequestResponse();
        }
        return $this->foundHandler;
    }

    /**
     * @param InvocationStrategyInterface $foundHandler
     */
    public function setFoundHandler($foundHandler)
    {
        $this->foundHandler = $foundHandler;
    }

    /**
     * @return callable
     */
    public function getErrorHandler()
    {
        if ($this->errorHandler === null) {
            $this->errorHandler = new Error();
        }
        return $this->errorHandler;
    }

    /**
     * @param callable $errorHandler
     */
    public function setErrorHandler($errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * @return callable
     */
    public function getNotFoundHandler()
    {
        if ($this->notFoundHandler === null) {
            $this->notFoundHandler = new NotFound();
        }
        return $this->notFoundHandler;
    }

    /**
     * @param callable $notFoundHandler
     */
    public function setNotFoundHandler($notFoundHandler)
    {
        $this->notFoundHandler = $notFoundHandler;
    }

    /**
     * @return callable
     */
    public function getNotAllowedHandler()
    {
        if ($this->notAllowedHandler === null) {
            $this->notAllowedHandler = new NotAllowed();
        }
        return $this->notAllowedHandler;
    }

    /**
     * @param callable $notAllowedHandler
     */
    public function setNotAllowedHandler($notAllowedHandler)
    {
        $this->notAllowedHandler = $notAllowedHandler;
    }

    /**
     * @return CallableResolverInterface
     */
    public function getCallableResolver()
    {
        if ($this->callableResolver === null) {
            $this->callableResolver = new CallableResolver($this->getContainer());
        }
        return $this->callableResolver;
    }

    /**
     * @param CallableResolverInterface $callableResolver
     */
    public function setCallableResolver($callableResolver)
    {
        $this->callableResolver = $callableResolver;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param ContainerInterface $container
     * @return Configuration
     */
    public function setContainer($container)
    {
        $this->container = $container;
        return $this;
    }
}
