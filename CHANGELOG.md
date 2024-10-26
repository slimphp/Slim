# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

#### New Features

- New `AppBuilder` to create a Slim App instance for different scenarios. Replaces the `AppFactory`.
- Unified DI container resolution. All the factory logic has been removed and moved to the DI container. This reduces the internal complexity by delegating the building logic into the DI container.
- Provide FIFO (first in, first out) middleware order support. LIFO is not supported anymore.
- Optimized internal routing concept for better separation of concern and flexibility.
    - `RoutingMiddleware` handles the routing process.
    - `EndpointMiddleware` processes the routing results and invokes the controller/action handler.
- Simplified Error handling concept. Relates to #3287.
  - Separation of Exceptions handling, PHP Error handling and Exception logging into different middleware.
  - `ExceptionLoggingMiddleware` for custom error logging.
  - `ExceptionHandlingMiddleware` delegates exceptions to a custom error handler.
  - `ErrorHandlingMiddleware` converts errors into `ErrorException` instances that can then be handled by the `ExceptionHandlingMiddleware` and `ExceptionLoggingMiddleware`.
  - New custom error handlers using a new `ExceptionHandlerInterface`. See new `ExceptionHandlingMiddleware`.
  - New `JsonExceptionRenderer` generates a JSON problem details (rfc7807) response
  - New `XmlExceptionRenderer` generates a XML problem details (rfc7807) response
- New `BasePathMiddleware` for dealing with Apache subdirectories.
- New `HeadMethodMiddleware` ensures that the response body is empty for HEAD requests.
- New `JsonRenderer` utility class for rendering JSON responses.
- New `RequestResponseTypedArgs` invocation strategy for route parameters with type declarations.
- New `UrlGeneratorMiddleware` injects the `UrlGenerator` into the request attributes.
- New `CorsMiddleware` for handling CORS requests.
- Support to build a custom middleware pipeline without the Slim App class. See new `ResponseFactoryMiddleware`
- New media type detector
- New Config class and ConfigInterface

### Changed

* Require PHP 8.2 or 8.3. News versions will be supported after a review and test process.
* Migrated all tests to PHPUnit 11
* Update GitHub action and build settings
* Improve DI container integration. Make the DI container a first-class citizen. Require a PSR-11 package.
* Ensure that route attributes are always in the Request. Related to #3280. See new `RoutingArgumentsMiddleware`.
* Unify `CallableResolver` and `AdvancedCallableResolver`. Resolved with the new CallableResolver. Relates to #3073.
- PSR-7 and PSR-15 compliance: Require at least psr/http-message 2.0.
- PSR-11 compliance: Require at least psr/container 2.0.
- PSR-3 compliance: Require at least psr/log 3.0
- The `App` class is not a request handler that implements the `RequestHandlerInterface` because the request handler is now used internally and must be "unique" within the DI container.

### Removed

* Remove LIFO middleware order support. Use FIFO instead.
* Router cache file support (File IO was never sufficient. PHP OpCache is much faster)
* The `$app->redirect()` method because it was not aware of the basePath. Use the `UrlGenerator` instead.
* The route `setArguments` and `setArgument` methods. Use a middleware for custom route arguments now.
* The `RouteContext::ROUTE` const. Use `$route = $request->getAttribute(RouteContext::ROUTING_RESULTS)->getRoute();` instead.
* Old tests for PHP 7
* Psalm
* phpspec/prophecy
* phpspec/prophecy-phpunit

### Fixed

- Resolving middleware breaks if resolver throws unexpected exception type #3071. Resolved with the new CallableResolver.
- Forward logger to own `ErrorHandlingMiddleware` #2943. See new `ExceptionLoggingMiddleware`.
- Code styles (PSR-12)

## Files

### Added

- `Slim/Builder/AppBuilder.php`: Introduced to replace `Slim/Factory/AppFactory.php`.
- `Slim/Container/CallableResolver.php`: New implementation of the Callable Resolver.
- `Slim/Container/DefaultDefinitions.php`: Default container definitions.
- `Slim/Handlers/ExceptionHandler.php`: New Exception Handler for better error handling.
- `Slim/Handlers/ExceptionRendererTrait.php`: Common functionality for exception renderers.
- `Slim/Handlers/HtmlExceptionRenderer.php`: HTML-based exception renderer.
- `Slim/Handlers/JsonExceptionRenderer.php`: JSON-based exception renderer.
- `Slim/Handlers/XmlExceptionRenderer.php`: XML-based exception renderer.
- `Slim/Interfaces/ExceptionRendererInterface.php`: New interface for exception renderers.
- `Slim/Logging/StdErrorLogger.php`: Logger that outputs to stderr.
- `Slim/Logging/StdOutLogger.php`: Logger that outputs to stdout.
- `Slim/Middleware/ErrorHandlingMiddleware.php`: Middleware for handling errors.
- `Slim/Middleware/ExceptionHandlingMiddleware.php`: Middleware for handling exceptions.
- `Slim/Middleware/ExceptionLoggingMiddleware.php`: Middleware for logging exceptions.
- `Slim/Middleware/ResponseFactoryMiddleware.php`: Middleware for response creation.
- `Slim/Middleware/UrlGeneratorMiddleware.php`: Middleware for URL generation.
- `Slim/Renderers/JsonRenderer.php`: Renderer for JSON responses.
- `Slim/RequestHandler/MiddlewareRequestHandler.php`: Handles requests through middleware.
- `Slim/RequestHandler/MiddlewareResolver.php`: Resolves middleware for handling requests.
- `Slim/RequestHandler/Runner.php`: Handles the execution flow of requests.
- `Slim/Strategies/RequestResponseNamedArgs.php`: New strategy for named arguments in RequestResponse.
- `Slim/Strategies/RequestResponseTypedArgs.php`: New strategy for typed arguments in RequestResponse. Requires `php-di/invoker`.

New files for routing, middleware, and factories, including:

- `Slim/Interfaces/EmitterInterface.php`
- `Slim/Middleware/BasePathMiddleware.php`
- `Slim/Routing/Router.php`, `RouteGroup.php`, `UrlGenerator.php`

### Changed

- `Slim/Interfaces/ErrorHandlerInterface.php` renamed to `Slim/Interfaces/ExceptionHandlerInterface.php`.
- `Slim/Interfaces/RouteParserInterface.php` renamed to `Slim/Interfaces/UrlGeneratorInterface.php`.
- `Slim/Handlers/Strategies/RequestResponse.php` renamed to `Slim/Strategies/RequestResponse.php`.
- `Slim/Handlers/Strategies/RequestResponseArgs.php` renamed to `Slim/Strategies/RequestResponseArgs.php`.
- `Slim/Error/Renderers/PlainTextErrorRenderer.php` renamed to `Slim/Handlers/PlainTextExceptionRenderer.php`.
- `Slim/Routing/RouteContext.php`

### Removed

- `Slim/CallableResolver.php`
- `Slim/Handlers/ErrorHandler.php`
- `Slim/Factory/AppFactory.php` and related `Psr17` factories.
- `Slim/Interfaces/AdvancedCallableResolverInterface.php`
- `Slim/Interfaces/RouteCollectorInterface.php`, 
- `RouteCollectorProxyInterface.php`, 
- `RouteGroupInterface.php`, and other route-related interfaces.
- `Slim/Routing/Dispatcher.php`, `FastRouteDispatcher.php` and related routing classes.

