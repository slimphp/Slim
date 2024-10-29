<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Container;

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Slim\Configuration\Config;
use Slim\Emitter\ResponseEmitter;
use Slim\Error\Handlers\ExceptionHandler;
use Slim\Error\Renderers\HtmlExceptionRenderer;
use Slim\Error\Renderers\JsonExceptionRenderer;
use Slim\Error\Renderers\PlainTextExceptionRenderer;
use Slim\Error\Renderers\XmlExceptionRenderer;
use Slim\Interfaces\ConfigurationInterface;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Interfaces\EmitterInterface;
use Slim\Interfaces\ExceptionHandlerInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;
use Slim\Media\MediaType;
use Slim\Media\MediaTypeDetector;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Middleware\ExceptionHandlingMiddleware;
use Slim\Middleware\ExceptionLoggingMiddleware;
use Slim\RequestHandler\MiddlewareRequestHandler;
use Slim\Routing\Router;
use Slim\Routing\Strategies\RequestResponse;

/**
 * This class provides the default dependency definitions for a Slim application. It implements the
 * `__invoke()` method to return an array of service definitions that are used to set up the Slim
 * framework’s core components, including the application instance, middleware, request and response
 * factories, and other essential services.
 *
 * This class ensures that the Slim application can be properly instantiated with the necessary
 * components and services.
 */
final class DefaultDefinitions
{
    public function __invoke(): array
    {
        return [
            BodyParsingMiddleware::class => function (ContainerInterface $container) {
                $mediaTypeDetector = $container->get(MediaTypeDetector::class);
                $middleware = new BodyParsingMiddleware($mediaTypeDetector);

                return $middleware
                    ->withDefaultMediaType('text/html')
                    ->withDefaultBodyParsers();
            },

            Config::class => function (ContainerInterface $container) {
                return new Config($container->has('settings') ? (array)$container->get('settings') : []);
            },

            ConfigurationInterface::class => function (ContainerInterface $container) {
                return $container->get(Config::class);
            },

            ContainerResolverInterface::class => function (ContainerInterface $container) {
                return $container->get(ContainerResolver::class);
            },

            EmitterInterface::class => function () {
                return new ResponseEmitter();
            },

            ExceptionHandlingMiddleware::class => function (ContainerInterface $container) {
                $handler = $container->get(ExceptionHandlerInterface::class);

                return (new ExceptionHandlingMiddleware())->withExceptionHandler($handler);
            },

            ExceptionHandlerInterface::class => function (ContainerInterface $container) {
                // Default exception handler
                $exceptionHandler = $container->get(ExceptionHandler::class);

                // Settings
                $displayErrorDetails = (bool)$container->get(ConfigurationInterface::class)
                    ->get('display_error_details', false);

                $exceptionHandler = $exceptionHandler
                    ->withDisplayErrorDetails($displayErrorDetails)
                    ->withDefaultMediaType(MediaType::TEXT_HTML);

                return $exceptionHandler
                    ->withoutHandlers()
                    ->withHandler(MediaType::APPLICATION_JSON, JsonExceptionRenderer::class)
                    ->withHandler(MediaType::TEXT_HTML, HtmlExceptionRenderer::class)
                    ->withHandler(MediaType::APPLICATION_XHTML_XML, HtmlExceptionRenderer::class)
                    ->withHandler(MediaType::APPLICATION_XML, XmlExceptionRenderer::class)
                    ->withHandler(MediaType::TEXT_XML, XmlExceptionRenderer::class)
                    ->withHandler(MediaType::TEXT_PLAIN, PlainTextExceptionRenderer::class);
            },

            ExceptionLoggingMiddleware::class => function (ContainerInterface $container) {
                // Default logger
                $logger = $container->get(LoggerInterface::class);
                $middleware = new ExceptionLoggingMiddleware($logger);

                // Read settings
                $logErrorDetails = (bool)$container->get(ConfigurationInterface::class)
                    ->get('log_error_details', false);

                return $middleware->withLogErrorDetails($logErrorDetails);
            },

            LoggerInterface::class => function () {
                return new NullLogger();
            },

            RequestHandlerInterface::class => function (ContainerInterface $container) {
                return $container->get(MiddlewareRequestHandler::class);
            },

            RequestHandlerInvocationStrategyInterface::class => function (ContainerInterface $container) {
                return $container->get(RequestResponse::class);
            },

            Router::class => function () {
                return new Router(new RouteCollector(new Std(), new GroupCountBased()));
            },
        ];
    }
}
