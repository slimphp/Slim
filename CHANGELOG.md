# Changelog

# 4.7.0 - 2020-11-30

### Fixed
- [3027: Fix: FastRoute dispatcher and data generator should match](https://github.com/slimphp/Slim/pull/3027) thanks to @edudobay

### Added
- [3015: PHP 8 support](https://github.com/slimphp/Slim/pull/3015) thanks to @edudobay

### Optimizations
- [3024: Randomize tests](https://github.com/slimphp/Slim/pull/3024) thanks to @pawel-slowik

## 4.6.0 - 2020-11-15

### Fixed
- [2942: Fix PHPdoc for error handlers in ErrorMiddleware ](https://github.com/slimphp/Slim/pull/2942) thanks to @TiMESPLiNTER
- [2944: Remove unused function in ErrorHandler](https://github.com/slimphp/Slim/pull/2944) thanks to @l0gicgate
- [2960: Fix phpstan 0.12 errors](https://github.com/slimphp/Slim/pull/2960) thanks to @adriansuter
- [2982: Removing cloning statements in tests](https://github.com/slimphp/Slim/pull/2982) thanks to @l0gicgate
- [3017: Fix request creator factory test](https://github.com/slimphp/Slim/pull/3017) thanks to @pawel-slowik
- [3022: Ensure RouteParser Always Present After Routing](https://github.com/slimphp/Slim/pull/3022) thanks to @l0gicgate

### Added
- [2949: Add the support in composer.json](https://github.com/slimphp/Slim/pull/2949) thanks to @ddrv
- [2958: Strict empty string content type checking in BodyParsingMiddleware::getMediaType](https://github.com/slimphp/Slim/pull/2958) thanks to @Ayesh
- [2997: Add hints to methods](https://github.com/slimphp/Slim/pull/2997) thanks to @evgsavosin - [3000: Fix route controller test](https://github.com/slimphp/Slim/pull/3000) thanks to @pawel-slowik
- [3001: Add missing `$strategy` parameter in a Route test](https://github.com/slimphp/Slim/pull/3001) thanks to @pawel-slowik

### Optimizations
- [2951: Minor optimizations in if() blocks](https://github.com/slimphp/Slim/pull/2951) thanks to @Ayesh
- [2959: Micro optimization: Declare closures in BodyParsingMiddleware as static](https://github.com/slimphp/Slim/pull/2959) thanks to @Ayesh
- [2978: Split the routing results to its own function.](https://github.com/slimphp/Slim/pull/2978) thanks to @dlundgren

### Dependencies Updated
- [2953: Update nyholm/psr7-server requirement from ^0.4.1](https://github.com/slimphp/Slim/pull/2953) thanks to @dependabot-preview[bot]
- [2954: Update laminas/laminas-diactoros requirement from ^2.1 to ^2.3](https://github.com/slimphp/Slim/pull/2954) thanks to @dependabot-preview[bot]
- [2955: Update guzzlehttp/psr7 requirement from ^1.5 to ^1.6](https://github.com/slimphp/Slim/pull/2955) thanks to @dependabot-preview[bot]
- [2956: Update slim/psr7 requirement from ^1.0 to ^1.1](https://github.com/slimphp/Slim/pull/2956) thanks to @dependabot-preview[bot]
- [2957: Update nyholm/psr7 requirement from ^1.1 to ^1.2](https://github.com/slimphp/Slim/pull/2957) thanks to @dependabot-preview[bot]
- [2963: Update phpstan/phpstan requirement from ^0.12.23 to ^0.12.25](https://github.com/slimphp/Slim/pull/2963) thanks to @dependabot-preview[bot]
- [2965: Update adriansuter/php-autoload-override requirement from ^1.0 to ^1.1](https://github.com/slimphp/Slim/pull/2965) thanks to @dependabot-preview[bot]
- [2967: Update nyholm/psr7 requirement from ^1.2 to ^1.3](https://github.com/slimphp/Slim/pull/2967) thanks to @dependabot-preview[bot]
- [2969: Update nyholm/psr7-server requirement from ^0.4.1 to ^1.0.0](https://github.com/slimphp/Slim/pull/2969) thanks to @dependabot-preview[bot]
- [2970: Update phpstan/phpstan requirement from ^0.12.25 to ^0.12.26](https://github.com/slimphp/Slim/pull/2970) thanks to @dependabot-preview[bot]
- [2971: Update phpstan/phpstan requirement from ^0.12.26 to ^0.12.27](https://github.com/slimphp/Slim/pull/2971) thanks to @dependabot-preview[bot]
- [2972: Update phpstan/phpstan requirement from ^0.12.27 to ^0.12.28](https://github.com/slimphp/Slim/pull/2972) thanks to @dependabot-preview[bot]
- [2973: Update phpstan/phpstan requirement from ^0.12.28 to ^0.12.29](https://github.com/slimphp/Slim/pull/2973) thanks to @dependabot-preview[bot]
- [2975: Update phpstan/phpstan requirement from ^0.12.29 to ^0.12.30](https://github.com/slimphp/Slim/pull/2975) thanks to @dependabot-preview[bot]
- [2976: Update phpstan/phpstan requirement from ^0.12.30 to ^0.12.31](https://github.com/slimphp/Slim/pull/2976) thanks to @dependabot-preview[bot]
- [2980: Update phpstan/phpstan requirement from ^0.12.31 to ^0.12.32](https://github.com/slimphp/Slim/pull/2980) thanks to @dependabot-preview[bot]
- [2981: Update phpspec/prophecy requirement from ^1.10 to ^1.11](https://github.com/slimphp/Slim/pull/2981) thanks to @dependabot-preview[bot]
- [2986: Update phpstan/phpstan requirement from ^0.12.32 to ^0.12.33](https://github.com/slimphp/Slim/pull/2986) thanks to @dependabot-preview[bot]
- [2990: Update phpstan/phpstan requirement from ^0.12.33 to ^0.12.34](https://github.com/slimphp/Slim/pull/2990) thanks to @dependabot-preview[bot]
- [2991: Update phpstan/phpstan requirement from ^0.12.34 to ^0.12.35](https://github.com/slimphp/Slim/pull/2991) thanks to @dependabot-preview[bot]
- [2993: Update phpstan/phpstan requirement from ^0.12.35 to ^0.12.36](https://github.com/slimphp/Slim/pull/2993) thanks to @dependabot-preview[bot]
- [2995: Update phpstan/phpstan requirement from ^0.12.36 to ^0.12.37](https://github.com/slimphp/Slim/pull/2995) thanks to @dependabot-preview[bot]
- [3010: Update guzzlehttp/psr7 requirement from ^1.6 to ^1.7](https://github.com/slimphp/Slim/pull/3010) thanks to @dependabot-preview[bot]
- [3011: Update phpspec/prophecy requirement from ^1.11 to ^1.12](https://github.com/slimphp/Slim/pull/3011) thanks to @dependabot-preview[bot]
- [3012: Update slim/http requirement from ^1.0 to ^1.1](https://github.com/slimphp/Slim/pull/3012) thanks to @dependabot-preview[bot]
- [3013: Update slim/psr7 requirement from ^1.1 to ^1.2](https://github.com/slimphp/Slim/pull/3013) thanks to @dependabot-preview[bot]
- [3014: Update laminas/laminas-diactoros requirement from ^2.3 to ^2.4](https://github.com/slimphp/Slim/pull/3014) thanks to @dependabot-preview[bot]
- [3018: Update phpstan/phpstan requirement from ^0.12.37 to ^0.12.54](https://github.com/slimphp/Slim/pull/3018) thanks to @dependabot-preview[bot]

## 4.5.0 - 2020-04-14

### Added
- [2928](https://github.com/slimphp/Slim/pull/2928) Test against PHP 7.4
- [2937](https://github.com/slimphp/Slim/pull/2937) Add support for PSR-3

### Fixed
- [2916](https://github.com/slimphp/Slim/pull/2916) Rename phpcs.xml to phpcs.xml.dist
- [2917](https://github.com/slimphp/Slim/pull/2917) Update .editorconfig
- [2925](https://github.com/slimphp/Slim/pull/2925) ResponseEmitter: Don't remove Content-Type and Content-Length when body is empt
- [2932](https://github.com/slimphp/Slim/pull/2932) Update the Tidelift enterprise language
- [2938](https://github.com/slimphp/Slim/pull/2938) Modify usage of deprecated expectExceptionMessageRegExp() method

## 4.4.0 - 2020-01-04

### Added
- [2862](https://github.com/slimphp/Slim/pull/2862) Optionally handle subclasses of exceptions in custom error handler
- [2869](https://github.com/slimphp/Slim/pull/2869) php-di/php-di added in composer suggestion
- [2874](https://github.com/slimphp/Slim/pull/2874) Add `null` to param type-hints
- [2889](https://github.com/slimphp/Slim/pull/2889) Make `RouteContext` attributes customizable and change default to use private names
- [2907](https://github.com/slimphp/Slim/pull/2907) Migrate to PSR-12 convention
- [2910](https://github.com/slimphp/Slim/pull/2910) Migrate Zend to Laminas
- [2912](https://github.com/slimphp/Slim/pull/2912) Add Laminas PSR17 Factory
- [2913](https://github.com/slimphp/Slim/pull/2913) Update php-autoload-override version
- [2914](https://github.com/slimphp/Slim/pull/2914) Added ability to add handled exceptions as an array

### Fixed
- [2864](https://github.com/slimphp/Slim/pull/2864) Optimize error message in error handling if displayErrorDetails was not set
- [2876](https://github.com/slimphp/Slim/pull/2876) Update links from http to https
- [2877](https://github.com/slimphp/Slim/pull/2877) Fix docblock for `Slim\Routing\RouteCollector::cacheFile`
- [2878](https://github.com/slimphp/Slim/pull/2878) check body is writable only on ouput buffering append
- [2896](https://github.com/slimphp/Slim/pull/2896) Render errors uniformly
- [2902](https://github.com/slimphp/Slim/pull/2902) Fix prophecies
- [2908](https://github.com/slimphp/Slim/pull/2908) Use autoload-dev for `Slim\Tests` namespace

### Removed
- [2871](https://github.com/slimphp/Slim/pull/2871) Remove explicit type-hint
- [2872](https://github.com/slimphp/Slim/pull/2872) Remove type-hint

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
