<?php

namespace Ensi\LaravelLogsHelper\Tests;

use Ensi\LaravelLogsHelper\LaravelLogsHelperServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelLogsHelperServiceProvider::class,
        ];
    }
}
