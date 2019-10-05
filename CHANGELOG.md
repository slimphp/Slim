# Changelog

## 4.3.0 - 2019-10-05

### Added
- [2819](https://github.com/slimphp/Slim/pull/2819) Added description to addRoutingMiddleware()
- [2820](https://github.com/slimphp/Slim/pull/2820) Update link to homepage in composer.json
- [2828](https://github.com/slimphp/Slim/pull/2828) Allow URIs with leading multi-slashes
- [2832](https://github.com/slimphp/Slim/pull/2832) Refactor `FastRouteDispatcher`
- [2835](https://github.com/slimphp/Slim/pull/2835) Rename `pathFor` to `urlFor` in docblock
- [2846](https://github.com/slimphp/Slim/pull/2846) Correcting the branch name as per issue-2843
- [2849](https://github.com/slimphp/Slim/pull/2849) Create class alias for FastRoute\RouteCollector
- [2855](https://github.com/slimphp/Slim/pull/2855) Add list of allowed methods to HttpMethodNotAllowedException
- [2860](https://github.com/slimphp/Slim/pull/2860) Add base path to `$request` and use `RouteContext` to read

### Fixed
- [2839](https://github.com/slimphp/Slim/pull/2839) Fix description for handler signature in phpdocs
- [2844](https://github.com/slimphp/Slim/pull/2844) Handle base path by routeCollector instead of RouteCollectorProxy
- [2845](https://github.com/slimphp/Slim/pull/2845) Fix composer scripts
- [2851](https://github.com/slimphp/Slim/pull/2851) Fix example of 'Hello World' app
- [2854](https://github.com/slimphp/Slim/pull/2854) Fix undefined property in tests

### Removed
- [2853](https://github.com/slimphp/Slim/pull/2853) Remove unused classes

## 4.2.0 - 2019-08-20

### Added
- [2787](https://github.com/slimphp/Slim/pull/2787) Add an advanced callable resolver
- [2791](https://github.com/slimphp/Slim/pull/2791) Add `inferPrivatePropertyTypeFromConstructor` to phpstan
- [2793](https://github.com/slimphp/Slim/pull/2793) Add ability to configure application via a container in `AppFactory`
- [2798](https://github.com/slimphp/Slim/pull/2798) Add PSR-7 Agnostic Body Parsing Middleware
- [2801](https://github.com/slimphp/Slim/pull/2801) Add `setLogErrorRenderer()` method to `ErrorHandler`
- [2807](https://github.com/slimphp/Slim/pull/2807) Add check for Slim callable notation if no resolver given
- [2803](https://github.com/slimphp/Slim/pull/2803) Add ability to emit non seekable streams in `ResponseEmitter`
- [2817](https://github.com/slimphp/Slim/pull/2817) Add the ability to pass in a custom `MiddlewareDispatcherInterface` to the `App`

### Fixed
- [2789](https://github.com/slimphp/Slim/pull/2789) Fix Cookie header detection in `ResponseEmitter`
- [2796](https://github.com/slimphp/Slim/pull/2796) Fix http message format
- [2800](https://github.com/slimphp/Slim/pull/2800) Fix null comparisons more clear in `ErrorHandler`
- [2802](https://github.com/slimphp/Slim/pull/2802) Fix incorrect search of a header in stack
- [2806](https://github.com/slimphp/Slim/pull/2806) Simplify `Route::prepare()` method argument preparation
- [2809](https://github.com/slimphp/Slim/pull/2809) Eliminate a duplicate code via HOF in `MiddlewareDispatcher`
- [2816](https://github.com/slimphp/Slim/pull/2816) Fix RouteCollectorProxy::redirect() bug

### Removed
- [2811](https://github.com/slimphp/Slim/pull/2811) Remove `DeferredCallable`

## 4.1.0 - 2019-08-06

### Added
- [#2779](https://github.com/slimphp/Slim/pull/2774) Add support for Slim callables `Class:method` resolution & Container Closure auto-binding in `MiddlewareDispatcher`
- [#2774](https://github.com/slimphp/Slim/pull/2774) Add possibility for custom `RequestHandler` invocation strategies

### Fixed
- [#2776](https://github.com/slimphp/Slim/pull/2774) Fix group middleware on multiple nested groups
