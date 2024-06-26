# Laravel stdout logs helper

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ensi/laravel-stdout-logs-helper.svg?style=flat-square)](https://packagist.org/packages/ensi/laravel-stdout-logs-helper)
[![Tests](https://github.com/ensi-platform/laravel-stdout-logs-helper/actions/workflows/run-tests.yml/badge.svg?branch=master)](https://github.com/ensi-platform/laravel-stdout-logs-helper/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/ensi/laravel-stdout-logs-helper.svg?style=flat-square)](https://packagist.org/packages/ensi/laravel-stdout-logs-helper)

Package for duplicating laravel logs in stdout

## Installation

You can install the package via composer:

```bash
composer require ensi/laravel-stdout-logs-helper
```

## Version Compatibility

| Laravel stdout logs helper | Monolog        | PHP            |
|----------------------------|----------------|----------------|
| ^0.1.0                     | ^2.3           | ^7.3 \|\| ^8.0 |
| ^0.2.0                     | ^2.3           | ^7.3 \|\| ^8.0 |
| ^0.3.0                     | ^2.0 \|\| ^3.0 | ^7.3 \|\| ^8.0 |
| ^0.4.0                     | ^2.0 \|\| ^3.0 | ^8.1           |

## Basic usage

Example:

```
return LaravelStdoutLogsHelper::addStdoutStacks([
    'default' => env('LOG_CHANNEL', 'stack'),
    'channels' => [
        'daily' => LaravelStdoutLogsHelper::makeDailyChannel(storage_path('logs/laravel.log')),
        'checkout' => LaravelStdoutLogsHelper::makeDailyChannel(storage_path('logs/checkout.log'), 3)
    ],
]);

```

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

### Testing

1. composer install
2. composer test

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
