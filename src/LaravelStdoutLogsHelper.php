<?php

namespace Ensi\LaravelStdoutLogsHelper;

use Monolog\Handler\StreamHandler;

class LaravelStdoutLogsHelper
{
    public static function addStdoutStacks(array $config, array $mirrorDrivers = ['daily', 'single']): array
    {
        if (self::isTesting()) {
            return $config;
        }

        $newChannels = [];
        foreach ($config['channels'] as $name => $channelSpec) {
            $driver = $channelSpec['driver'] ?? null;

            if (in_array($driver, $mirrorDrivers)) {
                [$stdoutName, $originalName] = self::getNamesForStack($name);

                $newChannels[$originalName] = $channelSpec;

                $stdoutLevel = $channelSpec['stdout_level'] ?? $channelSpec['level'];
                $newChannels[$stdoutName] = self::makeStdoutChannel($stdoutLevel);

                $newChannels[$name] = self::makeStackChannel($name, [$stdoutName, $originalName]);
            } else {
                $newChannels[$name] = $channelSpec;
            }
        }
        $config['channels'] = $newChannels;

        return $config;
    }

    public static function getNamesForStack(string $name): array
    {
        if (self::isTesting()) {
            return [$name];
        }

        return ["{$name}:stdout", "{$name}:original"];
    }

    public static function makeStdoutChannel(string $logLevel = 'debug'): array
    {
        return [
            'driver' => 'monolog',
            'level' => $logLevel,
            'handler' => StreamHandler::class,
            'with' => [
                'stream' => 'php://stdout',
            ],
        ];
    }

    public static function makeStackChannel(string $name, array $channels): array
    {
        return [
            'driver' => 'stack',
            'name' => $name,
            'channels' => $channels,
            'ignore_exceptions' => false,
        ];
    }

    public static function makeDailyChannel(string $path, int $ttlDays = 14, string $logLevel = 'debug', string $stdoutLevel = null): array
    {
        return [
            'driver' => 'daily',
            'path' => $path,
            'level' => $logLevel,
            'stdout_level' => $stdoutLevel,
            'days' => $ttlDays,
        ];
    }

    protected static function isTesting(): bool {
        if (!function_exists('env')) {
            return false;
        }

        return env('APP_ENV', 'production') == 'testing';
    }
}
