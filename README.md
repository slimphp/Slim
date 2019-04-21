# Slim Framework

[![Build Status](https://travis-ci.org/slimphp/Slim.svg?branch=4.x)](https://travis-ci.org/slimphp/Slim)
[![Coverage Status](https://coveralls.io/repos/github/slimphp/Slim/badge.svg?branch=4.x)](https://coveralls.io/github/slimphp/Slim?branch=4.x)
[![Total Downloads](https://poser.pugx.org/slim/slim/downloads)](https://packagist.org/packages/slim/slim)
[![License](https://poser.pugx.org/slim/slim/license)](https://packagist.org/packages/slim/slim)

Slim is a PHP micro-framework that helps you quickly write simple yet powerful web applications and APIs.

## Installation

It's recommended that you use [Composer](https://getcomposer.org/) to install Slim.

```bash
$ composer require "slim/slim:4.x-dev"
```

This will install Slim and all required dependencies. Slim requires PHP 7.1 or newer.

## Choose a PSR-7 Implementation & ServerRequest Creator

Before you can get up and running with Slim you will need to choose a PSR-7 implementation that best fits your application. A few notable ones:
- [Slim-Psr7](https://github.com/slimphp/Slim-Psr7) - This is the Slim Framework projects PSR-7 implementation.
- [Nyholm/psr7](https://github.com/Nyholm/psr7) & [Nyholm/psr7-server](https://github.com/Nyholm/psr7-server) - This is the fastest, strictest and most lightweight implementation at the moment
- [Guzzle/psr7](https://github.com/guzzle/psr7) & [http-interop/http-factory-guzzle](https://github.com/http-interop/http-factory-guzzle) - This is the implementation used by the Guzzle Client. It is not as strict but adds some nice functionality for Streams and file handling. It is the second fastest implementation but is a bit bulkier
- [zend-diactoros](https://github.com/zendframework/zend-diactoros) - This is the Zend implementation. It is the slowest implementation of the 3.


## Slim-Http Decorators

[Slim-Http](https://github.com/slimphp/Slim-Http) is a set of decorators for any PSR-7 implementation that we recommend is used with Slim Framework.

## Hello World using PSR-7 & ServerRequest creator auto detection
In order for auto-detection to work and enable you to use `AppFactory::create()` and `App::run()` without having to manually create a `ServerRequest` you need to install one of the following implementations:
- [Slim-Psr7](https://github.com/slimphp/Slim-Psr7) - Install using `composer require slim/psr7:dev-master`
- [Nyholm/psr7](https://github.com/Nyholm/psr7) & [Nyholm/psr7-server](https://github.com/Nyholm/psr7-server) - Install using `composer require nyholm/psr-7 nyholm/psr7-server`
- [Guzzle/psr7](https://github.com/guzzle/psr7) & [http-interop/http-factory-guzzle](https://github.com/http-interop/http-factory-guzzle) - `composer require guzzlehttp/psr7 http-interop/http-factory-guzzle`
- [zend-diactoros](https://github.com/zendframework/zend-diactoros) - Install using `zendframework/zend-diactoros`

```php
<?php
require 'vendor/autoload.php';

use Slim\AppFactory;

/**
 * The AppFactory::create() method takes 5 optional parameters
 * 
 * @param ResponseFactoryInterface Any implementation of a ResponseFactory
 * @param ContainerInterface|null Any implementation of a Container
 * @param CallableResolverInterface|null Any implementation of a CallableResolver
 * @param RouteCollectorInterface|null Any implementation of a RouteCollector
 * @param RouteResolverInterface|null Any implementation of a RouteResolverInterface
 */
$app = AppFactory::create();
$app->get('/hello/{name}', function ($request, $response, $args) {
    $response->getBody()->write("Hello, " . $args['name']);
    return $response;
});

$app->run();
```

## Hello World with Slim-Http & Slim-Psr7

```php
<?php
// composer require slim/slim:4.x-dev slim/psr7:dev-master slim/http
use Slim\App;
use Slim\Http\Factory\DecoratedResponseFactory;
use Slim\Http\ServerRequest;
use Slim\Middleware\ErrorMiddleware;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

$responseFactory = new DecoratedResponseFactory(new ResponseFactory(), new StreamFactory());
$app = new App($responseFactory);

// Add middleware (LIFO stack)
$errorMiddleware = new ErrorMiddleware($app->getCallableResolver(), $responseFactory, true, true, true);
$app->add($errorMiddleware);

// Action
$app->get('/hello/{name}', function ($request, $response, $args) {
    return $response->getBody()->write("Hello, " . $args['name']);
});

$request = new ServerRequest(ServerRequestFactory::createFromGlobals());
$app->run($request);
```

## Hello World with Nyholm/psr7 and Nyholm/psr7-server
```php
<?php
require 'vendor/autoload.php';

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

/**
 * We need to instantiate our factories before instantiating Slim\App
 * In the case of Nyholm/psr7 the Psr17Factory provides all the Http-Factories in one class
 * which includes ResponseFactoryInterface
 */
$psr17Factory = new Psr17Factory();
$serverRequestFactory = new ServerRequestCreator(
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
    $psr17Factory
);

/**
 * The App::__constructor() method takes 1 mandatory parameter and 4 optional parameters
 * 
 * @param ResponseFactoryInterface Any implementation of a ResponseFactory
 * @param ContainerInterface|null Any implementation of a Container
 * @param CallableResolverInterface|null Any implementation of a CallableResolver
 * @param RouteCollectorInterface|null Any implementation of a RouteCollector
 * @param RouteResolverInterface|null Any implementation of a RouteResolverInterface
 */
$app = new Slim\App($psr17Factory);
$app->get('/hello/{name}', function ($request, $response, $args) {
    $response->getBody()->write("Hello, " . $args['name']);
    return $response;
});

/**
 * The App::run() method takes 1 parameter
 * 
 * @param ServerRequestInterface An instantiation of a ServerRequest
 */
$request = $serverRequestFactory->fromGlobals();
$app->run($request);
```

## Hello World with Zend Diactoros & Zend HttpHandleRunner Response Emitter
```php
<?php
require 'vendor/autoload.php';

use Zend\Diactoros\ResponseFactory;
use Zend\Diactoros\ServerRequestFactory;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;

$responseFactory = new ResponseFactory();
$serverRequestFactory = new ServerRequestFactory();

/**
 * The App::__constructor() method takes 1 mandatory parameter and 4 optional parameters
 * @param ResponseFactoryInterface Any implementation of a ResponseFactory
 * @param ContainerInterface|null Any implementation of a Container
 * @param CallableResolverInterface|null Any implementation of a CallableResolver
 * @param RouteCollectorInterface|null Any implementation of a RouteCollector
 * @param RouteResolverInterface|null Any implementation of a RouteResolverInterface
 */
$app = new Slim\App($responseFactory);
$app->get('/hello/{name}', function ($request, $response, $args) {
    $response->getBody()->write("Hello, " . $args['name']);
    return $response;
});

/**
 * The App::handle() method takes 1 parameter
 * Note we are using handle() and not run() since we want to emit the response using Zend's Response Emitter
 * 
 * @param ServerRequestInterface An instantiation of a ServerRequest
 */
$request = ServerRequestFactory::fromGlobals();
$response = $app->handle($request);

/**
 * Once you have obtained the ResponseInterface from App::handle()
 * You will need to emit the response by using an emitter of your choice
 * We will use Zend HttpHandleRunner SapiEmitter for this example
 */
$responseEmitter = new SapiEmitter();
$responseEmitter->emit($response);
```

## Hello World with Slim-Http Decorators and Zend Diactoros
```php
<?php
require 'vendor/autoload.php';

use Slim\Http\Factory\DecoratedResponseFactory;
use Slim\Http\Decorators\ServerRequestDecorator;
use Zend\Diactoros\ResponseFactory;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\StreamFactory;

$responseFactory = new ResponseFactory();
$streamFactory = new StreamFactory();
$decoratedResponseFactory = new DecoratedResponseFactory($responseFactory, $streamFactory);
$serverRequestFactory = new ServerRequestFactory();

/**
 * The App::__constructor() method takes 1 mandatory parameter and 4 optional parameters
 * Note that we pass in the decorated response factory which will give us access to the Slim\Http
 * decorated Response methods like withJson()
 * 
 * @param ResponseFactoryInterface Any implementation of a ResponseFactory
 * @param ContainerInterface|null Any implementation of a Container
 * @param CallableResolverInterface|null Any implementation of a CallableResolver
 * @param RouteCollectorInterface|null Any implementation of a RouteCollector
 * @param RouteResolverInterface|null Any implementation of a RouteResolverInterface
 */
$app = new Slim\App($decoratedResponseFactory);
$app->get('/hello/{name}', function ($request, $response, $args) {
    return $response->withJson(['Hello' => 'World']);
});

/**
 * The App::run() method takes 1 parameter
 * Note that we pass in the decorated server request object which will give us access to the Slim\Http
 * decorated ServerRequest methods like withRedirect()
 * 
 * @param ServerRequestInterface An instantiation of a ServerRequest
 */
$request = ServerRequestFactory::fromGlobals();
$decoratedServerRequest = new ServerRequestDecorator($request);
$app->run($decoratedServerRequest);
```

## Hello World with Guzzle PSR-7 and Guzzle HTTP Factory
```php
<?php
require 'vendor/autoload.php';

use GuzzleHttp\Psr7\ServerRequest;
use Http\Factory\Guzzle\ResponseFactory;

$responseFactory = new ResponseFactory();

/**
 * The App::__constructor() method takes 1 mandatory parameter and 4 optional parameters
 * 
 * @param ResponseFactoryInterface Any implementation of a ResponseFactory
 * @param ContainerInterface|null Any implementation of a Container
 * @param CallableResolverInterface|null Any implementation of a CallableResolver
 * @param RouteCollectorInterface|null Any implementation of a RouteCollector
 * @param RouteResolverInterface|null Any implementation of a RouteResolverInterface
 */
$app = new Slim\App($responseFactory);
$app->get('/hello/{name}', function ($request, $response, $args) {
    $response->getBody()->write("Hello, " . $args['name']);
    return $response;
});

/**
 * The App::run() method takes 1 parameter
 * 
 * @param ServerRequestInterface An instantiation of a ServerRequest
 */
$request = ServerRequest::fromGlobals();
$app->run($request);
```

You may quickly test this using the built-in PHP server:
```bash
$ php -S localhost:8000
```

Going to http://localhost:8000/hello/world will now display "Hello, world".

For more information on how to configure your web server, see the [Documentation](https://www.slimframework.com/docs/start/web-servers.html).

## Tests
To execute the test suite, you'll need to install all development dependencies.

```bash
$ git clone https://github.com/slimphp/Slim
$ composer install
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Learn More

Learn more at these links:

- [Website](https://www.slimframework.com)
- [Documentation](https://www.slimframework.com/docs/start/installation.html)
- [Support Forum](http://discourse.slimframework.com)
- [Twitter](https://twitter.com/slimphp)
- [Resources](https://github.com/xssc/awesome-slim)

## Security

If you discover security related issues, please email security@slimframework.com instead of using the issue tracker.

## Credits

- [Josh Lockhart](https://github.com/codeguy)
- [Andrew Smith](https://github.com/silentworks)
- [Rob Allen](https://github.com/akrabat)
- [Pierre Bérubé](https://github.com/l0gicgate)
- [Gabriel Manricks](https://github.com/gmanricks)
- [All Contributors](../../contributors)

## License

The Slim Framework is licensed under the MIT license. See [License File](LICENSE.md) for more information.
