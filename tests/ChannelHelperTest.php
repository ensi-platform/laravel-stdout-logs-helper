<?php

use Ensi\LaravelStdoutLogsHelper\LaravelStdoutLogsHelper;
use PHPUnit\Framework\TestCase;

class ChannelHelperTest extends TestCase
{
    private const ORIGINAL_CONFIG = [
        'default' => 'stack',
        'channels' => [
            'first' => [
                'driver' => 'single',
                'path' => 'single.log',
                'level' => 'debug',
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

    public function testAddStdoutStacks()
    {
        $config = LaravelStdoutLogsHelper::addStdoutStacks(self::ORIGINAL_CONFIG, ['single']);

        $this->assertArrayHasKey('first:stdout', $config['channels']);
        $this->assertEquals('monolog', $config['channels']['first:stdout']['driver']);

        $this->assertArrayHasKey('first:original', $config['channels']);
        $this->assertEquals('single', $config['channels']['first:original']['driver']);

        $this->assertArrayHasKey('first', $config['channels']);
        $this->assertEquals('stack', $config['channels']['first']['driver']);

        $this->assertArrayHasKey('second', $config['channels']);
        $this->assertEquals('daily', $config['channels']['second']['driver']);

        $this->assertArrayHasKey('other', $config['channels']);
        $this->assertEquals('slack', $config['channels']['other']['driver']);
    }

    public function testMakeDailyChannel()
    {
        $channelSpec = LaravelStdoutLogsHelper::makeDailyChannel('/path/to/file.log');
        $this->assertArrayHasKey('driver', $channelSpec);
        $this->assertArrayHasKey('path', $channelSpec);
        $this->assertArrayHasKey('level', $channelSpec);
        $this->assertArrayHasKey('days', $channelSpec);
    }

    public function makeStdoutChannel()
    {
        $channelSpec = LaravelStdoutLogsHelper::makeStdoutChannel();
        $this->assertArrayHasKey('driver', $channelSpec);
        $this->assertArrayHasKey('handler', $channelSpec);
        $this->assertArrayHasKey('level', $channelSpec);
        $this->assertArrayHasKey('with', $channelSpec);
    }

    public function testStdoutLogLevel()
    {
        $makeChannels = function ($baseLevel, $stdoutLevel) {
            return LaravelStdoutLogsHelper::addStdoutStacks([
                'channels' => [
                    'first' => LaravelStdoutLogsHelper::makeDailyChannel('/path/to/file.log', 3, $baseLevel, $stdoutLevel)
                ]
            ]);
        };

        $config = $makeChannels('debug', null);
        $this->assertEquals('debug', $config['channels']['first:original']['level']);
        $this->assertEquals('debug', $config['channels']['first:stdout']['level']);

        $config = $makeChannels('debug', 'info');
        $this->assertEquals('debug', $config['channels']['first:original']['level']);
        $this->assertEquals('info', $config['channels']['first:stdout']['level']);
    }
}
