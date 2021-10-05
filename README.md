# Laravel stdout logs helper

Пакет для дублирования логов в stdout.

## Установка

1. `composer require ensi/laravel-stdout-logs-helper`
2. Оберните описание каналов логирования в `config/logging.php` в вызов `LaravelStdoutLogsHelper::addStdoutStacks()`
3. Добавляйте новые каналы логирования используя вспомогательные функции

Пример:

```
return LaravelStdoutLogsHelper::addStdoutStacks([
    'default' => env('LOG_CHANNEL', 'stack'),
    'channels' => [
        'daily' => LaravelStdoutLogsHelper::makeDailyChannel(storage_path('logs/laravel.log')),
        'checkout' => LaravelStdoutLogsHelper::makeDailyChannel(storage_path('logs/checkout.log'), 3)
    ],
]);

```

## Лицензия

[The MIT License (MIT)](LICENSE.md).
