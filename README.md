# Slim Framework

[![Build Status](https://travis-ci.org/slimphp/Slim.svg?branch=3.x)](https://travis-ci.org/slimphp/Slim)
[![Coverage Status](https://coveralls.io/repos/github/slimphp/Slim/badge.svg?branch=3.x)](https://coveralls.io/github/slimphp/Slim?branch=3.x)
[![Financial Contributors on Open Collective](https://opencollective.com/slimphp/all/badge.svg?label=financial+contributors)](https://opencollective.com/slimphp)
[![Total Downloads](https://poser.pugx.org/slim/slim/downloads)](https://packagist.org/packages/slim/slim)
[![License](https://poser.pugx.org/slim/slim/license)](https://packagist.org/packages/slim/slim)

Slim is a PHP micro-framework that helps you quickly write simple yet powerful web applications and APIs.

## Installation

It's recommended that you use [Composer](https://getcomposer.org/) to install Slim.

```bash
$ composer require slim/slim "^3.0"
```

This will install Slim and all required dependencies. Slim requires PHP 5.5.0 or newer.

## Usage

Create an index.php file with the following contents:

```php
<?php

require 'vendor/autoload.php';

$app = new Slim\App();

$app->get('/hello/{name}', function ($request, $response, $args) {
    return $response->getBody()->write("Hello, " . $args['name']);
});

$app->run();
```

You may quickly test this using the built-in PHP server:
```bash
$ php -S localhost:8000
```

Going to http://localhost:8000/hello/world will now display "Hello, world".

For more information on how to configure your web server, see the [Documentation](https://www.slimframework.com/docs/start/web-servers.html).

## Tests

To execute the test suite, you'll need phpunit.

```bash
$ phpunit
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
