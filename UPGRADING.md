# How to upgrade

* [2254] - You need to add the `Middleware\ContentLength` middleware if you want Slim to add this automatically.
* [2166] - You need to add the `Middleware\OutputBufferingMiddleware` middleware to capture echo'd or var_dump'd output from your code.
* [2098] - You need to add the App's router to the container for a straight upgrade. If you've created your own router factory in the container though, then you need to set it into the $app.
* [2102] - You must inject custom route invocation strategy with `$app->getRouter()->setDefaultInvocationStrategy($myStrategy)`

[2254]: https://github.com/slimphp/Slim/pull/2254
[2166]: https://github.com/slimphp/Slim/pull/2166
[2098]: https://github.com/slimphp/Slim/pull/2098
[2102]: https://github.com/slimphp/Slim/pull/2102
