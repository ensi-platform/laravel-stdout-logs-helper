# Laravel logs helper

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

| Laravel logs helper | Monolog        | Laravel                    | PHP            |
|---------------------|----------------|----------------------------|----------------|
| ^0.1.0              | ^2.3           | ^9.0 \|\| ^10.0 \|\| ^11.0 | ^7.3 \|\| ^8.0 |
| ^0.2.0              | ^2.3           | ^9.0 \|\| ^10.0 \|\| ^11.0 | ^7.3 \|\| ^8.0 |
| ^0.3.0              | ^2.0 \|\| ^3.0 | ^9.0 \|\| ^10.0 \|\| ^11.0 | ^7.3 \|\| ^8.0 |
| ^0.4.0              | ^2.0 \|\| ^3.0 | ^9.0 \|\| ^10.0 \|\| ^11.0 | ^8.1           |
| ^1.0.0              | ^3.0           | ^10.0 \|\| ^11.0           | ^8.1           |

### Migrate from 0.4 to 1.0

1. Replace namespace `Ensi\LaravelStdoutLogsHelper` to `Ensi\LaravelLogsHelper`
2. Replace method `LaravelStdoutLogsHelper::makeStdoutChannel` to `LogsConfigMaker::stdout`
3. Replace method `LaravelStdoutLogsHelper::makeStackChannel` to `LogsConfigMaker::stack`
4. Replace method `LaravelStdoutLogsHelper::makeDailyChannel` to `LogsConfigMaker::daily`

## Basic usage

### LaravelStdoutLogsHelper

In order for the channel to turn into a stack with output to stdout, you must register the `stdout_mirror` key in the source config

Example:

```php
return LaravelStdoutLogsHelper::addStdoutStacks([
    'default' => env('LOG_CHANNEL', 'stack'),
    'channels' => [
        // manual config
        'daily_1' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
            'days' => 14,
            'stdout_mirror' => true,
        ],
        // or use our helper
        'daily_1' => LogsConfigMaker::daily(storage_path('logs/laravel.log'))
    ],
]);
```

Result:

```php
// ...
'channels' => [
    'daily_1:original' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'debug',
        'days' => 14,
        'stdout_mirror' => true,
    ],
    'daily_1:stdout' => [
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => StreamHandler::class,
        'with' => [
            'stream' => 'php://stdout',
        ],
    ],
    'daily_1' => [
        'driver' => 'stack',
        'name' => 'daily_1',
        'channels' => ['daily_1:original', 'daily_1:stdout'],
        'ignore_exceptions' => false,
    ]
],
// ...
```

### DateSizeRotatingFileHandler

For a production environment, it can be important that logs are deleted not by date, but by size.  
To set up such rotation, use the `DateSizeRotatingFileHandler` class. Setting up a channel in `logger.php` Example:

```php
// ...
'channels' => [
    'my:channel' => LogsConfigMaker::dailySize(storage_path('logs/my/channel.log'))
],
// ...
```

To set up size limits (in bytes) use env:

1. LOGS_ROTATION_SIZE_ONE_FILE - the limit for one file
2. LOGS_ROTATION_SIZE_CHANNEL - the limit for one channel
3. LOGS_ROTATION_COUNT_CHANNEL - the limit for the count of files for one channel
4. LOGS_ROTATION_SIZE_TOTAL - the limit for sum of all channels with handler `DateSizeRotatingFileHandler::class`

For individual channels, you can redefine the limits using the parameter of the `dailySize` method.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

### Testing

1. composer install
2. composer test

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
