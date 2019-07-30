# Slim Framework

[![Build Status](https://travis-ci.org/slimphp/Slim.svg?branch=4.x)](https://travis-ci.org/slimphp/Slim)
[![Coverage Status](https://coveralls.io/repos/github/slimphp/Slim/badge.svg?branch=4.x)](https://coveralls.io/github/slimphp/Slim?branch=4.x)
[![Total Downloads](https://poser.pugx.org/slim/slim/downloads)](https://packagist.org/packages/slim/slim)
[![License](https://poser.pugx.org/slim/slim/license)](https://packagist.org/packages/slim/slim)

Slim is a PHP micro-framework that helps you quickly write simple yet powerful web applications and APIs.

## Installation

It's recommended that you use [Composer](https://getcomposer.org/) to install Slim.

```bash
$ composer require slim/slim:4.0.0
```

This will install Slim and all required dependencies. Slim requires PHP 7.1 or newer.

## Choose a PSR-7 Implementation & ServerRequest Creator

Before you can get up and running with Slim you will need to choose a PSR-7 implementation that best fits your application. A few notable ones:
- [Slim-Psr7](https://github.com/slimphp/Slim-Psr7) - This is the Slim Framework PSR-7 implementation
- [Nyholm/psr7](https://github.com/Nyholm/psr7) & [Nyholm/psr7-server](https://github.com/Nyholm/psr7-server) - This is the fastest, strictest and most lightweight implementation available
- [Guzzle/psr7](https://github.com/guzzle/psr7) & [http-interop/http-factory-guzzle](https://github.com/http-interop/http-factory-guzzle) - This is the implementation used by the Guzzle Client, featuring extra functionality for stream and file handling
- [zend-diactoros](https://github.com/zendframework/zend-diactoros) - This is the Zend PSR-7 implementation


## Slim-Http Decorators

[Slim-Http](https://github.com/slimphp/Slim-Http) is a set of decorators for any PSR-7 implementation that we recommend is used with Slim Framework.
To install the Slim-Http library simply run the following command:

```bash
composer require slim/http
```

The `ServerRequest` and `Response` object decorators are automatically detected and applied by the internal factories. If you have installed Slim-Http and wish to turn off automatic object decoration you can use the following statements:
```php
<?php

use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;

AppFactory::setSlimHttpDecoratorsAutomaticDetection(false);
ServerRequestCreatorFactory::setSlimHttpDecoratorsAutomaticDetection(false);

$app = AppFactory::create();

// ...
```

## Hello World using AppFactory with PSR-7 auto-detection
In order for auto-detection to work and enable you to use `AppFactory::create()` and `App::run()` without having to manually create a `ServerRequest` you need to install one of the following implementations:
- [Slim-Psr7](https://github.com/slimphp/Slim-Psr7) - Install using `composer require slim/psr7`
- [Nyholm/psr7](https://github.com/Nyholm/psr7) & [Nyholm/psr7-server](https://github.com/Nyholm/psr7-server) - Install using `composer require nyholm/psr7 nyholm/psr7-server`
- [Guzzle/psr7](https://github.com/guzzle/psr7) & [http-interop/http-factory-guzzle](https://github.com/http-interop/http-factory-guzzle) - Install using `composer require guzzlehttp/psr7 http-interop/http-factory-guzzle`
- [zend-diactoros](https://github.com/zendframework/zend-diactoros) - Install using `composer require zendframework/zend-diactoros`

```php
<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Instantiate App
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add route
$app->get('/hello/{name}', function (Request $request, Response $response, $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});

$app->run();
```

You may quickly test this using the built-in PHP server:
```bash
$ php -S localhost:8000
```

Going to http://localhost:8000/hello/world will now display "Hello, world".

For more information on how to configure your web server, see the [Documentation](https://www.slimframework.com/docs/v4/start/web-servers.html).

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
- [Documentation](https://www.slimframework.com/docs/v4/start/installation.html)
- [Slack](https://slimphp.slack.com)
- [Support Forum](https://discourse.slimframework.com)
- [Twitter](https://twitter.com/slimphp)
- [Resources](https://github.com/xssc/awesome-slim)

## Security

If you discover security related issues, please email security@slimframework.com instead of using the issue tracker.

## Professional support

Slim is part of [Tidelift](https://tidelift.com/subscription/pkg/packagist-slim-slim?utm_source=packagist-slim-slim&utm_medium=referral&utm_campaign=readme) which gives software development teams a single source for purchasing and maintaining their software, with professional grade assurances from the experts who know it best, while seamlessly integrating with existing tools.

## Contributors

### Code Contributors

This project exists thanks to all the people who contribute. [Contribute](CONTRIBUTING.md).
<a href="https://github.com/slimphp/Slim/graphs/contributors">
    <img src="https://opencollective.com/slimphp/contributors.svg?width=890&button=false" />
</a>

### Financial Contributors

Become a financial contributor and help us sustain our community. [Contribute](https://opencollective.com/slimphp/contribute)

#### Individuals

<a href="https://opencollective.com/slimphp"><img src="https://opencollective.com/slimphp/individuals.svg?width=890"></a>

#### Organizations

Support this project with your organization. Your logo will show up here with a link to your website. [Contribute](https://opencollective.com/slimphp/contribute)

<a href="https://opencollective.com/slimphp/organization/0/website"><img src="https://opencollective.com/slimphp/organization/0/avatar.svg"></a>
<a href="https://opencollective.com/slimphp/organization/1/website"><img src="https://opencollective.com/slimphp/organization/1/avatar.svg"></a>
<a href="https://opencollective.com/slimphp/organization/2/website"><img src="https://opencollective.com/slimphp/organization/2/avatar.svg"></a>
<a href="https://opencollective.com/slimphp/organization/3/website"><img src="https://opencollective.com/slimphp/organization/3/avatar.svg"></a>
<a href="https://opencollective.com/slimphp/organization/4/website"><img src="https://opencollective.com/slimphp/organization/4/avatar.svg"></a>
<a href="https://opencollective.com/slimphp/organization/5/website"><img src="https://opencollective.com/slimphp/organization/5/avatar.svg"></a>
<a href="https://opencollective.com/slimphp/organization/6/website"><img src="https://opencollective.com/slimphp/organization/6/avatar.svg"></a>
<a href="https://opencollective.com/slimphp/organization/7/website"><img src="https://opencollective.com/slimphp/organization/7/avatar.svg"></a>
<a href="https://opencollective.com/slimphp/organization/8/website"><img src="https://opencollective.com/slimphp/organization/8/avatar.svg"></a>
<a href="https://opencollective.com/slimphp/organization/9/website"><img src="https://opencollective.com/slimphp/organization/9/avatar.svg"></a>

## License

The Slim Framework is licensed under the MIT license. See [License File](LICENSE.md) for more information.
