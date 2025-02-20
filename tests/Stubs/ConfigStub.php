<?php

namespace Ensi\LaravelLogsHelper\Tests\Stubs;

class ConfigStub
{
    public static function original(): array
    {
        return [
            'default' => 'stack',
            'channels' => [
                'first' => [
                    'driver' => 'single',
                    'path' => 'single.log',
                    'level' => 'debug',
                    'stdout_mirror' => true,
                ],

                'second' => [
                    'driver' => 'daily',
                    'path' => 'daily.log',
                    'level' => 'debug',
                    'days' => 14,
                ],

                'other' => [
                    'driver' => 'slack',
                ],
            ],
        ];
    }
}
