# Changelog

## 4.0.0 - 2019-08-01

### Added
- [#2765](https://github.com/slimphp/Slim/pull/2765) Simplify queueing of internal middleware `ErrorMiddleware` and `RoutingMiddleware`
- [#2759](https://github.com/slimphp/Slim/pull/2759) Add `RouteContext` to enable access to the current route, route parser, and routing results
- [#2751](https://github.com/slimphp/Slim/pull/2751) Add ability to set default error renderer content type on the `ErrorHandler` component
- [#2737](https://github.com/slimphp/Slim/pull/2737) BC Change via `ErrorRendererInterface` changed to use invokable pattern to leverage `CallableResolver`
- [#2734](https://github.com/slimphp/Slim/pull/2734) Add support for content-type base error renderers and setting default error renderer
- [#2716](https://github.com/slimphp/Slim/pull/2716) Add automatic Slim-Http Request/Response decoration when library is in scope
- [#2654](https://github.com/slimphp/Slim/pull/2654) `RouteParser::pathFor()` and `RouteParser::relativePathFor()` are deprecated. Use `RouteParser::urlFor()` and `RouteParser::relativeUrlFor()`
- [#2642](https://github.com/slimphp/Slim/pull/2642) Add `AppFactory` to enable PSR-7 implementation and ServerRequest creator auto-detection
- [#2641](https://github.com/slimphp/Slim/pull/2641) Add `RouteCollectorProxyInterface` which extracts all the route mapping functionality from app into its own interface
- [#2640](https://github.com/slimphp/Slim/pull/2640) Add `RouteParserInterface` and decouple FastRoute route parser entirely from core. The methods `relativePathFor()`, `urlFor()` and `fullUrlFor()` are now located on this interface
- [#2639](https://github.com/slimphp/Slim/pull/2639) Add `DispatcherInterface` and decouple FastRoute dispatcher entirely from core. This enables us to swap out our router implementation for any other router
- [#2638](https://github.com/slimphp/Slim/pull/2638) Add `RouteCollector::fullUrlFor()` to give the ability to generate fully qualified URLs
- [#2634](https://github.com/slimphp/Slim/pull/2634) Added ability to set invocation strategy on a per-route basis
- [#2555](https://github.com/slimphp/Slim/pull/2555) Added PSR-15 Middleware Support
- [#2529](https://github.com/slimphp/Slim/pull/2529) Slim no longer ships with a PSR-7 implementation. You need to provide a PSR-7 ServerRequest and a PSR-17 ResponseFactory to run Slim
- [#2507](https://github.com/slimphp/Slim/pull/2507) Method names are now case-sensitive in Router::map(), and so, by extension, in App::map() 
- [#2497](https://github.com/slimphp/Slim/pull/2497) PSR-15 RequestHandlers can now be used as route callables
- [#2496](https://github.com/slimphp/Slim/pull/2496) A Slim App can now be used as PSR-15 Request Handler
- [#2405](https://github.com/slimphp/Slim/pull/2405) RoutingMiddleware now adds the `routingResults` request attribute to hold the results of routing
- [#2404](https://github.com/slimphp/Slim/pull/2404) Slim 4 requires PHP 7.1 or higher
- [#2425](https://github.com/slimphp/Slim/pull/2425) Added $app->redirect()
- [#2398](https://github.com/slimphp/Slim/pull/2398) Added Middleware\ErrorMiddleware
- [#2329](https://github.com/slimphp/Slim/pull/2329) Added Middleware\MethodOverrideMiddleware
- [#2288](https://github.com/slimphp/Slim/pull/2288) Separate routing from dispatching
- [#2254](https://github.com/slimphp/Slim/pull/2254) Added Middleware\ContentLengthMiddleware
- [#2166](https://github.com/slimphp/Slim/pull/2166) Added Middleware\OutputBufferingMiddleware

### Deprecated

- [#2641](https://github.com/slimphp/Slim/pull/2641) Deprecate `RouteCollector::pushGroup()`,`RouteCollector::popGroup()` which gets replaced by `RouteCollector::group()`
- [#2638](https://github.com/slimphp/Slim/pull/2638) Deprecate `RouteCollector::pathFor()` which gets replaced by `RouteCollector::urlFor()` preserving the orignal functionality
- [#2555](https://github.com/slimphp/Slim/pull/2555) Double-Pass Middleware Support has been deprecated

### Removed

- [#2612](https://github.com/slimphp/Slim/pull/2612) Remove Routable, refactored RouteGroup and Route interface
- [#2589](https://github.com/slimphp/Slim/pull/2589) Remove App::$settings altogether
- [#2587](https://github.com/slimphp/Slim/pull/2587) Remove Pimple as a dev-dependency
- [#2398](https://github.com/slimphp/Slim/pull/2398) Slim no longer has error handling built into App. Add ErrorMiddleware() as the outermost middleware
- [#2375](https://github.com/slimphp/Slim/pull/2375) Slim no longer sets the `default_mimetype` to an empty string, so you need to set it yourself in php.ini or your app using `ini_set('default_mimetype', '');`
- [#2288](https://github.com/slimphp/Slim/pull/2288) `determineRouteBeforeAppMiddleware` setting is removed. Add RoutingMiddleware() where you need it now
- [#2254](https://github.com/slimphp/Slim/pull/2254) `addContentLengthHeader` setting is removed
- [#2221](https://github.com/slimphp/Slim/pull/2221) `Slim\Http` has been removed and Slim now depends on the separate Slim-Http component
- [#2166](https://github.com/slimphp/Slim/pull/2166) `outputBuffering` setting is removed
- [#2078](https://github.com/slimphp/Slim/pull/2078) Remove App::subRequest()
- [#2098](https://github.com/slimphp/Slim/pull/2098) Remove CallableResolverTrait
- [#2102](https://github.com/slimphp/Slim/pull/2102) Remove container from router
- [#2124](https://github.com/slimphp/Slim/pull/2124) Remove Slim\Exception\SlimException
- [#2174](https://github.com/slimphp/Slim/pull/2174) Switch from Container-Interop to PSR-11
- [#2290](https://github.com/slimphp/Slim/pull/2290) Removed container. Set your own using `App::setContainer()`
- [#2560](https://github.com/slimphp/Slim/pull/2560) Remove binding of $this to group()

### Changed

- [#2104](https://github.com/slimphp/Slim/pull/2104) Settings are the top level array elements in `App::__construct()`

### Fixed
- [#2588](https://github.com/slimphp/Slim/pull/2588) Fix file/directory permission handling of `Router::setCacheFile()`
- [#2067](https://github.com/slimphp/Slim/pull/2067) Unit tests now pass on Windows systems
- [#2405](https://github.com/slimphp/Slim/pull/2405) We rawurldecode() the path before passing to FastRoute, so UTF-8 characters in paths should now work

