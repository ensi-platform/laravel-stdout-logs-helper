<?php

namespace Ensi\LaravelLogsHelper;

use Illuminate\Support\ServiceProvider;

class LaravelLogsHelperServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->packageBasePath("/../config/laravel-logs-helper.php"), 'laravel-logs-helper');
    }

    public function boot(): void
    {

    }

    protected function packageBasePath(?string $directory = null): string
    {
        if ($directory === null) {
            return __DIR__;
        }

        return __DIR__ . DIRECTORY_SEPARATOR . ltrim($directory, DIRECTORY_SEPARATOR);
    }
}
