# Slim Framework

[![Build Status](https://travis-ci.org/slimphp/Slim.svg?branch=develop)](https://travis-ci.org/slimphp/Slim)

Slim is a PHP micro framework that helps you quickly write simple yet powerful web applications and APIs. Learn more at these links:

- [Website](http://www.slimframework.com)
- [Documentation](http://docs.slimframework.com)
- [Support Forum](http://help.slimframework.com)
- [Twitter](https://twitter.com/slimphp)

## Install

Via [Composer](https://getcomposer.org/)

```bash
$ composer require slim/slim
```

Requires PHP 5.4.0 or newer.

## Usage

```php
$app = new \Slim\App();
$app->get('/hello/{name}', function ($request, $response, $args) {
    $response->write("Hello, " . $args['name']);
    return $response;
});
$app->run();
```

## Testing

```bash
phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email security@slimframework.com instead of using the issue tracker.

## Credits

- [Josh Lockhart](https://github.com/codeguy)
- [Andrew Smith](https://github.com/silentworks)
- [Gabriel Manricks](https://github.com/gmanricks) 
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
