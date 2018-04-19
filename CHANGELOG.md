# Changelog


## 4.0.0 - TBD

### Added

- [#2425](https://github.com/slimphp/Slim/pull/2425) Added $app->redirect()
- [#2398](https://github.com/slimphp/Slim/pull/2398) Added Middleware\ErrorMiddleware
- [#2329](https://github.com/slimphp/Slim/pull/2329) Added Middleware\MethodOverrideMiddleware
- [#2288](https://github.com/slimphp/Slim/pull/2288) Separate routing from dispatching
- [#2254](https://github.com/slimphp/Slim/pull/2254) Added Middleware\ContentLengthMiddleware
- [#2166](https://github.com/slimphp/Slim/pull/2166) Added Middleware\OutputBufferingMiddleware

### Deprecated

- Nothing.

### Removed

- [#2398](https://github.com/slimphp/Slim/pull/2398) Slim no longer has error handling built into App. Add ErrorMiddleware() as the outermost middleware.
- [#2375](https://github.com/slimphp/Slim/pull/2375) Slim no longer sets the `default_mimetype` to an empty string, so you need to set it yourself in php.ini or your app.
- [#2288](https://github.com/slimphp/Slim/pull/2288) `determineRouteBeforeAppMiddleware` setting is removed. Add RoutingMiddleware() where you need it now.
- [#2254](https://github.com/slimphp/Slim/pull/2254) `addContentLengthHeader` setting is removed
- [#2166](https://github.com/slimphp/Slim/pull/2166) `outputBuffering` setting is removed
- [#2067](https://github.com/slimphp/Slim/pull/2067) Remove App::VERSION
- [#2078](https://github.com/slimphp/Slim/pull/2078) Remove App::subRequest()
- [#2098](https://github.com/slimphp/Slim/pull/2098) Remove CallableResolverTrait
- [#2102](https://github.com/slimphp/Slim/pull/2102) Remove container from router
- [#2124](https://github.com/slimphp/Slim/pull/2124) Remove Slim\Exception\SlimException
- [#2174](https://github.com/slimphp/Slim/pull/2174) Switch from Container-Interop to PSR-11
- [#2290](https://github.com/slimphp/Slim/pull/2290) Removed container. Set your own using `App::setContainer()`

### Fixed

- [#2067](https://github.com/slimphp/Slim/pull/2067) Unit tests now pass on Windows systems

