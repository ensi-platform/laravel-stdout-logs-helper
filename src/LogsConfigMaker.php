<?php

namespace Ensi\LaravelLogsHelper;

use App\Domain\Common\DateSizeRotatingFileHandler;
use Monolog\Handler\StreamHandler;

class LogsConfigMaker
{
    public static function stdout(string $logLevel = 'debug'): array
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

    public static function stack(string $name, array $channels): array
    {
        return [
            'driver' => 'stack',
            'name' => $name,
            'channels' => $channels,
            'ignore_exceptions' => false,
        ];
    }

    public static function daily(
        string $path,
        int $ttlDays = 14,
        string $logLevel = 'debug',
        ?string $stdoutLevel = null,
        bool $stdoutMirror = true,
    ): array {
        return [
            'driver' => 'daily',
            'path' => $path,
            'level' => $logLevel,
            'stdout_level' => $stdoutLevel,
            'days' => $ttlDays,
            'stdout_mirror' => $stdoutMirror,
        ];
    }

    public static function dailySize(
        string $filename,
        ?int $oneFileSizeLimitBytes = null,
        ?int $channelSizeLimitBytes = null,
        bool $stdoutMirror = true,
        array $with = [],
    ): array {
        return [
            'driver' => 'monolog',
            'handler' => DateSizeRotatingFileHandler::class,
            'stdout_mirror' => $stdoutMirror,
            'with' => array_merge([
                'filename' => $filename,
                'oneFileSizeLimitBytes' => $oneFileSizeLimitBytes,
                'channelSizeLimitBytes' => $channelSizeLimitBytes,
                'dateFormat' => DateSizeRotatingFileHandler::FILE_PER_DAY,
            ], $with),
        ];
    }
}
