# How to upgrade

* [2654] - `RouteParser::pathFor()` and `RouteParser::relativePathFor()` are deprecated. Use `RouteParser::urlFor()` and `RouteParser::relativeUrlFor()`
* [2638] - `RouteCollector::pathFor()` is now deprecated. Use `RouteParser::urlFor()`
* [2622] - `Router` has been removed. It is now split into `RouteCollector`, `RouteRunner` and `RouteParser`
* [2555] - PSR-15 Middleware support was implemented at the cost of Double-Pass middleware being deprecated.
* [2529] - Slim no longer ships with its own PSR-7 implementation you will need to provide your own before you can create/run an app.
* [2507] - Method names are now case sensitive when using `App::map()`.
* [2404] - Slim 4 requires PHP 7.1 or higher
* [2398] - Error handling was extracted into its own middleware. Add `RoutingMiddleware` to your middleware pipeline to handle errors by default. See PR for more information.
* [2329] - If you were overriding the HTTP method using either the custom header or the body param, you need to add the `Middleware\MethodOverrideMiddleware` middleware to be able to override the method like before.
* [2290] - Slim no longer ships with `Pimple` as container dependency so you need to supply your own. `App::__call()` has been deprecated.
* [2288] - If you were using `determineRouteBeforeAppMiddleware`, you need to add the `Middleware\RoutingMiddleware` middleware to your application just before your call `run()` to maintain the previous behaviour.
* [2254] - You need to add the `Middleware\ContentLengthMiddleware` middleware if you want Slim to add the Content-Length header this automatically.
* [2166] - You need to add the `Middleware\OutputBuffering` middleware to capture echo'd or var_dump'd output from your code.

[2654]: https://github.com/slimphp/Slim/pull/2654
[2638]: https://github.com/slimphp/Slim/pull/2638
[2622]: https://github.com/slimphp/Slim/pull/2622
[2555]: https://github.com/slimphp/Slim/pull/2555
[2529]: https://github.com/slimphp/Slim/pull/2529
[2507]: https://github.com/slimphp/Slim/pull/2507
[2496]: https://github.com/slimphp/Slim/pull/2496
[2404]: https://github.com/slimphp/Slim/pull/2404
[2398]: https://github.com/slimphp/Slim/pull/2398
[2329]: https://github.com/slimphp/Slim/pull/2329
[2290]: https://github.com/slimphp/Slim/pull/2290
[2288]: https://github.com/slimphp/Slim/pull/2288
[2254]: https://github.com/slimphp/Slim/pull/2254
[2166]: https://github.com/slimphp/Slim/pull/2166
