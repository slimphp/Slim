<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\ContainerValueNotFoundException;
use Slim\Handlers\PhpError;
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
 * Slim's default Service Provider.
 */
class DefaultServicesProvider
{
    /**
     * Register Slim's default services.
     *
     * @param Container $container A DI container implementing ArrayAccess and container-interop.
     */
    public function register($container)
    {
        if (!isset($container['environment'])) {
            /**
             * This service MUST return a shared instance
             * of \Slim\Interfaces\Http\EnvironmentInterface.
             *
             * @return EnvironmentInterface
             */
            $container['environment'] = function () {
                trigger_error(
                    'Retrieving the environment from the container is deprecated; '
                    . 'update your code to use the one within the Request object.',
                    E_USER_DEPRECATED
                );
                return new Environment($_SERVER);
            };
        }

        if (!isset($container['request'])) {
            /**
             * PSR-7 Request object
             *
             * @param Container $container
             *
             * @return ServerRequestInterface
             */
            $container['request'] = function ($container) {
                trigger_error(
                    'Retrieving the request from the container is deprecated; '
                    . 'update your code to use the Request object that is passed through the middleware. '
                    . 'To use your own Request object, pass it in as the second parameter to run().',
                    E_USER_DEPRECATED
                );
                return Request::createFromEnvironment($container->get('environment'));
            };
        }

        if (!isset($container['response'])) {
            /**
             * PSR-7 Response object
             *
             * @param Container $container
             *
             * @return ResponseInterface
             */
            $container['response'] = function ($container) {
                trigger_error(
                    'Retrieving the response from the container is deprecated; '
                    . 'update your code to use the Response object that is passed through the middleware. '
                    . 'To use your own Response object, pass it in as the third parameter to run().',
                    E_USER_DEPRECATED
                );

                $headers = new Headers(['Content-Type' => 'text/html; charset=UTF-8']);
                $response = new Response(200, $headers);

                return $response->withProtocolVersion($container->get('settings')['httpVersion']);
            };
        }

        if (!isset($container['router'])) {
            /**
             * This service MUST return a SHARED instance
             * of \Slim\Interfaces\RouterInterface.
             *
             * @return RouterInterface
             */
            $container['router'] = function () {
                return new Router;
            };
        }

        if (!isset($container['foundHandler'])) {
            /**
             * This service MUST return a SHARED instance
             * of \Slim\Interfaces\InvocationStrategyInterface.
             *
             * @return InvocationStrategyInterface
             */
            $container['foundHandler'] = function () {
                return new RequestResponse;
            };
        }

        if (!isset($container['phpErrorHandler'])) {
            /**
             * This service MUST return a callable
             * that accepts three arguments:
             *
             * 1. Instance of \Psr\Http\Message\ServerRequestInterface
             * 2. Instance of \Psr\Http\Message\ResponseInterface
             * 3. Instance of \Error
             *
             * The callable MUST return an instance of
             * \Psr\Http\Message\ResponseInterface.
             *
             * @param Container $container
             *
             * @return callable
             */
            $container['phpErrorHandler'] = function ($container) {
                return new PhpError($container->get('settings')['displayErrorDetails']);
            };
        }

        if (!isset($container['errorHandler'])) {
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
             * @param Container $container
             *
             * @return callable
             */
            $container['errorHandler'] = function ($container) {
                return new Error($container->get('settings')['displayErrorDetails']);
            };
        }

        if (!isset($container['notFoundHandler'])) {
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
            $container['notFoundHandler'] = function () {
                return new NotFound;
            };
        }

        if (!isset($container['notAllowedHandler'])) {
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
            $container['notAllowedHandler'] = function () {
                return new NotAllowed;
            };
        }

        if (!isset($container['callableResolver'])) {
            /**
             * Instance of \Slim\Interfaces\CallableResolverInterface
             *
             * @param Container $container
             *
             * @return CallableResolverInterface
             */
            $container['callableResolver'] = function ($container) {
                return new CallableResolver($container);
            };
        }
    }
}
